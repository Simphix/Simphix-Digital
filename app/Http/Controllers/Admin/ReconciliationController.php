<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reconciliation\ReconciliationEngine;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only reconciliation dashboard: one tab per category
 * (App\Services\Reconciliation\ReconciliationEngine::RECONCILERS), each
 * showing findings for the selected date range. Deliberately detection-only
 * — no corrective actions taken from here, see docs on the individual
 * reconcilers for what each one checks.
 */
class ReconciliationController extends Controller
{
    public function index(Request $request, string $tab = 'p2p'): View
    {
        abort_unless(array_key_exists($tab, ReconciliationEngine::RECONCILERS), 404);

        $range = $request->input('range', '7days');
        [$rangeStart, $rangeEnd] = match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '3days' => [now()->subDays(2)->startOfDay(), now()->endOfDay()],
            'custom' => [
                $request->filled('period_start') ? \Carbon\Carbon::parse($request->period_start)->startOfDay() : now()->subDays(6)->startOfDay(),
                $request->filled('period_end') ? \Carbon\Carbon::parse($request->period_end)->endOfDay() : now()->endOfDay(),
            ],
            default => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
        };
        $periodStart = $rangeStart->format('Y-m-d');
        $periodEnd = $rangeEnd->format('Y-m-d');

        $engine = app(ReconciliationEngine::class);

        $stuckThresholdMinutes = (int) $request->input('stuck_threshold_minutes', 60);
        $findings = $engine->run($tab, $rangeStart, $rangeEnd, ['stuckThresholdMinutes' => $stuckThresholdMinutes]);

        // Per-tab badge counts — re-runs every reconciler on each page load.
        // Fine given this app's current data scale (confirmed near-empty
        // across the money-moving tables this reads); the first thing to
        // cache/lazy-load if that ever changes.
        $summary = collect(ReconciliationEngine::RECONCILERS)->keys()->mapWithKeys(
            fn ($key) => [$key => $engine->run($key, $rangeStart, $rangeEnd, ['stuckThresholdMinutes' => $stuckThresholdMinutes])->count()]
        );

        return view('admin.reconciliation.index', compact('tab', 'findings', 'summary', 'range', 'periodStart', 'periodEnd', 'stuckThresholdMinutes'));
    }
}
