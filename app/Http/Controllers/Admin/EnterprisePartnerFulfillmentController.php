<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerApplicationItem;
use App\Models\Sim;
use App\Services\EnterprisePartnerFulfillmentService;
use App\Support\SimStatus;
use Illuminate\Http\Request;

class EnterprisePartnerFulfillmentController extends Controller
{
    public function __construct(private EnterprisePartnerFulfillmentService $fulfillmentService)
    {
    }

    /**
     * Pending: paid orders awaiting an admin's fulfillment decision, with
     * the live stock for every category/network combo they reference (real
     * counts — this is an internal admin screen, unlike the EP-facing
     * cart's boolean-only availability). Resolved: a paginated fulfillment
     * history log, mirroring CashOutController::index()'s pending/resolved
     * split.
     */
    public function index()
    {
        $pending = EnterprisePartnerApplication::with(['items', 'user'])
            ->where('status', 'awaiting_fulfillment')
            ->latest('paid_at')
            ->get();

        $stockMatrix = [];
        foreach ($pending as $application) {
            foreach ($application->items as $item) {
                $key = $item->sim_category . '|' . $item->provider;
                if (!isset($stockMatrix[$key])) {
                    $stockMatrix[$key] = Sim::where('status', SimStatus::UNASSIGNED)
                        ->where('category', $item->sim_category)
                        ->where('provider', $item->provider)
                        ->count();
                }
            }
        }

        $resolved = EnterprisePartnerApplication::with(['items', 'user', 'fulfilledBy'])
            ->where('status', 'fulfilled')
            ->latest('fulfilled_at')
            ->paginate(10, ['*'], 'applications_page')
            ->withQueryString();

        return view('admin.enterprise-partners.fulfillment', compact('pending', 'resolved', 'stockMatrix'));
    }

    /**
     * The admin submits specific sim_ids per item (picked via the "Select
     * SIMs" modal on the fulfillment page — either checked from a live
     * list or resolved from pasted numbers via resolveItemNumbers()). A
     * bad or raced-away id is tolerated, not a validation failure — it's
     * silently skipped inside EnterprisePartnerFulfillmentService::fulfill(),
     * matching SimPlanController::approveRequest()'s tolerant philosophy.
     */
    public function fulfill(Request $request, EnterprisePartnerApplication $application)
    {
        if ($application->status !== 'awaiting_fulfillment') {
            return back()->with('error', 'This order is not awaiting fulfillment (already processed, or payment not yet confirmed).');
        }

        $validated = $request->validate([
            'sim_ids' => ['nullable', 'array'],
            'sim_ids.*' => ['array'],
            'sim_ids.*.*' => ['integer'],
        ]);

        try {
            $summary = $this->fulfillmentService->fulfill($application, $validated['sim_ids'] ?? [], $request->user());
            $totalFulfilled = array_sum(array_column($summary, 'fulfilled'));

            return back()->with('success', "Order fulfilled — {$totalFulfilled} SIM(s) assigned. The Enterprise Partner's dashboard is now unlocked.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * AJAX: resolve admin-pasted SIM numbers for a single fulfillment line
     * item, mirroring SimPlanController::resolveRequestNumbers() but scoped
     * to an EnterprisePartnerApplicationItem's category/provider instead of
     * a SimRequest's. Read-only — fulfill() re-validates everything for
     * real inside its own locked transaction.
     */
    public function resolveItemNumbers(Request $request, EnterprisePartnerApplicationItem $item)
    {
        if ($item->application->status !== 'awaiting_fulfillment') {
            return response()->json(['resolved' => [], 'errors' => ['This order is not awaiting fulfillment.']]);
        }

        $request->validate(['numbers' => 'required|string']);

        $rawNumbers = preg_split('/[\r\n,]+/', $request->numbers);
        $resolved = [];
        $errors = [];
        $seen = [];

        foreach ($rawNumbers as $rawNumber) {
            $number = trim($rawNumber);
            if ($number === '' || isset($seen[$number])) {
                continue;
            }
            $seen[$number] = true;

            if (strlen($number) !== 11 || !ctype_digit($number)) {
                $errors[] = "{$number} — Invalid format (must be 11 digits)";
                continue;
            }

            $sim = Sim::where('number', $number)->first();

            if (!$sim) {
                $errors[] = "{$number} — Not found";
                continue;
            }

            if ($sim->category !== $item->sim_category || $sim->provider !== $item->provider) {
                $errors[] = "{$number} — Wrong category/provider for this item";
                continue;
            }

            if ($sim->status === SimStatus::ACTIVATED) {
                $errors[] = "{$number} — Already activated";
                continue;
            }

            if ($sim->status !== SimStatus::UNASSIGNED) {
                $errors[] = "{$number} — Already assigned";
                continue;
            }

            $resolved[] = ['id' => $sim->id, 'number' => $sim->number];
        }

        return response()->json(['resolved' => $resolved, 'errors' => $errors]);
    }
}
