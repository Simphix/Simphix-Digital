<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnterprisePartnerApplication;
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
     * Never trusts the client's quantity inputs beyond a basic shape check —
     * the real "never more than ordered, never more than live stock" cap is
     * derived server-side inside EnterprisePartnerFulfillmentService::fulfill().
     */
    public function fulfill(Request $request, EnterprisePartnerApplication $application)
    {
        if ($application->status !== 'awaiting_fulfillment') {
            return back()->with('error', 'This order is not awaiting fulfillment (already processed, or payment not yet confirmed).');
        }

        $validated = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $summary = $this->fulfillmentService->fulfill($application, $validated['quantities'], $request->user());
            $totalFulfilled = array_sum(array_column($summary, 'fulfilled'));

            return back()->with('success', "Order fulfilled — {$totalFulfilled} SIM(s) assigned. The Enterprise Partner's dashboard is now unlocked.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
