<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ActivationBonusRetryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Failed activation-bonus data top-ups for the support desk — the same list
 * the admin SIM Plans page shows, plus the same Retry. Deliberately has no
 * access to the per-category Activation Controls kill-switch (that stays
 * super_admin only, see Admin\SimPlanController::toggleActivation).
 */
class FailedActivationController extends Controller
{
    public function index(): View
    {
        // 'retrying' rows are a retry claimed but not yet resolved — shown
        // (non-actionable) so an agent can see it's in flight; a resolved
        // ('completed') row drops out of this list entirely.
        $failedActivations = Report::where('type', 'activation_bonus_data')
            ->whereIn('status', ['failed', 'retrying'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $failedCount = Report::where('type', 'activation_bonus_data')->where('status', 'failed')->count();
        $retryingCount = Report::where('type', 'activation_bonus_data')->where('status', 'retrying')->count();

        return view('support-desk.failed-activations.index', compact('failedActivations', 'failedCount', 'retryingCount'));
    }

    public function retry(Report $report, ActivationBonusRetryService $retry): RedirectResponse
    {
        [$ok, $message] = $retry->retry($report);

        return back()->with($ok ? 'success' : 'error', $message);
    }
}
