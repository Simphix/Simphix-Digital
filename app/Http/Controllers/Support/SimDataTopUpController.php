<?php

namespace App\Http\Controllers\Support;

use App\Helpers\RequestIdHelper;
use App\Http\Controllers\Controller;
use App\Models\ActivationBonusSettings;
use App\Models\Report;
use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Models\SmeData;
use App\Models\SmeDataProviderSetting;
use App\Services\SmeDataProviderFactory;
use App\Support\SimBonusPeriod;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Support-desk SIM data top-ups and 28-day activation bonus fulfillment desk.
 */
class SimDataTopUpController extends Controller
{
    private const TRACKED_TYPES = ['data', 'activation_bonus_data', 'admin_data_topup'];

    public function index(Request $request): View
    {
        $activeTab = $request->query('tab', 'triggered');

        // Triggered Activation Bonuses Query
        $triggeredSearch = $request->query('triggered_search');
        $triggeredNetwork = $request->query('triggered_network');

        $triggeredGrants = SimActivationBonusGrant::where('status', SimActivationBonusGrant::STATUS_PENDING)
            ->with(['sim.user', 'sim.partner', 'sim.business'])
            ->when($triggeredNetwork, function ($q, $net) {
                $q->whereHas('sim', fn ($sq) => $sq->where('provider', strtolower($net)));
            })
            ->when($triggeredSearch, function ($q, $search) {
                $q->whereHas('sim', fn ($sq) => $sq->where('number', 'like', "%{$search}%"));
            })
            ->latest('triggered_at')
            ->paginate(15, ['*'], 'triggered_page')
            ->withQueryString();

        $pendingTriggeredCount = SimActivationBonusGrant::where('status', SimActivationBonusGrant::STATUS_PENDING)->count();

        // Bonus plan configurations by provider
        $bonusSettings = [];
        foreach (ActivationBonusSettings::PROVIDERS as $provider) {
            $bonusSettings[$provider] = ActivationBonusSettings::forProvider($provider);
        }

        // Recent Top-Up History
        $filters = $this->resolveFilters($request);
        $rows = $this->topUpsQuery($filters)->paginate(20, ['*'], 'history_page')->withQueryString();

        $monthStart = now()->startOfMonth();
        $thisMonth = Report::whereIn('type', self::TRACKED_TYPES)
            ->where('created_at', '>=', $monthStart)
            ->get(['type', 'status', 'network', 'description']);

        $countByType = $thisMonth->groupBy('type')->map->count();
        $volumeByNetwork = $thisMonth
            ->where('status', 'completed')
            ->groupBy('network')
            ->map(fn ($group) => round($group->sum(fn ($r) => $this->parseDataSizeToMb($r->description)), 2));

        $completedBonusesThisMonth = SimActivationBonusGrant::whereIn('status', [SimActivationBonusGrant::STATUS_COMPLETED, SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY])
            ->where('granted_at', '>=', $monthStart)
            ->count();

        return view('admin.sim-data-topup.index', [
            'activeTab' => $activeTab,
            'triggeredGrants' => $triggeredGrants,
            'pendingTriggeredCount' => $pendingTriggeredCount,
            'bonusSettings' => $bonusSettings,
            'filters' => $filters,
            'rows' => $rows,
            'countByType' => $countByType,
            'volumeByNetwork' => $volumeByNetwork,
            'completedBonusesThisMonth' => $completedBonusesThisMonth,
            'networks' => SmeData::select('network')->distinct()->pluck('network'),
            'triggeredSearch' => $triggeredSearch,
            'triggeredNetwork' => $triggeredNetwork,
        ]);
    }

