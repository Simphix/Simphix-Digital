<?php

namespace App\Http\Controllers;

use App\Models\CommissionEarning;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\State;
use App\Models\User;
use App\Support\EnterprisePricing;
use App\Support\SimAccess;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SalesPerformanceController extends Controller
{
    /**
     * A SIM held N+ days without being activated is flagged as dormant.
     */
    public const DORMANT_DAYS = 7;

    /**
     * Per-role FK on `sims` that scopes "SIMs this role currently holds or
     * has distributed downline".
     */
    private const HOLDER_FK = [
        'regional_manager' => 'regional_manager_id',
        'coordinator'      => 'coordinator_id',
        'partner'          => 'partner_id',
        'business'         => 'business_id',
        'business_agent'   => 'business_agent_id',
    ];

    private const ASSIGNED_STATUS = [
        'regional_manager' => SimStatus::ASSIGNED_TO_RM,
        'coordinator'      => SimStatus::ASSIGNED_TO_COORDINATOR,
        'partner'          => SimStatus::ASSIGNED_TO_PARTNER,
        'business'         => SimStatus::ASSIGNED_TO_BUSINESS,
        'business_agent'   => SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
    ];

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }
        if (!SimAccess::canBrowseCatalog($user)) {
            abort(403, 'Sales Performance is only available to catalog users.');
        }

        $data = $this->buildReportData($user, $request);

        return view('sales-performance.index', $data);
    }

    public function export(Request $request): Response
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        if (!SimAccess::canBrowseCatalog($user)) {
            abort(403, 'Sales Performance is only available to catalog users.');
        }

        $data = $this->buildReportData($user, $request);

        $format = strtolower($request->input('format', 'csv'));
        if ($format === 'pdf') {
            $pdfContent = $this->generatePdf($data, $user);
            $filename = 'sales-performance-report-' . str_replace('_', '-', $user->role) . '-' . now()->format('Y-m-d') . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Microsoft Excel
        $csv .= "\"SIMPHIX SALES PERFORMANCE & TERRITORY REPORT\"\n";
        $csv .= "\"Generated For\",\"" . str_replace('"', '""', $user->name) . " (" . ucfirst(str_replace('_', ' ', $user->role)) . ")\"\n";
        $csv .= "\"Territory / Scope\",\"" . str_replace('"', '""', $user->jurisdictionLabel() ?? 'All Managed Territories') . "\"\n";
        $csv .= "\"Reporting Period\",\"" . str_replace('"', '""', $data['rangeLabel']) . "\"\n";
        $csv .= "\"Generated At\",\"" . now()->format('Y-m-d H:i:s') . "\"\n";
        $csv .= "\n";
        $csv .= "\"OVERVIEW SUMMARY\"\n";
        $csv .= "\"Total Allocated SIMs\",\"" . $data['totalAllocatedSims'] . "\"\n";
        $csv .= "\"Total Activated Sales\",\"" . $data['activatedSalesCount'] . "\"\n";
        $csv .= "\"Total Sales Volume (NGN)\",\"" . number_format($data['totalSalesVolume'], 2) . "\"\n";
        $csv .= "\"Direct Sales\",\"" . $data['directSalesCount'] . " SIMs (NGN " . number_format($data['directSalesVolume'], 2) . ")\"\n";
        $csv .= "\"Downline Sales\",\"" . $data['downlineSalesCount'] . " SIMs (NGN " . number_format($data['downlineSalesVolume'], 2) . ")\"\n";
        $csv .= "\"Overall Conversion Rate\",\"" . $data['overallConversionRate'] . "%\"\n";
        $csv .= "\"Commissions in Period (NGN)\",\"" . number_format($data['commissionThisPeriod'], 2) . "\"\n";
        $csv .= "\"Pending Stock\",\"" . $data['pendingCount'] . "\"\n";
        $csv .= "\"Dormant Stock (7+ days)\",\"" . $data['dormantCount'] . "\"\n";
        $csv .= "\n";
        $csv .= "\"TERRITORY & DOWNLINE BREAKDOWN\"\n";
        $csv .= "\"Territory\",\"Level / Type\",\"Assigned Lead\",\"Contact Phone\",\"Allocated SIMs\",\"Activated Sales\",\"Sales Volume (NGN)\",\"Conversion Rate (%)\",\"Pending Stock\",\"Dormant Stock\"\n";

        foreach ($data['jurisdictionRows'] as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
                $row['jurisdiction'],
                $row['type'],
                $row['lead_name'],
                $row['lead_phone'],
                $row['allocated'],
                $row['activated'],
                number_format($row['sales_volume'], 2),
                $row['conversion_rate'] . '%',
                $row['pending'],
                $row['dormant'],
            ])) . "\n";
        }

        $t = $data['jurisdictionTotals'];
        $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
            'TOTAL',
            '--',
            '--',
            '--',
            $t['allocated'],
            $t['activated'],
            number_format($t['sales_volume'], 2),
            $t['conversion_rate'] . '%',
            $t['pending'],
            $t['dormant'],
        ])) . "\n";
        $csv .= "\n";
        $csv .= "\"ITEMIZED ACTIVATED SIMS (SALES RECORDS)\"\n";
        $csv .= "\"SIM Number\",\"Category\",\"Provider\",\"Status\",\"Customer Name\",\"Customer Phone\",\"Activated Date\",\"Activated By\",\"Seller Role\",\"Territory\",\"Sales Amount (NGN)\"\n";

        foreach ($data['activatedSimsList'] as $sim) {
            $customerName = $sim->customer?->full_name
                ?? ($sim->user && $sim->user_id !== $sim->partner_id ? trim($sim->user->first_name . ' ' . $sim->user->last_name) : '—');
            $customerPhone = $sim->customer?->phone_number
                ?? ($sim->user && $sim->user_id !== $sim->partner_id ? $sim->user->phone : '—');
            $activatedBy = $sim->user ? trim($sim->user->first_name . ' ' . $sim->user->last_name) : 'Direct';
            $sellerRole = $sim->user ? ucfirst(str_replace('_', ' ', $sim->user->role)) : ucfirst(str_replace('_', ' ', $user->role));
            $jurisdiction = $this->resolveSimJurisdictionLabel($sim);
            $price = $this->getSimPrice($sim, $data['priceMap']);

            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
                $sim->number,
                $sim->category,
                strtoupper($sim->provider),
                ucfirst($sim->status),
                $customerName,
                $customerPhone,
                $sim->customer?->created_at?->toDateTimeString() ?? $sim->updated_at?->toDateTimeString(),
                $activatedBy,
                $sellerRole,
                $jurisdiction,
                number_format($price, 2),
            ])) . "\n";
        }

        $filename = 'sales-performance-report-' . str_replace('_', '-', $user->role) . '-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function generatePdf(array $data, User $user): string
    {
        $data['controller'] = $this;

        $html = view('sales-performance.pdf', $data)->render();

        $pdf = new class('L', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF {
            public function Footer()
            {
                $this->SetY(-10);
                $this->SetFont('dejavusans', '', 7.2);
                $this->SetTextColor(148, 163, 184); // slate-400
                $this->Cell(190, 6, 'SimPhix Sales Performance Report · Confidential & Proprietary · Generated ' . date('Y-m-d H:i'), 0, 0, 'L');
                $this->Cell(0, 6, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };

        $pdf->SetCreator('SimPhix');
        $pdf->SetAuthor('SimPhix Platform');
        $pdf->SetTitle('Sales Performance & Territory Report - ' . $user->name);
        $pdf->SetSubject('Sales Performance Report');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('sales-performance-report.pdf', 'S');
    }

    public function buildReportData(User $user, Request $request): array
    {
        $fk = self::HOLDER_FK[$user->role] ?? 'user_id';
        $isPersonal = in_array($user->role, ['personal', 'agent', 'business_agent'], true);
        $assignedStatus = self::ASSIGNED_STATUS[$user->role] ?? SimStatus::ASSIGNED_TO_PARTNER;

        [$rangeStart, $rangeEnd, $rangeLabel] = $this->resolveRange($request);
        $selectedCategory = $request->input('category');
        $selectedProvider = $request->input('provider');

        $priceMap = $this->resolvePriceMap($user);

        // All SIMs within scope of this catalog user
        $baseSimsQuery = Sim::where($fk, $user->id)
            ->when($selectedCategory, fn ($q) => $q->where('category', $selectedCategory))
            ->when($selectedProvider, fn ($q) => $q->where('provider', $selectedProvider));

        $totalAllocatedSims = (clone $baseSimsQuery)->count();

        // All-time activated SIMs count
        $allTimeActivatedCount = (clone $baseSimsQuery)->where('status', SimStatus::ACTIVATED)->count();
        $overallConversionRate = $totalAllocatedSims > 0 ? round(($allTimeActivatedCount / $totalAllocatedSims) * 100, 1) : 0;

        // Period-filtered activated SIMs (Sales)
        $periodActivatedQuery = (clone $baseSimsQuery)->where('status', SimStatus::ACTIVATED);
        if ($rangeStart && $rangeEnd) {
            $periodActivatedQuery->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('updated_at', [$rangeStart, $rangeEnd])
                  ->orWhereHas('histories', fn ($h) => $h->where('to_status', SimStatus::ACTIVATED)->whereBetween('created_at', [$rangeStart, $rangeEnd]));
            });
        }

        $activatedSimsInPeriod = (clone $periodActivatedQuery)
            ->with(['customer', 'user', 'coordinator.districtAsCoordinator.state.region', 'regionalManager.regionAsManager', 'partner', 'businessAgent'])
            ->latest('updated_at')
            ->get();

        $activatedSalesCount = $activatedSimsInPeriod->count();

        // Revenue calculations
        $totalSalesVolume = 0.00;
        $directSalesCount = 0;
        $directSalesVolume = 0.00;
        $downlineSalesCount = 0;
        $downlineSalesVolume = 0.00;

        foreach ($activatedSimsInPeriod as $sim) {
            $price = $this->getSimPrice($sim, $priceMap);
            $totalSalesVolume += $price;

            $isDirect = (int) $sim->user_id === (int) $user->id
                || ($user->role === 'partner' && (int) $sim->partner_id === (int) $user->id && (int) $sim->user_id === (int) $user->id)
                || ($user->role === 'business_agent' && (int) $sim->business_agent_id === (int) $user->id);

            if ($isDirect) {
                $directSalesCount++;
                $directSalesVolume += $price;
            } else {
                $downlineSalesCount++;
                $downlineSalesVolume += $price;
            }
        }

        // Category breakdown
        $categoryBreakdown = $activatedSimsInPeriod->groupBy('category')->map(function ($items, $catName) use ($priceMap, $baseSimsQuery) {
            $count = $items->count();
            $volume = $items->sum(fn ($s) => $this->getSimPrice($s, $priceMap));
            $allocatedForCat = (clone $baseSimsQuery)->where('category', $catName)->count();

            return [
                'category'         => $catName,
                'allocated'        => $allocatedForCat,
                'activated'        => $count,
                'volume'           => $volume,
                'formatted_volume' => number_format($volume, 2),
                'conversion_rate'  => $allocatedForCat > 0 ? round(($count / $allocatedForCat) * 100, 1) : 0,
            ];
        })->values();

        // Provider breakdown
        $providerBreakdown = $activatedSimsInPeriod->groupBy('provider')->map(function ($items, $provName) use ($baseSimsQuery) {
            $count = $items->count();
            $allocatedForProv = (clone $baseSimsQuery)->where('provider', $provName)->count();

            return [
                'provider'  => strtoupper($provName),
                'allocated' => $allocatedForProv,
                'activated' => $count,
            ];
        })->values();

        // Unactivated and dormant SIMs
        $unactivatedQuery = (clone $baseSimsQuery)->where('status', $assignedStatus)->orderBy('created_at');
        if (!$isPersonal) {
            $unactivatedQuery->with('user');
        }
        $unactivatedSimsCollection = (clone $unactivatedQuery)->get();
        $dormantCount = $unactivatedSimsCollection->filter(fn ($s) => $s->daysHeld() >= self::DORMANT_DAYS)->count();
        $pendingCount = $unactivatedSimsCollection->count();

        $unactivatedSimsList = $unactivatedSimsCollection->take(20)->map(function (Sim $sim) use ($isPersonal) {
            $daysHeld = $sim->daysHeld();

            return [
                'sim'        => $sim,
                'holder'     => $isPersonal ? null : $sim->user,
                'days_held'  => $daysHeld,
                'is_dormant' => $daysHeld >= self::DORMANT_DAYS,
            ];
        });

        // Commission query for user in period
        $commissionQuery = CommissionEarning::where('user_id', $user->id);
        if ($rangeStart && $rangeEnd) {
            $commissionQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        }
        $commissionThisPeriod = (float) $commissionQuery->sum('amount');

        // Commission weekly trend (last 6 weeks)
        $weeklyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $amount = (float) CommissionEarning::where('user_id', $user->id)
                ->where('period_week', $weekStart->toDateString())
                ->sum('amount');

            $weeklyTrend[] = [
                'label'  => $weekStart->format('M d'),
                'amount' => $amount,
            ];
        }

        // Commission this week vs last week
        $thisWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        $commissionThisWeek = (float) CommissionEarning::where('user_id', $user->id)->where('period_week', $thisWeekStart)->sum('amount');
        $commissionLastWeek = (float) CommissionEarning::where('user_id', $user->id)->where('period_week', $lastWeekStart)->sum('amount');
        $commissionTrendPct = $commissionLastWeek > 0
            ? round((($commissionThisWeek - $commissionLastWeek) / $commissionLastWeek) * 100)
            : ($commissionThisWeek > 0 ? 100 : 0);

        // Jurisdiction breakdown
        $jurisdictionData = $this->buildJurisdictionBreakdown($user, $baseSimsQuery, $priceMap, $rangeStart, $rangeEnd);

        return [
            'user'                  => $user,
            'isPersonal'            => $isPersonal,
            'range'                 => $request->input('range', '7days'),
            'rangeLabel'            => $rangeLabel,
            'periodStart'           => $rangeStart?->format('Y-m-d'),
            'periodEnd'             => $rangeEnd?->format('Y-m-d'),
            'selectedCategory'      => $selectedCategory,
            'selectedProvider'      => $selectedProvider,
            'totalAllocatedSims'    => $totalAllocatedSims,
            'totalSims'             => $totalAllocatedSims,
            'activatedSims'         => $allTimeActivatedCount,
            'conversionRate'        => $overallConversionRate,
            'allTimeActivatedCount' => $allTimeActivatedCount,
            'overallConversionRate' => $overallConversionRate,
            'activatedSalesCount'   => $activatedSalesCount,
            'totalSalesVolume'      => $totalSalesVolume,
            'directSalesCount'      => $directSalesCount,
            'directSalesVolume'     => $directSalesVolume,
            'downlineSalesCount'    => $downlineSalesCount,
            'downlineSalesVolume'   => $downlineSalesVolume,
            'categoryBreakdown'     => $categoryBreakdown,
            'providerBreakdown'     => $providerBreakdown,
            'unactivatedSims'       => $unactivatedSimsList,
            'pendingCount'          => $pendingCount,
            'dormantCount'          => $dormantCount,
            'commissionThisPeriod'  => $commissionThisPeriod,
            'commissionThisWeek'    => $commissionThisWeek,
            'commissionLastWeek'    => $commissionLastWeek,
            'commissionTrendPct'    => $commissionTrendPct,
            'weeklyTrend'           => $weeklyTrend,
            'jurisdictionRows'      => $jurisdictionData['rows'],
            'jurisdictionTotals'    => $jurisdictionData['totals'],
            'activatedSimsList'     => $activatedSimsInPeriod,
            'priceMap'              => $priceMap,
            'categories'            => array_keys($priceMap),
            'providers'             => ['mtn', 'airtel', 'glo', '9mobile'],
        ];
    }

    private function resolveRange(Request $request): array
    {
        $range = $request->input('range', '7days');

        return match ($range) {
            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
                'Today (' . now()->format('M d, Y') . ')',
            ],
            '30days' => [
                now()->subDays(29)->startOfDay(),
                now()->endOfDay(),
                'Last 30 Days (' . now()->subDays(29)->format('M d') . ' - ' . now()->format('M d, Y') . ')',
            ],
            'this_month' => [
                now()->startOfMonth(),
                now()->endOfMonth(),
                'This Month (' . now()->format('F Y') . ')',
            ],
            'all' => [
                null,
                null,
                'All Time',
            ],
            'custom' => [
                $request->filled('period_start') ? Carbon::parse($request->period_start)->startOfDay() : now()->subDays(6)->startOfDay(),
                $request->filled('period_end') ? Carbon::parse($request->period_end)->endOfDay() : now()->endOfDay(),
                'Custom (' . ($request->period_start ?? now()->subDays(6)->toDateString()) . ' to ' . ($request->period_end ?? now()->toDateString()) . ')',
            ],
            default => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                'Last 7 Days (' . now()->subDays(6)->format('M d') . ' - ' . now()->format('M d, Y') . ')',
            ],
        };
    }

    private function resolvePriceMap(?User $user = null): array
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

        if ($user && in_array($user->role, ['business', 'business_agent'], true)) {
            $businessId = EnterprisePricing::businessIdFor($user);
            foreach ($prices as $category => $price) {
                $epPrice = EnterprisePricing::customerPrice($businessId, $category);
                if ($epPrice !== null) {
                    $prices[$category] = (float) $epPrice;
                }
            }
        }

        return $prices;
    }

    public function getSimPrice(Sim $sim, array $priceMap): float
    {
        return (float) ($priceMap[$sim->category] ?? 1000.00);
    }

    private function buildJurisdictionBreakdown(User $user, $baseSimsQuery, array $priceMap, ?Carbon $rangeStart, ?Carbon $rangeEnd): array
    {
        $rows = [];
        $allSims = (clone $baseSimsQuery)
            ->with(['coordinator.districtAsCoordinator.state.region', 'regionalManager.regionAsManager', 'partner', 'businessAgent'])
            ->get();

        $evalSlice = function ($simsSlice, string $jurisdiction, string $type, string $leadName, string $leadPhone, string $leadRole) use ($priceMap, $rangeStart, $rangeEnd) {
            $allocated = $simsSlice->count();
            $activatedSlice = $simsSlice->where('status', SimStatus::ACTIVATED);
            if ($rangeStart && $rangeEnd) {
                $activatedSlice = $activatedSlice->filter(function ($s) use ($rangeStart, $rangeEnd) {
                    return $s->updated_at >= $rangeStart && $s->updated_at <= $rangeEnd;
                });
            }
            $activated = $activatedSlice->count();
            $salesVolume = $activatedSlice->sum(fn ($s) => $this->getSimPrice($s, $priceMap));
            $conversionRate = $allocated > 0 ? round(($activated / $allocated) * 100, 1) : 0;
            $pending = $allocated - $activated;
            $dormant = $simsSlice->filter(fn ($s) => $s->status !== SimStatus::ACTIVATED && $s->daysHeld() >= self::DORMANT_DAYS)->count();

            return [
                'jurisdiction'    => $jurisdiction,
                'type'            => $type,
                'lead_name'       => $leadName,
                'lead_phone'      => $leadPhone,
                'lead_role'       => $leadRole,
                'allocated'       => $allocated,
                'activated'       => $activated,
                'sales_volume'    => $salesVolume,
                'conversion_rate' => $conversionRate,
                'pending'         => $pending,
                'dormant'         => $dormant,
            ];
        };

        if ($user->role === 'regional_manager') {
            $region = $user->regionAsManager;
            if ($region) {
                $states = $region->states()->with('senatorialDistricts.coordinator')->get();
                $handledSimIds = [];

                foreach ($states as $state) {
                    foreach ($state->senatorialDistricts as $district) {
                        $coord = $district->coordinator;
                        $districtSims = $allSims->filter(function ($s) use ($district, $coord) {
                            return ($coord && (int) $s->coordinator_id === (int) $coord->id)
                                || ($s->coordinator?->districtAsCoordinator?->id === $district->id);
                        });
                        $handledSimIds = array_merge($handledSimIds, $districtSims->pluck('id')->all());

                        $leadName = $coord ? trim($coord->first_name . ' ' . $coord->last_name) : 'Unassigned';
                        $leadPhone = $coord?->phone ?? '—';

                        $rows[] = $evalSlice(
                            $districtSims,
                            "{$district->name} · {$state->name}",
                            'Senatorial District',
                            $leadName,
                            $leadPhone,
                            'Coordinator'
                        );
                    }
                }

                $directSims = $allSims->whereNotIn('id', $handledSimIds);
                if ($directSims->count() > 0 || count($rows) === 0) {
                    $rows[] = $evalSlice(
                        $directSims,
                        "{$region->name} (Regional HQ / Direct)",
                        'Regional HQ',
                        trim($user->first_name . ' ' . $user->last_name),
                        $user->phone ?? '—',
                        'Regional Manager'
                    );
                }
            } else {
                $coordinators = User::where('referred_by', $user->id)->where('role', 'coordinator')->get();
                foreach ($coordinators as $coord) {
                    $coordSims = $allSims->where('coordinator_id', $coord->id);
                    $label = $coord->jurisdictionLabel() ?: "{$coord->first_name} {$coord->last_name} Territory";
                    $rows[] = $evalSlice($coordSims, $label, 'Coordinator Downline', trim($coord->first_name . ' ' . $coord->last_name), $coord->phone ?? '—', 'Coordinator');
                }
                $unassigned = $allSims->whereNull('coordinator_id');
                if ($unassigned->count() > 0 || count($rows) === 0) {
                    $rows[] = $evalSlice($unassigned, 'Regional Office / Direct', 'Regional HQ', trim($user->first_name . ' ' . $user->last_name), $user->phone ?? '—', 'Regional Manager');
                }
            }
        } elseif ($user->role === 'coordinator') {
            $district = $user->districtAsCoordinator;
            $districtName = $district ? "{$district->name} · {$district->state?->name}" : 'Assigned District';

            $partners = User::where('referred_by', $user->id)->where('role', 'partner')->get();
            $handledSimIds = [];

            foreach ($partners as $partner) {
                $partnerSims = $allSims->where('partner_id', $partner->id);
                $handledSimIds = array_merge($handledSimIds, $partnerSims->pluck('id')->all());
                $partnerTerritory = $partner->lga ? "{$partner->lga} ({$partner->state})" : "{$districtName} (Partner Territory)";

                $rows[] = $evalSlice(
                    $partnerSims,
                    $partnerTerritory,
                    'Partner Territory',
                    trim($partner->first_name . ' ' . $partner->last_name),
                    $partner->phone ?? '—',
                    'Partner'
                );
            }

            $directSims = $allSims->whereNotIn('id', $handledSimIds);
            if ($directSims->count() > 0 || count($rows) === 0) {
                $rows[] = $evalSlice(
                    $directSims,
                    "{$districtName} (Coordinator Office)",
                    'Coordinator Office',
                    trim($user->first_name . ' ' . $user->last_name),
                    $user->phone ?? '—',
                    'Coordinator'
                );
            }
        } elseif ($user->role === 'business') {
            $agents = User::where('referred_by', $user->id)->where('role', 'business_agent')->get();
            $handledSimIds = [];

            foreach ($agents as $agent) {
                $agentSims = $allSims->where('business_agent_id', $agent->id);
                $handledSimIds = array_merge($handledSimIds, $agentSims->pluck('id')->all());
                $agentTerritory = $agent->lga ? "{$agent->lga} ({$agent->state})" : ($agent->state ?: 'Business Agent Field');

                $rows[] = $evalSlice(
                    $agentSims,
                    $agentTerritory,
                    'Agent Territory',
                    trim($agent->first_name . ' ' . $agent->last_name),
                    $agent->phone ?? '—',
                    'Business Agent'
                );
            }

            $directSims = $allSims->whereNotIn('id', $handledSimIds);
            if ($directSims->count() > 0 || count($rows) === 0) {
                $rows[] = $evalSlice(
                    $directSims,
                    'Head Office / Direct Activations',
                    'Enterprise HQ',
                    trim($user->first_name . ' ' . $user->last_name),
                    $user->phone ?? '—',
                    'Enterprise Partner'
                );
            }
        } else {
            // Partner or Business Agent — group by category / device segment
            $categories = $allSims->pluck('category')->unique()->filter()->values();
            if ($categories->isEmpty()) {
                $categories = collect(['POS SIM', 'CCTV', 'ROUTER SIM']);
            }

            foreach ($categories as $cat) {
                $catSims = $allSims->where('category', $cat);
                $rows[] = $evalSlice(
                    $catSims,
                    $cat . ' Operations',
                    'Device Segment',
                    trim($user->first_name . ' ' . $user->last_name),
                    $user->phone ?? '—',
                    ucfirst(str_replace('_', ' ', $user->role))
                );
            }
        }

        $totalAllocated = array_sum(array_column($rows, 'allocated'));
        $totalActivated = array_sum(array_column($rows, 'activated'));
        $totalVolume = array_sum(array_column($rows, 'sales_volume'));
        $totalPending = array_sum(array_column($rows, 'pending'));
        $totalDormant = array_sum(array_column($rows, 'dormant'));
        $conversionRate = $totalAllocated > 0 ? round(($totalActivated / $totalAllocated) * 100, 1) : 0;

        $totals = [
            'allocated'       => $totalAllocated,
            'activated'       => $totalActivated,
            'sales_volume'    => $totalVolume,
            'conversion_rate' => $conversionRate,
            'pending'         => $totalPending,
            'dormant'         => $totalDormant,
        ];

        return [
            'rows'   => $rows,
            'totals' => $totals,
        ];
    }

    public function resolveSimJurisdictionLabel(Sim $sim): string
    {
        if ($sim->coordinator && $sim->coordinator->districtAsCoordinator) {
            $district = $sim->coordinator->districtAsCoordinator;
            return implode(' · ', array_filter([$district->name, $district->state?->name, $district->state?->region?->name]));
        }

        if ($sim->regionalManager && $sim->regionalManager->regionAsManager) {
            return $sim->regionalManager->regionAsManager->name . ' (Region)';
        }

        if ($sim->partner && $sim->partner->lga) {
            return $sim->partner->lga . ' (' . ($sim->partner->state ?? 'Partner') . ')';
        }

        if ($sim->businessAgent && $sim->businessAgent->lga) {
            return $sim->businessAgent->lga . ' (' . ($sim->businessAgent->state ?? 'Agent') . ')';
        }

        return 'Direct / Headquarters';
    }
}
