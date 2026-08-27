<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\State;
use App\Models\User;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The first "Advanced Dashboard" admin tool: filterable metrics for SIM
 * activations (date range, region/state/senatorial district, user). Every
 * widget composes off the same base Sim query closure so the filters apply
 * consistently everywhere on the page.
 */
class SimsOperationsDashboardController extends Controller
{
    private const HOLDER_COLUMNS = ['user_id', 'partner_id', 'coordinator_id', 'regional_manager_id', 'business_id', 'business_agent_id'];

    public function index(Request $request): View
    {
        [$rangeStart, $rangeEnd, $range, $periodStart, $periodEnd] = $this->resolveRange($request);
        $filters = $this->resolveFilters($request);
        $base = $this->baseQuery($filters);

        $totalActivations = $this->activationCount($base, $rangeStart, $rangeEnd);
        $byJurisdiction = $this->activationsByJurisdiction($base, $rangeStart, $rangeEnd);
        $trend = $this->activationTrend($base, $rangeStart, $rangeEnd);
        $byCategory = $this->activationsByCategory($base, $rangeStart, $rangeEnd);
        $byProvider = $this->activationsByProvider($base, $rangeStart, $rangeEnd);
        $events = $this->activationEvents($base, $rangeStart, $rangeEnd)->paginate(20)->withQueryString();

        $regions = Region::orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $districts = SenatorialDistrict::orderBy('name')->get();
        $users = User::whereIn('role', ['regional_manager', 'coordinator', 'partner', 'business', 'business_agent'])
            ->orderBy('first_name')
            ->get();

        return view('admin.sims-operations.index', compact(
            'range', 'periodStart', 'periodEnd', 'filters',
            'totalActivations', 'byJurisdiction', 'trend', 'byCategory', 'byProvider', 'events',
            'regions', 'states', 'districts', 'users'
        ));
    }

    public function exportCsv(Request $request): Response
    {
        [$rangeStart, $rangeEnd] = $this->resolveRange($request);
        $filters = $this->resolveFilters($request);
        $base = $this->baseQuery($filters);

        $rows = $this->activationEvents($base, $rangeStart, $rangeEnd)->get();

        $csv = "Date,Sim Number,Category,Provider,Activated For,Jurisdiction,Activated By\n";
        foreach ($rows as $history) {
            $sim = $history->sim;
            $jurisdiction = $this->resolveJurisdictionLabel($sim);
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
                $history->created_at->toDateTimeString(),
                $sim?->number,
                $sim?->category,
                $sim?->provider,
                $sim?->user ? trim($sim->user->first_name . ' ' . $sim->user->last_name) : '',
                $jurisdiction,
                $history->actor ? trim($history->actor->first_name . ' ' . $history->actor->last_name) : '',
            ])) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sim-activations-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    private function resolveRange(Request $request): array
    {
        $range = $request->input('range', '7days');
        [$rangeStart, $rangeEnd] = match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '3days' => [now()->subDays(2)->startOfDay(), now()->endOfDay()],
            'custom' => [
                $request->filled('period_start') ? Carbon::parse($request->period_start)->startOfDay() : now()->subDays(6)->startOfDay(),
                $request->filled('period_end') ? Carbon::parse($request->period_end)->endOfDay() : now()->endOfDay(),
            ],
            default => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
        };

        return [$rangeStart, $rangeEnd, $range, $rangeStart->format('Y-m-d'), $rangeEnd->format('Y-m-d')];
    }

    private function resolveFilters(Request $request): array
    {
        return [
            'region_id' => $request->filled('region_id') ? (int) $request->region_id : null,
            'state_id' => $request->filled('state_id') ? (int) $request->state_id : null,
            'district_id' => $request->filled('district_id') ? (int) $request->district_id : null,
            'user_id' => $request->filled('user_id') ? (int) $request->user_id : null,
        ];
    }

    private function baseQuery(array $filters): \Closure
    {
        return fn () => Sim::query()
            ->when($filters['region_id'] || $filters['state_id'] || $filters['district_id'], fn ($q) => $q->inJurisdiction($filters['region_id'], $filters['state_id'], $filters['district_id']))
            ->when($filters['user_id'], function ($q) use ($filters) {
                $q->where(function ($q2) use ($filters) {
                    foreach (self::HOLDER_COLUMNS as $column) {
                        $q2->orWhere($column, $filters['user_id']);
                    }
                });
            });
    }

    private function activationCount(\Closure $base, Carbon $from, Carbon $to): int
    {
        $simIds = $base()->pluck('id');

        return SimHistory::whereIn('sim_id', $simIds)
            ->where('to_status', SimStatus::ACTIVATED)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * Distinct-sim count per jurisdiction (not event count like the other
     * widgets) — jurisdiction isn't a DB column, it's resolved through the
     * live relation chain in PHP, so this groups the matching Sim models
     * directly rather than a SimHistory event stream.
     */
    private function activationsByJurisdiction(\Closure $base, Carbon $from, Carbon $to): Collection
    {
        $sims = $base()
            ->whereHas('history', fn ($q) => $q->where('to_status', SimStatus::ACTIVATED)->whereBetween('created_at', [$from, $to]))
            ->with(['regionalManager.regionAsManager', 'coordinator.districtAsCoordinator.state.region'])
            ->get();

        return $sims->groupBy(fn (Sim $sim) => $this->resolveJurisdictionLabel($sim) ?: 'Unassigned')
            ->map->count()
            ->sortDesc();
    }

    private function resolveJurisdictionLabel(?Sim $sim): ?string
    {
        if (!$sim) {
            return null;
        }

        $region = $sim->coordinator?->districtAsCoordinator?->state?->region
            ?? $sim->regionalManager?->regionAsManager;

        return $region?->name;
    }

    private function activationTrend(\Closure $base, Carbon $from, Carbon $to): Collection
    {
        $simIds = $base()->pluck('id');

        return SimHistory::whereIn('sim_id', $simIds)
            ->where('to_status', SimStatus::ACTIVATED)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    private function activationsByCategory(\Closure $base, Carbon $from, Carbon $to): Collection
    {
        return $this->groupedEventCounts($base, $from, $to, 'category');
    }

    private function activationsByProvider(\Closure $base, Carbon $from, Carbon $to): Collection
    {
        return $this->groupedEventCounts($base, $from, $to, 'provider');
    }

    private function groupedEventCounts(\Closure $base, Carbon $from, Carbon $to, string $simColumn): Collection
    {
        $simIds = $base()->pluck('id');

        return SimHistory::whereIn('sim_histories.sim_id', $simIds)
            ->where('sim_histories.to_status', SimStatus::ACTIVATED)
            ->whereBetween('sim_histories.created_at', [$from, $to])
            ->join('sims', 'sims.id', '=', 'sim_histories.sim_id')
            ->selectRaw("sims.{$simColumn} as label, COUNT(*) as count")
            ->groupBy("sims.{$simColumn}")
            ->orderByDesc('count')
            ->get();
    }

    private function activationEvents(\Closure $base, Carbon $from, Carbon $to)
    {
        $simIds = $base()->pluck('id');

        return SimHistory::whereIn('sim_id', $simIds)
            ->where('to_status', SimStatus::ACTIVATED)
            ->whereBetween('created_at', [$from, $to])
            ->with(['sim.user', 'sim.regionalManager.regionAsManager', 'sim.coordinator.districtAsCoordinator.state.region', 'actor'])
            ->orderByDesc('created_at');
    }
}