    /**
     * Completes action on a triggered SIM by dispatching the data top-up through SME data provider.
     */
    public function completeTriggeredBonus(Request $request, SimActivationBonusGrant $grant): RedirectResponse
    {
        if (!$grant->isPending()) {
            return back()->with('error', 'This activation bonus trigger has already been processed.');
        }

        $sim = $grant->sim;
        $settings = ActivationBonusSettings::forProvider($sim->provider);

        $planId = $request->input('plan');
        if ($planId) {
            $plan = SmeData::where('data_id', $planId)->where('provider', SmeDataProviderSetting::activeProvider())->first();
        } else {
            $plan = $settings->plan;
        }

        if (!$plan) {
            return back()->with('error', "No valid data plan configured for {$sim->provider}. Please select a plan.");
        }

        $actor = $request->user();
        $requestId = RequestIdHelper::generateRequestId();
        $cycle = min(12, $grant->period_index + 1);
        $description = "Activation bonus ({$cycle}/12): {$plan->size} {$plan->plan_type} for {$sim->number} ({$plan->network}), completed by "
            . trim($actor->first_name . ' ' . $actor->last_name);

        $recipientId = $sim->user_id ?? $sim->partner_id ?? $sim->business_id ?? $sim->coordinator_id ?? $sim->regional_manager_id;
        $balance = $recipientId ? (\App\Models\Wallet::where('user_id', $recipientId)->value('balance') ?? 0.00) : 0.00;

        $report = Report::create([
            'user_id' => $recipientId,
            'phone_number' => $sim->number,
            'network' => $plan->network,
            'ref' => $requestId,
            'amount' => 0.00,
            'status' => 'processing',
            'type' => 'activation_bonus_data',
            'description' => $description,
            'old_balance' => $balance,
            'new_balance' => $balance,
            'service_id' => null,
        ]);

        try {
            $result = SmeDataProviderFactory::forProvider($plan->provider)->purchase(
                $sim->number,
                $plan->network,
                $plan->data_id,
                $requestId,
                $plan->vendor_amount
            );

            if ($result['success']) {
                $report->update([
                    'ref' => $result['transaction_ref'] ?? $requestId,
                    'status' => 'completed',
                ]);

                $grant->update([
                    'status' => SimActivationBonusGrant::STATUS_COMPLETED,
                    'granted_at' => now(),
                    'resolved_at' => now(),
                    'resolution_type' => 'vendor_topup',
                    'report_id' => $report->id,
                    'admin_id' => $actor->id,
                    'admin_notes' => "Completed by {$actor->first_name} {$actor->last_name}",
                ]);

                return back()->with('success', "Activation bonus ({$cycle}/12) of {$plan->size} successfully granted to {$sim->number}.");
            }

            $errorMessage = $result['data']['message'] ?? $result['message'] ?? 'Top-up failed. Please try again.';
            $report->update([
                'status' => 'failed',
                'description' => "Failed ({$cycle}/12): {$errorMessage}",
            ]);

            return back()->with('error', "Data top-up failed: {$errorMessage}");
        } catch (\Throwable $e) {
            Log::error('Activation bonus completion error: ' . $e->getMessage());
            $report->update([
                'status' => 'failed',
                'description' => "Failed ({$cycle}/12): Connection Error - {$e->getMessage()}",
            ]);

            return back()->with('error', 'Could not connect to data provider. Please try again later.');
        }
    }

    /**
     * Resolves a triggered bonus silently without dispatching data.
     */
    public function resolveSilently(Request $request, SimActivationBonusGrant $grant): RedirectResponse
    {
        if (!$grant->isPending()) {
            return back()->with('error', 'This activation bonus trigger has already been processed.');
        }

        $actor = $request->user();
        $sim = $grant->sim;
        $cycle = min(12, $grant->period_index + 1);
        $notes = $request->input('notes') ?: "Resolved silently by {$actor->first_name} {$actor->last_name} (manual external top-up).";

        $grant->update([
            'status' => SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY,
            'granted_at' => now(),
            'resolved_at' => now(),
            'resolution_type' => 'silent',
            'admin_id' => $actor->id,
            'admin_notes' => $notes,
        ]);

        return back()->with('success', "SIM {$sim->number} resolved silently. Bonus cycle ({$cycle}/12) marked as completed without dispatching data.");
    }

    /**
     * On-demand check for activated SIMs reaching 28 days.
     */
    public function run28DayCheck(): RedirectResponse
    {
        Artisan::call('sim:grant-bulk-activation-bonus');
        $output = trim(Artisan::output());

        return back()->with('success', '28-day trigger check completed. ' . $output);
    }

