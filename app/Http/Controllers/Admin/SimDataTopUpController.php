<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\RequestIdHelper;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Sim;
use App\Models\SmeData;
use App\Models\SmeDataProviderSetting;
use App\Services\SmeDataProviderFactory;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Admin-granted SIM data top-ups: free (no wallet touched), routed through
 * whichever SME-data vendor is currently active, with no monthly cap —
 * deliberately a separate code path from GrantActivationBonusData's
 * once-per-rolling-month automatic bonus (SimBonusPeriod/
 * SimActivationBonusGrant are never consulted here). Only ACTIVATED SIMs
 * with a genuine current holder are eligible, so every Report row this
 * writes always has a real user_id to attribute to.
 */
class SimDataTopUpController extends Controller
{
    private const TRACKED_TYPES = ['data', 'activation_bonus_data', 'admin_data_topup'];

    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);
        $rows = $this->topUpsQuery($filters)->paginate(20)->withQueryString();

        $monthStart = now()->startOfMonth();
        $thisMonth = Report::whereIn('type', self::TRACKED_TYPES)
            ->where('created_at', '>=', $monthStart)
            ->get(['type', 'status', 'network', 'description']);

        $countByType = $thisMonth->groupBy('type')->map->count();
        $volumeByNetwork = $thisMonth
            ->where('status', 'completed')
            ->groupBy('network')
            ->map(fn ($group) => round($group->sum(fn ($r) => $this->parseDataSizeToMb($r->description)), 2));

        return view('admin.sim-data-topup.index', [
            'filters' => $filters,
            'rows' => $rows,
            'countByType' => $countByType,
            'volumeByNetwork' => $volumeByNetwork,
            'networks' => SmeData::select('network')->distinct()->pluck('network'),
        ]);
    }

    /** AJAX: SIMs matching a partial number — only ACTIVATED SIMs with a genuine current holder are eligible. */
    public function searchSims(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $sims = Sim::where('status', SimStatus::ACTIVATED)
            ->whereNotNull('user_id')
            ->where('number', 'like', '%' . $request->q . '%')
            ->orderBy('number')
            ->limit(10)
            ->get(['id', 'number', 'category', 'provider']);

        return response()->json($sims);
    }

    /**
     * AJAX: the admin's "review before confirm" step. No vendor exposes a
     * real data-usage/balance API — this is top-up HISTORY (every Report
     * this SIM's number appears on, across all three top-up paths) over
     * the trailing 30 days, not an actual consumption figure.
     */
    public function usageReview(Sim $sim): JsonResponse
    {
        $since = now()->subMonth();

        $rows = Report::where('phone_number', $sim->number)
            ->whereIn('type', self::TRACKED_TYPES)
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->get(['type', 'network', 'description', 'created_at']);

        $totalMb = $rows->sum(fn ($r) => $this->parseDataSizeToMb($r->description));

        return response()->json([
            'sim' => ['number' => $sim->number, 'provider' => $sim->provider, 'category' => $sim->category],
            'since' => $since->toDateTimeString(),
            'count' => $rows->count(),
            'total_mb' => round($totalMb, 2),
            'history' => $rows->map(fn ($r) => [
                'type' => $r->type,
                'network' => $r->network,
                'description' => $r->description,
                'date' => $r->created_at->format('M d, Y H:i'),
            ]),
        ]);
    }

    /** AJAX: purchasable plans for this SIM's own network, on the currently-active vendor only. */
    public function plans(Sim $sim): JsonResponse
    {
        $plans = SmeData::where('provider', SmeDataProviderSetting::activeProvider())
            ->where('network', strtoupper($sim->provider))
            ->where('status', 'enabled')
            ->get(['data_id', 'plan_type', 'size', 'validity', 'network']);

        return response()->json($plans);
    }

    /**
     * Never trusts the client's plan selection beyond a basic shape check —
     * re-resolves the plan against the active vendor and re-checks it
     * matches this SIM's own network before ever calling the vendor.
     */
    public function topup(Request $request, Sim $sim): RedirectResponse
    {
        $validated = $request->validate(['plan' => 'required|string']);

        $plan = SmeData::where('data_id', $validated['plan'])
            ->where('provider', SmeDataProviderSetting::activeProvider())
            ->first();

        if (!$plan) {
            return back()->with('error', 'Invalid or no-longer-available data plan.');
        }

        if (strtoupper($plan->network) !== strtoupper($sim->provider)) {
            return back()->with('error', "This plan does not match {$sim->number}'s network.");
        }

        $actor = $request->user();
        $requestId = RequestIdHelper::generateRequestId();
        $description = "Admin data top-up: {$plan->size} {$plan->plan_type} for {$sim->number} ({$plan->network}), granted by "
            . trim($actor->first_name . ' ' . $actor->last_name);

        $report = DB::transaction(fn () => Report::create([
            'user_id' => $sim->user_id,
            'phone_number' => $sim->number,
            'network' => $plan->network,
            'ref' => $requestId,
            'amount' => 0.00,
            'status' => 'processing',
            'type' => 'admin_data_topup',
            'description' => $description,
            'old_balance' => 0.00,
            'new_balance' => 0.00,
            'service_id' => null,
        ]));

        try {
            $result = SmeDataProviderFactory::make()->purchase($sim->number, $plan->network, $plan->data_id, $requestId, $plan->vendor_amount);

            if ($result['success']) {
                $report->update(['ref' => $result['transaction_ref'], 'status' => 'completed']);

                return back()->with('success', "Top-up of {$plan->size} ({$plan->plan_type}) for {$sim->number} was successful.");
            }

            $errorMessage = $result['data']['message'] ?? $result['message'] ?? 'Top-up failed. Please try again.';
            $report->update(['status' => 'failed', 'description' => "Failed: {$errorMessage}"]);

            return back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            Log::error('Admin SIM data top-up connection error: ' . $e->getMessage());
            $report->update(['status' => 'failed', 'description' => "Failed: Connection Error - {$e->getMessage()}"]);

            return back()->with('error', 'Could not connect to data provider. Please try again later.');
        }
    }

    private function resolveFilters(Request $request): array
    {
        return [
            'date_start' => $request->filled('date_start') ? Carbon::parse($request->date_start)->startOfDay() : null,
            'date_end' => $request->filled('date_end') ? Carbon::parse($request->date_end)->endOfDay() : null,
            'network' => $request->filled('network') ? $request->network : null,
            'type' => $request->filled('type') ? $request->type : null,
        ];
    }

    private function topUpsQuery(array $filters): Builder
    {
        return Report::whereIn('type', self::TRACKED_TYPES)
            ->when($filters['date_start'], fn ($q) => $q->where('created_at', '>=', $filters['date_start']))
            ->when($filters['date_end'], fn ($q) => $q->where('created_at', '<=', $filters['date_end']))
            ->when($filters['network'], fn ($q) => $q->where('network', $filters['network']))
            ->when($filters['type'], fn ($q) => $q->where('type', $filters['type']))
            ->with('user')
            ->latest();
    }

    /**
     * Report has no structured data-size column — every purchase path
     * (buySMEdata, GrantActivationBonusData, and this controller's own
     * topup()) writes description in the consistent shape
     * "{size} {plan_type} for {mobile} (...)". Extracts the leading size
     * token and normalizes to MB so amounts across GB/MB rows can be
     * summed. This is a text-parsing convenience over an existing field,
     * not a real structured metric — returns 0.0 if the pattern isn't
     * found (e.g. a failed/legacy row).
     */
    private function parseDataSizeToMb(?string $description): float
    {
        if (!$description || !preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $description, $m)) {
            return 0.0;
        }

        $value = (float) $m[1];

        return strtoupper($m[2]) === 'GB' ? $value * 1024 : $value;
    }
}
