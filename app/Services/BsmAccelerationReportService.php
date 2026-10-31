<?php

namespace App\Services;

use App\Models\Region;
use App\Models\Report;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Sim;
use App\Models\SimRequest;
use App\Models\User;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BsmAccelerationReportService
{
    public const DORMANT_DAYS = 7;

    public const MANAGER_ROLES = [
        'regional_manager',
        'coordinator',
        'partner',
        'business',
        'business_agent',
    ];

    /**
     * Resolve date range filter with support for presets or explicit start/end dates.
     *
     * @return array [Carbon|null $start, Carbon|null $end, string $label, string $key]
     */
    public function resolveRange(Request $request): array
    {
        // If explicit start/end date passed, prioritize custom range
        if ($request->filled('start_date') || $request->filled('period_start')) {
            $startDate = $request->input('start_date') ?? $request->input('period_start');
            $endDate = $request->input('end_date') ?? $request->input('period_end');

            $start = Carbon::parse($startDate)->startOfDay();
            $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

            return [
                $start,
                $end,
                'Custom Range (' . $start->format('M d, Y') . ' to ' . $end->format('M d, Y') . ')',
                'custom',
            ];
        }

        $range = strtolower($request->input('range', '30days'));

        return match ($range) {
            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
                'Today (' . now()->format('M d, Y') . ')',
                'today',
            ],
            'yesterday' => [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                'Yesterday (' . now()->subDay()->format('M d, Y') . ')',
                'yesterday',
            ],
            '7days', '7_days', 'last_7_days' => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                'Last 7 Days (' . now()->subDays(6)->format('M d') . ' - ' . now()->format('M d, Y') . ')',
                '7days',
            ],
            '30days', '30_days', 'last_30_days' => [
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
            'last_month' => [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                'Last Month (' . now()->subMonth()->format('F Y') . ')',
                'last_month',
            ],
            'this_quarter' => [
                now()->firstOfQuarter()->startOfDay(),
                now()->endOfDay(),
                'This Quarter (' . now()->firstOfQuarter()->format('M d') . ' - ' . now()->format('M d, Y') . ')',
                'this_quarter',
            ],
            'this_year' => [
                now()->startOfYear(),
                now()->endOfDay(),
                'This Year (' . now()->format('Y') . ')',
                'this_year',
            ],
            'all', 'all_time' => [
                null,
                null,
                'All Time Historical',
                'all',
            ],
            'custom' => [
                $request->filled('period_start') ? Carbon::parse($request->period_start)->startOfDay() : now()->subDays(29)->startOfDay(),
                $request->filled('period_end') ? Carbon::parse($request->period_end)->endOfDay() : now()->endOfDay(),
                'Custom (' . ($request->period_start ?? now()->subDays(29)->toDateString()) . ' to ' . ($request->period_end ?? now()->toDateString()) . ')',
                'custom',
            ],
            default => [
                now()->subDays(29)->startOfDay(),
                now()->endOfDay(),
                'Last 30 Days (' . now()->subDays(29)->format('M d') . ' - ' . now()->format('M d, Y') . ')',
                '30days',
            ],
        };
    }

    /**
     * Resolve manager filtering constraints.
     */
    public function resolveManagerFilter(Request $request): array
    {
        $targetManager = null;
        $matchedManagerIds = [];
        $managerRole = $request->filled('manager_role') && in_array($request->manager_role, self::MANAGER_ROLES, true)
            ? $request->manager_role
            : null;

        if ($request->filled('manager_id')) {
            $targetManager = User::whereIn('role', self::MANAGER_ROLES)->find($request->manager_id);
            if ($targetManager) {
                $matchedManagerIds = [$targetManager->id];
                $managerLabel = "{$targetManager->name} (" . ucfirst(str_replace('_', ' ', $targetManager->role)) . ") — {$targetManager->phone}";
                return compact('targetManager', 'matchedManagerIds', 'managerLabel', 'managerRole');
            }
        }

        $search = trim((string) ($request->input('manager_search') ?? $request->input('q') ?? ''));

        if ($search !== '') {
            $query = User::whereIn('role', self::MANAGER_ROLES)
                ->where(function (Builder $q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });

            if ($managerRole) {
                $query->where('role', $managerRole);
            }

            $matchedUsers = $query->limit(50)->get();
            $matchedManagerIds = $matchedUsers->pluck('id')->all();

            if ($matchedUsers->count() === 1) {
                $targetManager = $matchedUsers->first();
                $managerLabel = "{$targetManager->name} (" . ucfirst(str_replace('_', ' ', $targetManager->role)) . ") — {$targetManager->phone}";
            } else {
                $managerLabel = "Matching '{$search}' (" . count($matchedManagerIds) . " managers)";
            }

            return compact('targetManager', 'matchedManagerIds', 'managerLabel', 'managerRole');
        }

        if ($managerRole) {
            $managerLabel = "All " . ucwords(str_replace('_', ' ', $managerRole)) . "s";
            return compact('targetManager', 'matchedManagerIds', 'managerLabel', 'managerRole');
        }

        return [
            'targetManager'     => null,
            'matchedManagerIds' => [],
            'managerLabel'      => 'All Platform Managers (Network Wide)',
            'managerRole'       => null,
        ];
    }

    /**
     * Query potential managers for searchable selector.
     */
    public function searchManagers(?string $term = null, ?string $role = null, int $limit = 30): Collection
    {
        $query = User::query()
            ->whereIn('role', self::MANAGER_ROLES)
            ->where('status', 'active');

        if ($role && in_array($role, self::MANAGER_ROLES, true)) {
            $query->where('role', $role);
        }

        if ($term !== null && trim($term) !== '') {
            $clean = trim($term);
            $query->where(function (Builder $q) use ($clean) {
                $q->where('first_name', 'like', "%{$clean}%")
                    ->orWhere('middle_name', 'like', "%{$clean}%")
                    ->orWhere('last_name', 'like', "%{$clean}%")
                    ->orWhere('email', 'like', "%{$clean}%")
                    ->orWhere('phone', 'like', "%{$clean}%");
            });
        }

        return $query->orderBy('role')->orderBy('first_name')->limit($limit)->get()->map(fn (User $u) => [
            'id'         => $u->id,
            'name'       => $u->name,
            'email'      => $u->email,
            'phone'      => $u->phone,
            'role'       => $u->role,
            'role_label' => ucwords(str_replace('_', ' ', $u->role)),
            'territory'  => $u->jurisdictionLabel() ?? 'Unassigned',
        ]);
    }

    /**
     * Build comprehensive report dataset matching all filters.
     */
    public function buildData(Request $request): array
    {
        [$rangeStart, $rangeEnd, $rangeLabel, $rangeKey] = $this->resolveRange($request);
        $managerFilter = $this->resolveManagerFilter($request);
        $targetManager = $managerFilter['targetManager'];
        $matchedManagerIds = $managerFilter['matchedManagerIds'];
        $managerLabel = $managerFilter['managerLabel'];
        $managerRole = $managerFilter['managerRole'];

        $dormantCutoff = now()->subDays(self::DORMANT_DAYS);
        $priceMap = $this->resolvePriceMap();

        // 1. Query Stalled / Dormant SIMs (>= 7 days in field, unactivated)
        $simsQuery = Sim::with(['user', 'partner', 'coordinator', 'regionalManager', 'business'])
            ->where('status', '!=', SimStatus::ACTIVATED)
            ->where('status', '!=', SimStatus::UNASSIGNED);

        if ($rangeStart && $rangeEnd) {
            $simsQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        } else {
            $simsQuery->where('created_at', '<=', $dormantCutoff);
        }

        if ($targetManager) {
            $mId = $targetManager->id;
            $mRole = $targetManager->role;
            $simsQuery->where(function (Builder $q) use ($mId, $mRole) {
                $q->where('user_id', $mId);
                if ($mRole === 'regional_manager') $q->orWhere('regional_manager_id', $mId);
                if ($mRole === 'coordinator') $q->orWhere('coordinator_id', $mId);
                if ($mRole === 'partner') $q->orWhere('partner_id', $mId);
                if ($mRole === 'business') $q->orWhere('business_id', $mId);
                if ($mRole === 'business_agent') $q->orWhere('business_agent_id', $mId);
            });
        } elseif (!empty($matchedManagerIds)) {
            $simsQuery->where(function (Builder $q) use ($matchedManagerIds) {
                $q->whereIn('user_id', $matchedManagerIds)
                    ->orWhereIn('regional_manager_id', $matchedManagerIds)
                    ->orWhereIn('coordinator_id', $matchedManagerIds)
                    ->orWhereIn('partner_id', $matchedManagerIds)
                    ->orWhereIn('business_id', $matchedManagerIds)
                    ->orWhereIn('business_agent_id', $matchedManagerIds);
            });
        } elseif ($managerRole) {
            $simsQuery->whereHas('user', fn ($uq) => $uq->where('role', $managerRole));
        }

        $stalledSims = $simsQuery->latest('created_at')->get();

        // 2. Query Pending Stock Requests
        $requestsQuery = SimRequest::with(['user', 'upline'])->where('status', 'pending');

        if ($rangeStart && $rangeEnd) {
            $requestsQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        }

        if ($targetManager) {
            $mId = $targetManager->id;
            $requestsQuery->where(function (Builder $q) use ($mId) {
                $q->where('user_id', $mId)->orWhere('upline_id', $mId);
            });
        } elseif (!empty($matchedManagerIds)) {
            $requestsQuery->where(function (Builder $q) use ($matchedManagerIds) {
                $q->whereIn('user_id', $matchedManagerIds)
                    ->orWhereIn('upline_id', $matchedManagerIds);
            });
        } elseif ($managerRole) {
            $requestsQuery->whereHas('user', fn ($uq) => $uq->where('role', $managerRole));
        }

        $pendingRequests = $requestsQuery->latest()->get();

        // 3. Query Failed Activation Bonuses
        $bonusesQuery = Report::where('type', 'activation_bonus_data')
            ->whereIn('status', ['failed', 'retrying']);

        if ($rangeStart && $rangeEnd) {
            $bonusesQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        }

        if ($targetManager) {
            $bonusesQuery->where('user_id', $targetManager->id);
        } elseif (!empty($matchedManagerIds)) {
            $bonusesQuery->whereIn('user_id', $matchedManagerIds);
        }

        $failedBonusReports = $bonusesQuery->latest()->get();

        // 4. Build Manager Velocity & Acceleration Summary Table
        $managersQuery = User::whereIn('role', self::MANAGER_ROLES)->where('status', 'active');

        if ($targetManager) {
            $managersQuery->where('id', $targetManager->id);
        } elseif (!empty($matchedManagerIds)) {
            $managersQuery->whereIn('id', $matchedManagerIds);
        } elseif ($managerRole) {
            $managersQuery->where('role', $managerRole);
        }

        $managers = $managersQuery->orderBy('role')->orderBy('first_name')->get();

        $managerSummaries = [];
        $totalAllocatedAcross = 0;
        $totalActivatedAcross = 0;
        $totalVolumeAcross = 0.00;

        foreach ($managers as $mgr) {
            $col = match ($mgr->role) {
                'regional_manager' => 'regional_manager_id',
                'coordinator'      => 'coordinator_id',
                'partner'          => 'partner_id',
                'business'         => 'business_id',
                'business_agent'   => 'business_agent_id',
                default            => 'user_id',
            };

            $baseMgrSims = Sim::where($col, $mgr->id);

            $allocatedCount = (clone $baseMgrSims)->count();

            $activatedQuery = (clone $baseMgrSims)->where('status', SimStatus::ACTIVATED);
            if ($rangeStart && $rangeEnd) {
                $activatedQuery->whereBetween('updated_at', [$rangeStart, $rangeEnd]);
            }
            $activatedCount = $activatedQuery->count();

            $stalledCount = (clone $baseMgrSims)
                ->where('status', '!=', SimStatus::ACTIVATED)
                ->where('status', '!=', SimStatus::UNASSIGNED)
                ->where('created_at', '<=', $dormantCutoff)
                ->count();

            $mgrPendingRequests = SimRequest::where('user_id', $mgr->id)->where('status', 'pending')->count();

            $conversionRate = $allocatedCount > 0 ? round(($activatedCount / $allocatedCount) * 100, 1) : 0.0;
            $mgrVolume = $activatedCount * 1000.00; // estimated standard nominal volume

            // Acceleration Status Tag
            $velocityStatus = 'Healthy';
            $velocityClass = 'text-emerald-700 bg-emerald-50';
            if ($stalledCount > 15) {
                $velocityStatus = 'Critical Bottleneck';
                $velocityClass = 'text-rose-700 bg-rose-50';
            } elseif ($stalledCount > 0 || $mgrPendingRequests > 0) {
                $velocityStatus = 'Needs Attention';
                $velocityClass = 'text-amber-700 bg-amber-50';
            } elseif ($conversionRate >= 50) {
                $velocityStatus = 'High Velocity';
                $velocityClass = 'text-blue-700 bg-blue-50';
            }

            $managerSummaries[] = [
                'id'              => $mgr->id,
                'name'            => $mgr->name,
                'role'            => $mgr->role,
                'role_label'      => ucfirst(str_replace('_', ' ', $mgr->role)),
                'phone'           => $mgr->phone ?? '—',
                'email'           => $mgr->email ?? '—',
                'territory'       => $mgr->jurisdictionLabel() ?? 'Unassigned',
                'allocated'       => $allocatedCount,
                'activated'       => $activatedCount,
                'stalled'         => $stalledCount,
                'pending_requests'=> $mgrPendingRequests,
                'conversion_rate' => $conversionRate,
                'sales_volume'    => $mgrVolume,
                'status'          => $velocityStatus,
                'status_class'    => $velocityClass,
            ];

            $totalAllocatedAcross += $allocatedCount;
            $totalActivatedAcross += $activatedCount;
            $totalVolumeAcross += $mgrVolume;
        }

        // Calculate KPI summary
        $totalStalledCount = $stalledSims->count();
        $totalPendingRequestsCount = $pendingRequests->count();
        $totalPendingQuantity = $pendingRequests->sum('quantity');
        $totalFailedBonusesCount = $failedBonusReports->count();
        $totalManagersCount = count($managerSummaries);

        $stalledEstimatedValue = 0.00;
        foreach ($stalledSims as $s) {
            $cat = $s->category ?? 'POS SIM';
            $stalledEstimatedValue += ($priceMap[$cat] ?? 1000.00);
        }

        return [
            'rangeStart'            => $rangeStart,
            'rangeEnd'              => $rangeEnd,
            'rangeLabel'            => $rangeLabel,
            'rangeKey'              => $rangeKey,
            'managerLabel'          => $managerLabel,
            'targetManager'         => $targetManager,
            'managerRole'           => $managerRole,
            'stalledSims'           => $stalledSims,
            'pendingRequests'       => $pendingRequests,
            'failedBonusReports'    => $failedBonusReports,
            'managerSummaries'      => $managerSummaries,
            'kpis'                  => [
                'stalled_count'           => $totalStalledCount,
                'stalled_value'           => $stalledEstimatedValue,
                'pending_requests_count'  => $totalPendingRequestsCount,
                'pending_units_count'     => $totalPendingQuantity,
                'failed_bonuses_count'    => $totalFailedBonusesCount,
                'managers_count'          => $totalManagersCount,
                'total_allocated'         => $totalAllocatedAcross,
                'total_activated'         => $totalActivatedAcross,
                'total_sales_volume'      => $totalVolumeAcross,
            ],
        ];
    }

    /**
     * Generate Excel-compatible clean CSV document.
     */
    public function toCsv(array $data, User $actor): string
    {
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Microsoft Excel

        // 1. Report Header
        $csv .= "\"SIMPHIX BUSINESS SALES MANAGER — SALES ACCELERATION & INTERVENTIONS REPORT\"\n";
        $csv .= "\"Generated By\",\"" . str_replace('"', '""', $actor->name) . " (Business Sales Manager)\"\n";
        $csv .= "\"Reporting Period\",\"" . str_replace('"', '""', $data['rangeLabel']) . "\"\n";
        $csv .= "\"Manager Filter Scope\",\"" . str_replace('"', '""', $data['managerLabel']) . "\"\n";
        $csv .= "\"Generated At\",\"" . now()->format('Y-m-d H:i:s') . "\"\n";
        $csv .= "\n";

        // 2. Executive Acceleration KPIs
        $kpis = $data['kpis'];
        $csv .= "\"EXECUTIVE ACCELERATION & INTERVENTIONS OVERVIEW\"\n";
        $csv .= "\"Stalled Field Stock (>= 7 Days)\",\"" . $kpis['stalled_count'] . " SIMs (Est. Value: NGN " . number_format($kpis['stalled_value'], 2) . ")\"\n";
        $csv .= "\"Pending Inventory Requests\",\"" . $kpis['pending_requests_count'] . " requests (" . $kpis['pending_units_count'] . " units)\"\n";
        $csv .= "\"Failed / Stalled Activation Bonuses\",\"" . $kpis['failed_bonuses_count'] . " bonus retries required\"\n";
        $csv .= "\"Active Managers In Scope\",\"" . $kpis['managers_count'] . "\"\n";
        $csv .= "\"Total Field Stock Allocated\",\"" . $kpis['total_allocated'] . " SIMs\"\n";
        $csv .= "\"Total Activated in Period\",\"" . $kpis['total_activated'] . " SIMs (NGN " . number_format($kpis['total_sales_volume'], 2) . ")\"\n";
        $csv .= "\n";

        // 3. Manager Acceleration & Field Stock Summary
        $csv .= "\"MANAGER ACCELERATION & FIELD VELOCITY SUMMARY\"\n";
        $csv .= "\"Manager Name\",\"Role\",\"Phone\",\"Email\",\"Territory / Jurisdiction\",\"Allocated Stock\",\"Activated in Period\",\"Stalled Stock (>= 7d)\",\"Pending Requests\",\"Conversion Rate (%)\",\"Sales Volume (NGN)\",\"Velocity Status\"\n";

        foreach ($data['managerSummaries'] as $mgr) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
                $mgr['name'],
                $mgr['role_label'],
                $mgr['phone'],
                $mgr['email'],
                $mgr['territory'],
                $mgr['allocated'],
                $mgr['activated'],
                $mgr['stalled'],
                $mgr['pending_requests'],
                $mgr['conversion_rate'] . '%',
                number_format($mgr['sales_volume'], 2),
                $mgr['status'],
            ])) . "\n";
        }
        $csv .= "\n";

        // 4. Stalled Field Stock Line Items
        $csv .= "\"STALLED FIELD STOCK LINE ITEMS (UNACTIVATED >= 7 DAYS)\"\n";
        $csv .= "\"SIM Number\",\"Provider\",\"Category\",\"Current Holder\",\"Holder Role\",\"Contact Phone\",\"Days In Field\",\"Allocated Date\"\n";

        foreach ($data['stalledSims'] as $sim) {
            $holder = $sim->user ?? $sim->partner ?? $sim->coordinator ?? $sim->regionalManager;
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
                $sim->number,
                strtoupper((string) $sim->provider),
                $sim->category ?? 'POS SIM',
                $holder?->name ?? 'Unassigned',
                ucfirst(str_replace('_', ' ', $holder?->role ?? '')),
                $holder?->phone ?? '—',
                $sim->daysHeld() . ' days',
                $sim->created_at ? $sim->created_at->format('Y-m-d H:i') : '—',
            ])) . "\n";
        }
        $csv .= "\n";

        // 5. Pending Stock Requests
        $csv .= "\"PENDING INVENTORY DISPATCH REQUESTS\"\n";
        $csv .= "\"Requester Name\",\"Requester Role\",\"Contact Phone\",\"Category\",\"Provider\",\"Quantity\",\"Requested Date\",\"Status\"\n";

        foreach ($data['pendingRequests'] as $req) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
                $req->user?->name ?? 'User #' . $req->user_id,
                ucfirst(str_replace('_', ' ', $req->user?->role ?? '')),
                $req->user?->phone ?? '—',
                $req->category,
                strtoupper((string) $req->provider),
                $req->quantity,
                $req->created_at ? $req->created_at->format('Y-m-d H:i') : '—',
                ucfirst($req->status),
            ])) . "\n";
        }
        $csv .= "\n";

        // 6. Failed Activation Bonuses
        $csv .= "\"STALLED ACTIVATION BONUS RETRIES\"\n";
        $csv .= "\"Target Number\",\"Network\",\"Status\",\"Attempt Date\",\"Error / Vendor Payload\"\n";

        foreach ($data['failedBonusReports'] as $bon) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', [
                $bon->phone ?? $bon->account_number ?? '—',
                strtoupper((string) ($bon->network ?? '—')),
                strtoupper((string) $bon->status),
                $bon->created_at ? $bon->created_at->format('Y-m-d H:i') : '—',
                $bon->description ?? $bon->api_response ?? 'No payload',
            ])) . "\n";
        }

        return $csv;
    }

    /**
     * Generate executive, beautifully formatted PDF document via TCPDF.
     */
    public function toPdf(array $data, User $actor): string
    {
        $html = view('bsm.reports.acceleration_pdf', array_merge($data, [
            'actor'      => $actor,
            'reportDate' => now()->format('M d, Y h:i A'),
        ]))->render();

        $pdf = new class('L', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF {
            public function Footer()
            {
                $this->SetY(-10);
                $this->SetFont('dejavusans', '', 7.2);
                $this->SetTextColor(148, 163, 184); // slate-400
                $this->Cell(200, 6, 'SimPhix BSM Sales Acceleration Report · Confidential · Generated ' . date('Y-m-d H:i'), 0, 0, 'L');
                $this->Cell(0, 6, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };

        $pdf->SetCreator('SimPhix');
        $pdf->SetAuthor('SimPhix BSM Console');
        $pdf->SetTitle('BSM Sales Acceleration & Interventions Report - ' . now()->format('Y-m-d'));
        $pdf->SetSubject('Sales Acceleration Report');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(10, 8, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('bsm-sales-acceleration-report.pdf', 'S');
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
}