    /**
     * Bulk action on multiple triggered SIMs (complete or resolve silent).
     */
    public function bulkActionTriggered(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:complete,resolve_silent',
            'grant_ids' => 'required|array|min:1',
            'grant_ids.*' => 'integer|exists:sim_activation_bonus_grants,id',
        ]);

        $grants = SimActivationBonusGrant::whereIn('id', $validated['grant_ids'])
            ->where('status', SimActivationBonusGrant::STATUS_PENDING)
            ->with('sim')
            ->get();

        if ($grants->isEmpty()) {
            return back()->with('error', 'No eligible pending triggers selected.');
        }

        $action = $validated['action'];
        $actor = $request->user();
        $successCount = 0;
        $failCount = 0;

        foreach ($grants as $grant) {
            $sim = $grant->sim;
            $cycle = min(12, $grant->period_index + 1);

            if ($action === 'resolve_silent') {
                $grant->update([
                    'status' => SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY,
                    'granted_at' => now(),
                    'resolved_at' => now(),
                    'resolution_type' => 'silent',
                    'admin_id' => $actor->id,
                    'admin_notes' => "Bulk resolved silently by {$actor->first_name} {$actor->last_name}",
                ]);
                $successCount++;
            } elseif ($action === 'complete') {
                $settings = ActivationBonusSettings::forProvider($sim->provider);
                $plan = $settings->plan;
                if (!$plan) {
                    $failCount++;
                    continue;
                }

                $requestId = RequestIdHelper::generateRequestId();
                $recipientId = $sim->user_id ?? $sim->partner_id ?? $sim->business_id;
                $balance = $recipientId ? (\App\Models\Wallet::where('user_id', $recipientId)->value('balance') ?? 0.00) : 0.00;

                $report = Report::create([
                    'user_id' => $recipientId,
                    'phone_number' => $sim->number,
                    'network' => $plan->network,
                    'ref' => $requestId,
                    'amount' => 0.00,
                    'status' => 'processing',
                    'type' => 'activation_bonus_data',
                    'description' => "Activation bonus ({$cycle}/12): {$plan->size} {$plan->plan_type} for {$sim->number} (Bulk action)",
                    'old_balance' => $balance,
                    'new_balance' => $balance,
                ]);

                try {
                    $result = SmeDataProviderFactory::forProvider($plan->provider)->purchase(
                        $sim->number,
                        $plan->network,
                        $plan->data_id,
                        $requestId,
                        $plan->vendor_amount
                    );

                    if ($result['success']) {
                        $report->update(['ref' => $result['transaction_ref'] ?? $requestId, 'status' => 'completed']);
                        $grant->update([
                            'status' => SimActivationBonusGrant::STATUS_COMPLETED,
                            'granted_at' => now(),
                            'resolved_at' => now(),
                            'resolution_type' => 'vendor_topup',
                            'report_id' => $report->id,
                            'admin_id' => $actor->id,
                        ]);
                        $successCount++;
                    } else {
                        $report->update(['status' => 'failed']);
                        $failCount++;
                    }
                } catch (\Throwable $e) {
                    $report->update(['status' => 'failed']);
                    $failCount++;
                }
            }
        }

        $msg = "Processed {$successCount} trigger(s).";
        if ($failCount > 0) {
            $msg .= " ({$failCount} failed).";
        }

        return back()->with('success', $msg);
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

    /** AJAX: the agent's "review before confirm" step — top-up history, not a real vendor usage figure (see Admin\SimDataTopUpController). */
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

    /** Never trusts the client's plan selection beyond a basic shape check — see Admin\SimDataTopUpController::topup(). */
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
        $description = "Support data top-up: {$plan->size} {$plan->plan_type} for {$sim->number} ({$plan->network}), granted by "
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
            Log::error('Support SIM data top-up connection error: ' . $e->getMessage());
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

    /** Same description text-parsing convenience as Admin\SimDataTopUpController — see there for why. */
    private function parseDataSizeToMb(?string $description): float
    {
        if (!$description || !preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $description, $m)) {
            return 0.0;
        }

        $value = (float) $m[1];

        return strtoupper($m[2]) === 'GB' ? $value * 1024 : $value;
    }
}
