<?php

namespace App\Http\Controllers\Bsm;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Report;
use App\Models\SenatorialDistrict;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Sim;
use App\Models\SimCustomer;
use App\Models\SimRequest;
use App\Models\State;
use App\Models\User;
use App\Services\ActivationBonusRetryService;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BsmDashboardController extends Controller
{
    public const DORMANT_DAYS = 7;

    public function dashboard(Request $request): View
    {
        $actor = $request->user();
        [$rangeStart, $rangeEnd, $rangeLabel, $rangeKey] = $this->resolveRange($request);

        $selectedCategory = $request->input('category');
        $selectedProvider = $request->input('provider');
        $activeTab = $request->input('tab', 'overview');

        $priceMap = $this->resolvePriceMap();

        // 1. Executive KPIs (Platform-wide or filtered by category/provider)
        $simsQuery = Sim::query()
            ->when($selectedCategory, fn ($q) => $q->where('category', $selectedCategory))
            ->when($selectedProvider, fn ($q) => $q->where('provider', $selectedProvider));

        $totalSims = (clone $simsQuery)->count();
        $totalActivated = (clone $simsQuery)->where('status', SimStatus::ACTIVATED)->count();
        $totalUnassigned = (clone $simsQuery)->where('status', SimStatus::UNASSIGNED)->count();
        $totalAllocated = (clone $simsQuery)->whereIn('status', [
            SimStatus::ASSIGNED_TO_RM,
            SimStatus::ASSIGNED_TO_COORDINATOR,
            SimStatus::ASSIGNED_TO_PARTNER,
            SimStatus::ASSIGNED_TO_BUSINESS,
            SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
        ])->count();

        $dormantCutoff = now()->subDays(self::DORMANT_DAYS);
        $totalDormant = (clone $simsQuery)
            ->where('status', '!=', SimStatus::ACTIVATED)
            ->where('status', '!=', SimStatus::UNASSIGNED)
            ->where('created_at', '<=', $dormantCutoff)
            ->count();

        $conversionRate = $totalSims > 0 ? round(($totalActivated / $totalSims) * 100, 1) : 0;
        $totalCustomers = SimCustomer::count();

        // Period Sales metrics
        $periodSalesQuery = (clone $simsQuery)->where('status', SimStatus::ACTIVATED);
        if ($rangeStart && $rangeEnd) {
            $periodSalesQuery->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('updated_at', [$rangeStart, $rangeEnd])
                  ->orWhereHas('history', fn ($h) => $h->where('to_status', SimStatus::ACTIVATED)->whereBetween('created_at', [$rangeStart, $rangeEnd]));
            });
        }
        $activatedSalesCount = (clone $periodSalesQuery)->count();
        $periodActivatedSims = (clone $periodSalesQuery)->get();
        $totalSalesVolume = $periodActivatedSims->sum(fn ($s) => (float) ($priceMap[$s->category] ?? 1000.00));

        // 2. Jurisdictions Breakdown
        $jurisdictionData = $this->buildJurisdictionData($priceMap, $rangeStart, $rangeEnd, $selectedCategory, $selectedProvider);

        // 3. SIM Lifecycle Management List
        $lifecycleStatus = $request->input('lifecycle_status', 'all');
        $simSearch = $request->input('sim_search');

        $lifecycleSimsQuery = Sim::with(['user', 'partner', 'coordinator', 'regionalManager', 'business', 'businessAgent', 'customer'])
            ->when($selectedCategory, fn ($q) => $q->where('category', $selectedCategory))
            ->when($selectedProvider, fn ($q) => $q->where('provider', $selectedProvider))
            ->when($lifecycleStatus && $lifecycleStatus !== 'all', fn ($q) => $q->where('status', $lifecycleStatus))
            ->when($simSearch, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('number', 'like', "%{$search}%")
                      ->orWhereHas('user', fn ($uq) => $uq->where('phone', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                      ->orWhereHas('partner', fn ($pq) => $pq->where('phone', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                      ->orWhereHas('coordinator', fn ($cq) => $cq->where('phone', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                      ->orWhereHas('customer', fn ($cuq) => $cuq->where('full_name', 'like', "%{$search}%")->orWhere('phone_number', 'like', "%{$search}%"));
                });
            })
            ->latest('updated_at');

        $lifecycleSims = $lifecycleSimsQuery->paginate(15, ['*'], 'sims_page')->withQueryString();

        // 4. Customer Handling Desk
        $customerSearch = $request->input('customer_search');
        $customersQuery = SimCustomer::with(['sim', 'partner'])
            ->when($customerSearch, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('full_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('sim', fn ($simQ) => $simQ->where('number', 'like', "%{$search}%"));
                });
            })
            ->latest();

        $customers = $customersQuery->paginate(15, ['*'], 'customers_page')->withQueryString();

        // 5. Sales Acceleration & Interventions Desk
        $dormantSims = Sim::with(['user', 'partner', 'coordinator', 'regionalManager'])
            ->where('status', '!=', SimStatus::ACTIVATED)
            ->where('status', '!=', SimStatus::UNASSIGNED)
            ->where('created_at', '<=', $dormantCutoff)
            ->latest('created_at')
            ->take(10)
            ->get();

        $pendingRequests = SimRequest::with(['user', 'upline'])
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        $failedBonusReports = Report::where('type', 'activation_bonus_data')
            ->whereIn('status', ['failed', 'retrying'])
            ->latest()
            ->take(10)
            ->get();

        // Candidate users for assignments and swaps
        $regionalManagers = User::where('role', 'regional_manager')->where('status', 'active')->orderBy('first_name')->get();
        $coordinators = User::where('role', 'coordinator')->where('status', 'active')->orderBy('first_name')->get();
        $partners = User::where('role', 'partner')->where('status', 'active')->orderBy('first_name')->get();
        $availableUnassignedSims = Sim::where('status', SimStatus::UNASSIGNED)->latest()->take(50)->get();

        return view('bsm.dashboard', [
            'actor'                   => $actor,
            'activeTab'               => $activeTab,
            'range'                   => $rangeKey,
            'rangeLabel'              => $rangeLabel,
            'selectedCategory'        => $selectedCategory,
            'selectedProvider'        => $selectedProvider,
            'priceMap'                => $priceMap,
            'categories'              => array_keys($priceMap),
            'providers'               => ['mtn', 'airtel', 'glo', '9mobile'],
            // KPIs
            'totalSims'               => $totalSims,
            'totalActivated'          => $totalActivated,
            'totalAllocated'          => $totalAllocated,
            'totalUnassigned'         => $totalUnassigned,
            'totalDormant'            => $totalDormant,
            'conversionRate'          => $conversionRate,
            'totalCustomers'          => $totalCustomers,
            'totalSalesVolume'        => $totalSalesVolume,
            'activatedSalesCount'     => $activatedSalesCount,
            // Jurisdiction Breakdown
            'regionsBreakdown'        => $jurisdictionData['regions'],
            'districtsBreakdown'      => $jurisdictionData['districts'],
            'jurisdictionTotals'      => $jurisdictionData['totals'],
            // SIM Lifecycle Management
            'lifecycleSims'           => $lifecycleSims,
            'lifecycleStatus'         => $lifecycleStatus,
            'simSearch'               => $simSearch,
            // Customer Handling
            'customers'               => $customers,
            'customerSearch'          => $customerSearch,
            // Acceleration & Interventions
            'dormantSims'             => $dormantSims,
            'pendingRequests'         => $pendingRequests,
            'failedBonusReports'      => $failedBonusReports,
            'regionalManagers'        => $regionalManagers,
            'coordinators'            => $coordinators,
            'partners'                => $partners,
            'availableUnassignedSims' => $availableUnassignedSims,
        ]);
    }

    public function exportJurisdictionReport(Request $request): Response
    {
        [$rangeStart, $rangeEnd, $rangeLabel, $rangeKey] = $this->resolveRange($request);
        $selectedCategory = $request->input('category');
        $selectedProvider = $request->input('provider');
        $priceMap = $this->resolvePriceMap();

        $jurisdictionData = $this->buildJurisdictionData($priceMap, $rangeStart, $rangeEnd, $selectedCategory, $selectedProvider);

        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        $csv .= "\"SIMPHIX BUSINESS SALES MANAGER - TERRITORY & SALES REPORT\"\n";
        $csv .= "\"Generated By\",\"" . str_replace('"', '""', $request->user()->name) . " (Business Sales Manager)\"\n";
        $csv .= "\"Reporting Period\",\"" . str_replace('"', '""', $rangeLabel) . "\"\n";
        $csv .= "\"Generated At\",\"" . now()->format('Y-m-d H:i:s') . "\"\n";
        $csv .= "\n";

        // Section 1: Overview Summary
        $t = $jurisdictionData['totals'];
        $csv .= "\"EXECUTIVE OVERVIEW\"\n";
        $csv .= "\"Total Allocated SIMs\",\"" . $t['allocated'] . "\"\n";
        $csv .= "\"Total Activated Sales\",\"" . $t['activated'] . "\"\n";
        $csv .= "\"Total Sales Volume (NGN)\",\"" . number_format($t['sales_volume'], 2) . "\"\n";
        $csv .= "\"Overall Conversion Rate\",\"" . $t['conversion_rate'] . "%\"\n";
        $csv .= "\"Pending Stock\",\"" . $t['pending'] . "\"\n";
        $csv .= "\"Dormant Stock (7+ days)\",\"" . $t['dormant'] . "\"\n";
        $csv .= "\n";

        // Section 2: Regional Performance
        $csv .= "\"REGIONAL SALES BREAKDOWN\"\n";
        $csv .= "\"Region Name\",\"Regional Manager\",\"Contact Phone\",\"Allocated SIMs\",\"Activated Sales\",\"Sales Volume (NGN)\",\"Conversion Rate (%)\",\"Pending Stock\",\"Dormant Stock\"\n";
        foreach ($jurisdictionData['regions'] as $reg) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
                $reg['name'],
                $reg['lead_name'],
                $reg['lead_phone'],
                $reg['allocated'],
                $reg['activated'],
                number_format($reg['sales_volume'], 2),
                $reg['conversion_rate'] . '%',
                $reg['pending'],
                $reg['dormant'],
            ])) . "\n";
        }
        $csv .= "\n";

        // Section 3: Senatorial District Performance
        $csv .= "\"SENATORIAL DISTRICTS BREAKDOWN\"\n";
        $csv .= "\"District Name\",\"State\",\"Region\",\"Coordinator\",\"Contact Phone\",\"Allocated SIMs\",\"Activated Sales\",\"Sales Volume (NGN)\",\"Conversion Rate (%)\",\"Pending Stock\",\"Dormant Stock\"\n";
        foreach ($jurisdictionData['districts'] as $dist) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
                $dist['district'],
                $dist['state'],
                $dist['region'],
                $dist['coordinator_name'],
                $dist['coordinator_phone'],
                $dist['allocated'],
                $dist['activated'],
                number_format($dist['sales_volume'], 2),
                $dist['conversion_rate'] . '%',
                $dist['pending'],
                $dist['dormant'],
            ])) . "\n";
        }

        $filename = 'bsm-territory-sales-report-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function assignSim(Request $request, SimAssignmentService $assignmentService): RedirectResponse
    {
        $request->validate([
            'sim_id'    => 'required|exists:sims,id',
            'target_id' => 'required|exists:users,id',
        ]);

        $sim = Sim::findOrFail($request->sim_id);
        $target = User::findOrFail($request->target_id);

        try {
            $assignmentService->adminAssign($sim, $target, $request->user());
            return back()->with('success', "SIM {$sim->number} successfully assigned to {$target->name} ({$target->role}).");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reassignSim(Request $request, SimAssignmentService $assignmentService): RedirectResponse
    {
        $request->validate([
            'sim_id'     => 'required|exists:sims,id',
            'new_holder_id' => 'required|exists:users,id',
        ]);

        $sim = Sim::findOrFail($request->sim_id);
        $newHolder = User::findOrFail($request->new_holder_id);

        try {
            $assignmentService->swapHolder($sim, $newHolder, $request->user());
            return back()->with('success', "SIM {$sim->number} successfully swapped to {$newHolder->name} ({$newHolder->role}).");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function revertSim(Request $request, SimAssignmentService $assignmentService): RedirectResponse
    {
        $request->validate([
            'sim_id' => 'required|exists:sims,id',
            'note'   => 'nullable|string|max:255',
        ]);

        $sim = Sim::findOrFail($request->sim_id);

        try {
            $assignmentService->revertToHolder(
                $sim,
                $request->user(),
                $request->note ?? 'Reverted to holder by Business Sales Manager to unblock distribution.'
            );
            return back()->with('success', "SIM {$sim->number} successfully reverted to holder.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateSimStatus(Request $request, Sim $sim, SimAssignmentService $assignmentService): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:suspend,deactivate,activate',
        ]);

        $actor = $request->user();

        try {
            match ($request->action) {
                'suspend'    => $assignmentService->suspend($sim, $actor),
                'deactivate' => $assignmentService->deactivate($sim, $actor),
                'activate'   => $assignmentService->activate($sim, $actor, false, 'Reactivated by Business Sales Manager.'),
            };

            return back()->with('success', "SIM {$sim->number} status updated to " . strtoupper($request->action) . '.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function quickActivate(Request $request, SimAssignmentService $assignmentService): RedirectResponse
    {
        $request->validate([
            'sim_id'       => 'required|exists:sims,id',
            'full_name'    => 'required|string|max:150',
            'phone_number' => 'required|string|max:25',
            'email'        => 'nullable|email|max:150',
            'address'      => 'nullable|string|max:255',
        ]);

        $sim = Sim::findOrFail($request->sim_id);

        if ($sim->status === SimStatus::ACTIVATED) {
            return back()->with('error', "SIM {$sim->number} is already activated.");
        }

        $actor = $request->user();
        $partnerId = $sim->partner_id ?? $sim->business_agent_id ?? $actor->id;

        try {
            DB::transaction(function () use ($sim, $request, $partnerId, $actor, $assignmentService) {
                SimCustomer::create([
                    'sim_id'       => $sim->id,
                    'partner_id'   => $partnerId,
                    'full_name'    => $request->full_name,
                    'email'        => $request->email,
                    'phone_number' => $request->phone_number,
                    'address'      => $request->address,
                ]);

                if (!$sim->user_id) {
                    $sim->update(['user_id' => $partnerId]);
                }

                $assignmentService->activate($sim, $actor, true, 'Expedited customer onboarding & activation via BSM Desk.');
            });

            return back()->with('success', "SIM {$sim->number} successfully activated for customer {$request->full_name}!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveRequest(Request $request, SimRequest $simRequest, SimAssignmentService $assignmentService): RedirectResponse
    {
        if ($simRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $actor = $request->user();

        try {
            DB::transaction(function () use ($simRequest, $actor, $assignmentService) {
                $lockedRequest = SimRequest::where('id', $simRequest->id)->lockForUpdate()->first();
                if (!$lockedRequest || $lockedRequest->status !== 'pending') {
                    throw new \Exception('This request has already been processed.');
                }

                $requester = User::find($lockedRequest->user_id);
                if (!$requester) {
                    throw new \Exception('Requester user not found.');
                }

                // If specific sim_id is associated
                if ($lockedRequest->sim_id) {
                    $sim = Sim::find($lockedRequest->sim_id);
                    if ($sim && $sim->status === SimStatus::UNASSIGNED) {
                        $assignmentService->adminAssign($sim, $requester, $actor);
                    }
                } else {
                    // Purchase quantity: take available unassigned SIMs matching category/provider
                    $available = Sim::where('status', SimStatus::UNASSIGNED)
                        ->when($lockedRequest->category, fn ($q) => $q->where('category', $lockedRequest->category))
                        ->when($lockedRequest->provider, fn ($q) => $q->where('provider', $lockedRequest->provider))
                        ->take($lockedRequest->quantity)
                        ->get();

                    foreach ($available as $sim) {
                        $assignmentService->adminAssign($sim, $requester, $actor);
                    }
                }

                $lockedRequest->update([
                    'status'      => 'approved',
                    'admin_notes' => 'Approved by Business Sales Manager to accelerate inventory dispatch.',
                ]);
            });

            return back()->with('success', "Stock request #{$simRequest->id} approved and SIMs dispatched.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function retryFailedActivation(Report $report, ActivationBonusRetryService $retry): RedirectResponse
    {
        [$ok, $message] = $retry->retry($report);

        return back()->with($ok ? 'success' : 'error', $message);
    }

    private function resolveRange(Request $request): array
    {
        $range = $request->input('range', '7days');

        return match ($range) {
            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
                'Today (' . now()->format('M d, Y') . ')',
                'today',
            ],
            '30days' => [
                now()->subDays(29)->startOfDay(),
                now()->endOfDay(),
                'Last 30 Days (' . now()->subDays(29)->format('M d') . ' - ' . now()->format('M d, Y') . ')',
                '30days',
            ],
            'this_month' => [
                now()->startOfMonth(),
                now()->endOfMonth(),
                'This Month (' . now()->format('F Y') . ')',
                'this_month',
            ],
            'all' => [
                null,
                null,
                'All Time',
                'all',
            ],
            'custom' => [
                $request->filled('period_start') ? Carbon::parse($request->period_start)->startOfDay() : now()->subDays(6)->startOfDay(),
                $request->filled('period_end') ? Carbon::parse($request->period_end)->endOfDay() : now()->endOfDay(),
                'Custom (' . ($request->period_start ?? now()->subDays(6)->toDateString()) . ' to ' . ($request->period_end ?? now()->toDateString()) . ')',
                'custom',
            ],
            default => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                'Last 7 Days (' . now()->subDays(6)->format('M d') . ' - ' . now()->format('M d, Y') . ')',
                '7days',
            ],
        };
    }

    private function resolvePriceMap(): array
    {
        $simService = Service::where('name', 'simcard')->first();
        $prices = [
            'POS SIM'    => 1000.00,
            'CAMERA SIM' => 1500.00,
            'CCTV'       => 2000.00,
            'ROUTER SIM' => 2500.00,
            'GPS SIM'    => 3000.00,
        ];

        if ($simService) {
            $fields = ServiceField::where('service_id', $simService->id)->with('prices')->get();
            foreach ($fields as $field) {
                $base = (float) $field->base_price;
                $personalPrice = $field->prices->where('user_type', 'personal')->whereNull('user_id')->first();
                $prices[$field->field_name] = $personalPrice ? (float) $personalPrice->price : $base;
            }
        }

        return $prices;
    }

    private function buildJurisdictionData(array $priceMap, ?Carbon $rangeStart, ?Carbon $rangeEnd, ?string $selectedCategory, ?string $selectedProvider): array
    {
        $dormantCutoff = now()->subDays(self::DORMANT_DAYS);

        // 1. Regional Breakdown
        $regions = Region::with(['regionalManager', 'states.senatorialDistricts.coordinator'])->orderBy('name')->get();
        $regionsList = [];

        $totalAllocated = 0;
        $totalActivated = 0;
        $totalSalesVolume = 0.00;
        $totalPending = 0;
        $totalDormant = 0;

        foreach ($regions as $region) {
            $rmId = $region->regional_manager_id;
            $distCoordIds = $region->states->flatMap->senatorialDistricts->pluck('coordinator_id')->filter()->all();

            $regSimsQuery = Sim::query()
                ->where(function ($q) use ($rmId, $distCoordIds) {
                    if ($rmId) {
                        $q->where('regional_manager_id', $rmId);
                    }
                    if (!empty($distCoordIds)) {
                        $q->orWhereIn('coordinator_id', $distCoordIds);
                    }
                })
                ->when($selectedCategory, fn ($q) => $q->where('category', $selectedCategory))
                ->when($selectedProvider, fn ($q) => $q->where('provider', $selectedProvider));

            $allocated = (clone $regSimsQuery)->count();

            $activatedQuery = (clone $regSimsQuery)->where('status', SimStatus::ACTIVATED);
            if ($rangeStart && $rangeEnd) {
                $activatedQuery->where(function ($q) use ($rangeStart, $rangeEnd) {
                    $q->whereBetween('updated_at', [$rangeStart, $rangeEnd])
                      ->orWhereHas('history', fn ($h) => $h->where('to_status', SimStatus::ACTIVATED)->whereBetween('created_at', [$rangeStart, $rangeEnd]));
                });
            }

            $activated = (clone $activatedQuery)->count();
            $actSims = (clone $activatedQuery)->get();
            $salesVolume = $actSims->sum(fn ($s) => (float) ($priceMap[$s->category] ?? 1000.00));
            $conversionRate = $allocated > 0 ? round(($activated / $allocated) * 100, 1) : 0;
            $pending = max(0, $allocated - $activated);
            $dormant = (clone $regSimsQuery)
                ->where('status', '!=', SimStatus::ACTIVATED)
                ->where('created_at', '<=', $dormantCutoff)
                ->count();

            $regionsList[] = [
                'name'            => $region->name,
                'lead_name'       => $region->regionalManager?->name ?? 'Unassigned',
                'lead_phone'      => $region->regionalManager?->phone ?? '—',
                'allocated'       => $allocated,
                'activated'       => $activated,
                'sales_volume'    => $salesVolume,
                'conversion_rate' => $conversionRate,
                'pending'         => $pending,
                'dormant'         => $dormant,
            ];

            $totalAllocated += $allocated;
            $totalActivated += $activated;
            $totalSalesVolume += $salesVolume;
            $totalPending += $pending;
            $totalDormant += $dormant;
        }

        // 2. Senatorial District Breakdown
        $districts = SenatorialDistrict::with(['coordinator', 'state.region'])->orderBy('name')->get();
        $districtsList = [];

        foreach ($districts as $district) {
            $coordId = $district->coordinator_id;
            $distSimsQuery = Sim::query()
                ->when($coordId, fn ($q) => $q->where('coordinator_id', $coordId), fn ($q) => $q->whereRaw('1 = 0'))
                ->when($selectedCategory, fn ($q) => $q->where('category', $selectedCategory))
                ->when($selectedProvider, fn ($q) => $q->where('provider', $selectedProvider));

            $allocated = (clone $distSimsQuery)->count();

            $activatedQuery = (clone $distSimsQuery)->where('status', SimStatus::ACTIVATED);
            if ($rangeStart && $rangeEnd) {
                $activatedQuery->where(function ($q) use ($rangeStart, $rangeEnd) {
                    $q->whereBetween('updated_at', [$rangeStart, $rangeEnd])
                      ->orWhereHas('history', fn ($h) => $h->where('to_status', SimStatus::ACTIVATED)->whereBetween('created_at', [$rangeStart, $rangeEnd]));
                });
            }

            $activated = (clone $activatedQuery)->count();
            $actSims = (clone $activatedQuery)->get();
            $salesVolume = $actSims->sum(fn ($s) => (float) ($priceMap[$s->category] ?? 1000.00));
            $conversionRate = $allocated > 0 ? round(($activated / $allocated) * 100, 1) : 0;
            $pending = max(0, $allocated - $activated);
            $dormant = (clone $distSimsQuery)
                ->where('status', '!=', SimStatus::ACTIVATED)
                ->where('created_at', '<=', $dormantCutoff)
                ->count();

            $districtsList[] = [
                'district'          => $district->name,
                'state'             => $district->state?->name ?? '—',
                'region'            => $district->state?->region?->name ?? '—',
                'coordinator_name'  => $district->coordinator?->name ?? 'Unassigned',
                'coordinator_phone' => $district->coordinator?->phone ?? '—',
                'allocated'         => $allocated,
                'activated'         => $activated,
                'sales_volume'      => $salesVolume,
                'conversion_rate'   => $conversionRate,
                'pending'           => $pending,
                'dormant'           => $dormant,
            ];
        }

        $overallConversionRate = $totalAllocated > 0 ? round(($totalActivated / $totalAllocated) * 100, 1) : 0;

        return [
            'regions'   => $regionsList,
            'districts' => $districtsList,
            'totals'    => [
                'allocated'       => $totalAllocated,
                'activated'       => $totalActivated,
                'sales_volume'    => $totalSalesVolume,
                'conversion_rate' => $overallConversionRate,
                'pending'         => $totalPending,
                'dormant'         => $totalDormant,
            ],
        ];
    }
}
