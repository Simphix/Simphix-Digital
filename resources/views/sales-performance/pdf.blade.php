<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SimPhix - Sales Performance and Territory Report</title>
    <style>
        body {
            font-family: dejavusans, sans-serif;
            color: #1e293b;
            font-size: 8pt;
            line-height: 1.5;
        }

        /* Executive Header Banner */
        .banner-card {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .header-title {
            font-size: 13.5pt;
            font-weight: bold;
            color: #0056D2;
            letter-spacing: 0.3px;
            margin-bottom: 3px;
        }
        .header-meta {
            font-size: 7.3pt;
            color: #475569;
            line-height: 1.5;
        }

        /* Clean Section Headings (No heavy colored lines) */
        .section-wrap {
            margin-top: 16px;
            margin-bottom: 7px;
        }
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.2px;
        }
        .section-desc {
            font-size: 6.8pt;
            color: #64748b;
            margin-top: 1px;
        }

        /* Executive KPI Cards Grid */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            margin-bottom: 10px;
        }
        .kpi-card {
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
        }
        .kpi-label {
            font-size: 6.6pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 3px;
        }
        .kpi-number {
            font-size: 11.5pt;
            font-weight: bold;
            line-height: 1.25;
        }
        .kpi-sub {
            font-size: 6.7pt;
            margin-top: 2px;
        }

        /* Airy Tables with Light Colors and Good Edges */
        .styled-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
        }
        .styled-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            font-size: 7.2pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1.5px solid #cbd5e1;
            padding: 7px 8px;
        }
        .styled-table td {
            font-size: 7.5pt;
            padding: 6.5px 8px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .styled-table tr.even td {
            background-color: #fafbfc;
        }
        .styled-table tr.totals-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
            border-top: 1.5px solid #cbd5e1;
            border-bottom: 1.5px solid #cbd5e1;
            padding: 7.5px 8px;
        }

        /* Clean Soft Badges */
        .badge {
            font-size: 6.6pt;
            font-weight: bold;
            padding: 2.5px 6px;
            border-radius: 4px;
        }
        .badge-success { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .badge-info    { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-warning { background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .badge-danger  { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-slate   { background-color: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

    <!-- Header Banner Card (Light, Rounded, Elegant) -->
    <table class="banner-card" cellpadding="0" cellspacing="0">
        <tr>
            <td width="30%" valign="middle">
                @if (file_exists(public_path('assets/images/logo/logo1.png')))
                    <img src="{{ public_path('assets/images/logo/logo1.png') }}" height="32" /><br/>
                @else
                    <span style="font-size:16pt; font-weight:bold; color:#0056D2;">SimPhix</span><br/>
                @endif
                <span style="font-size:6.8pt; color:#64748b; font-weight:500;">Empowering Businesses Through Smart Connectivity</span>
            </td>
            <td width="70%" align="right" valign="middle">
                <div class="header-title">Sales Performance and Territory Report</div>
                <div class="header-meta">
                    <b>Generated For:</b> {{ $user->name }} &nbsp;·&nbsp;
                    <span style="color:#0056D2; font-weight:bold;">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span> &nbsp;·&nbsp;
                    <b>Scope:</b> {{ $user->jurisdictionLabel() ?? 'All Managed Territories' }}<br/>
                    <b>Reporting Window:</b> <span style="color:#0f172a; font-weight:bold;">{{ $rangeLabel }}</span> &nbsp;|&nbsp;
                    <b>Generated:</b> {{ now()->format('F d, Y · h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Section 1: Executive KPI Summary -->
    <div class="section-wrap">
        <div class="section-title">Executive Summary</div>
        <div class="section-desc">Key performance indicators, sales velocity, and inventory status for the reporting window.</div>
    </div>

    <!-- KPI Grid (2 Balanced Rows with Light Pastel Backgrounds) -->
    <table class="kpi-table" cellpadding="3" cellspacing="5">
        <!-- Row 1 -->
        <tr>
            <td width="25%">
                <div class="kpi-card" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="kpi-label" style="color: #64748b;">Total Allocated SIMs</div>
                    <div class="kpi-number" style="color: #0f172a;">{{ number_format($totalAllocatedSims) }}</div>
                    <div class="kpi-sub" style="color: #64748b;">Entire stock in jurisdiction</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                    <div class="kpi-label" style="color: #1e40af;">Activated Sales</div>
                    <div class="kpi-number" style="color: #0056D2;">{{ number_format($activatedSalesCount) }}</div>
                    <div class="kpi-sub" style="color: #2563eb;">Completed SIM activations</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card" style="background-color: #f0fdf4; border: 1px solid #bbf7d0;">
                    <div class="kpi-label" style="color: #166534;">Total Sales Volume</div>
                    <div class="kpi-number" style="color: #15803d; font-size: 11pt;">NGN {{ number_format($totalSalesVolume, 2) }}</div>
                    <div class="kpi-sub" style="color: #16a34a;">Gross activation value</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card" style="background-color: #faf5ff; border: 1px solid #e9d5ff;">
                    <div class="kpi-label" style="color: #7e22ce;">Overall Conversion</div>
                    <div class="kpi-number" style="color: #6d28d9;">{{ $overallConversionRate }}%</div>
                    <div class="kpi-sub" style="color: #9333ea;">All-time activation rate</div>
                </div>
            </td>
        </tr>
        <!-- Row 2 -->
        <tr>
            <td width="25%">
                <div class="kpi-card" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="kpi-label" style="color: #64748b;">Direct Sales</div>
                    <div class="kpi-number" style="color: #334155; font-size: 10.5pt;">{{ number_format($directSalesCount) }} <span style="font-size: 7.2pt; font-weight: normal; color: #64748b;">SIMs</span></div>
                    <div class="kpi-sub" style="color: #64748b;">NGN {{ number_format($directSalesVolume, 2) }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="kpi-label" style="color: #64748b;">Downline Sales</div>
                    <div class="kpi-number" style="color: #334155; font-size: 10.5pt;">{{ number_format($downlineSalesCount) }} <span style="font-size: 7.2pt; font-weight: normal; color: #64748b;">SIMs</span></div>
                    <div class="kpi-sub" style="color: #64748b;">NGN {{ number_format($downlineSalesVolume, 2) }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card" style="background-color: #f5f3ff; border: 1px solid #ddd6fe;">
                    <div class="kpi-label" style="color: #6d28d9;">Commissions Earned</div>
                    <div class="kpi-number" style="color: #7c3aed; font-size: 10.5pt;">NGN {{ number_format($commissionThisPeriod, 2) }}</div>
                    <div class="kpi-sub" style="color: #8b5cf6;">In selected window</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card" style="background-color: #fffbeb; border: 1px solid #fde68a;">
                    <div class="kpi-label" style="color: #92400e;">Stock Status</div>
                    <div class="kpi-number" style="font-size: 10.5pt;">
                        <span style="color: #d97706;">{{ number_format($pendingCount) }}</span>
                        <span style="font-size: 7.2pt; color: #94a3b8; font-weight: normal;">pending &nbsp;·&nbsp;</span>
                        <span style="color: #dc2626;">{{ number_format($dormantCount) }}</span>
                        <span style="font-size: 7.2pt; color: #94a3b8; font-weight: normal;">dormant</span>
                    </div>
                    <div class="kpi-sub" style="color: #b45309;">Unactivated stock breakdown</div>
                </div>
            </td>
        </tr>
    </table>

    <div style="height: 10px;"></div>

    <!-- Section 2: Category and Network Breakdown Tables (Side-by-Side) -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 10px;">
        <tr>
            <!-- Category Column -->
            <td width="58%" valign="top">
                <div class="section-wrap" style="margin-top: 0; margin-bottom: 6px;">
                    <div class="section-title">SIM Category Breakdown</div>
                    <div class="section-desc">Performance segmented by device package type.</div>
                </div>
                <table class="styled-table" cellpadding="6">
                    <thead>
                        <tr>
                            <th width="32%"><b>Category</b></th>
                            <th width="17%" align="center"><b>Allocated</b></th>
                            <th width="17%" align="center"><b>Activated</b></th>
                            <th width="20%" align="right"><b>Volume (NGN)</b></th>
                            <th width="14%" align="center"><b>Conv. %</b></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categoryBreakdown as $cat)
                            <tr class="{{ $loop->even ? 'even' : '' }}">
                                <td><b>{{ $cat['category'] }}</b></td>
                                <td align="center" style="color: #64748b;">{{ number_format($cat['allocated']) }}</td>
                                <td align="center" style="color: #0056D2; font-weight: bold;">{{ number_format($cat['activated']) }}</td>
                                <td align="right" style="color: #15803d; font-weight: bold;">{{ number_format($cat['volume'], 2) }}</td>
                                <td align="center">
                                    <span class="badge {{ $cat['conversion_rate'] >= 50 ? 'badge-success' : 'badge-slate' }}">
                                        {{ $cat['conversion_rate'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" align="center" style="color: #64748b; padding: 12px;">No category sales recorded in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <!-- Gutter -->
            <td width="4%"></td>
            <!-- Network Column -->
            <td width="38%" valign="top">
                <div class="section-wrap" style="margin-top: 0; margin-bottom: 6px;">
                    <div class="section-title">Network Distribution</div>
                    <div class="section-desc">Carrier distribution and market share.</div>
                </div>
                <table class="styled-table" cellpadding="6">
                    <thead>
                        <tr>
                            <th width="35%"><b>Network</b></th>
                            <th width="25%" align="center"><b>Allocated</b></th>
                            <th width="25%" align="center"><b>Activated</b></th>
                            <th width="15%" align="center"><b>Share</b></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalProvActive = $providerBreakdown->sum('activated'); @endphp
                        @forelse ($providerBreakdown as $prov)
                            @php $share = $totalProvActive > 0 ? round(($prov['activated'] / $totalProvActive) * 100, 1) : 0; @endphp
                            <tr class="{{ $loop->even ? 'even' : '' }}">
                                <td><b>{{ $prov['provider'] }}</b></td>
                                <td align="center" style="color: #64748b;">{{ number_format($prov['allocated']) }}</td>
                                <td align="center" style="color: #0056D2; font-weight: bold;">{{ number_format($prov['activated']) }}</td>
                                <td align="center">
                                    <span class="badge badge-info">{{ $share }}%</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" align="center" style="color: #64748b; padding: 12px;">No network sales recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div style="height: 10px;"></div>

    <!-- Section 3: Territory and Downline Breakdown Table -->
    <div class="section-wrap">
        <div class="section-title">Territory and Downline Breakdown</div>
        <div class="section-desc">Comprehensive jurisdictional performance across districts, regional offices, and partner portfolios.</div>
    </div>
    <table class="styled-table" cellpadding="6.5">
        <thead>
            <tr>
                <th width="24%"><b>Territory / Coverage</b></th>
                <th width="14%"><b>Level / Type</b></th>
                <th width="16%"><b>Assigned Lead</b></th>
                <th width="12%"><b>Contact Phone</b></th>
                <th width="7%" align="center"><b>Alloc.</b></th>
                <th width="7%" align="center"><b>Active</b></th>
                <th width="10%" align="right"><b>Volume (NGN)</b></th>
                <th width="5%" align="center"><b>Conv.</b></th>
                <th width="5%" align="center"><b>Pending</b></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jurisdictionRows as $row)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td><b>{{ $row['jurisdiction'] }}</b></td>
                    <td style="color: #64748b;">{{ $row['type'] }}</td>
                    <td>{{ $row['lead_name'] }}</td>
                    <td style="color: #64748b;">{{ $row['lead_phone'] }}</td>
                    <td align="center" style="color: #64748b;">{{ number_format($row['allocated']) }}</td>
                    <td align="center" style="color: #0056D2; font-weight: bold;">{{ number_format($row['activated']) }}</td>
                    <td align="right" style="color: #15803d; font-weight: bold;">{{ number_format($row['sales_volume'], 2) }}</td>
                    <td align="center">
                        <span class="badge {{ $row['conversion_rate'] >= 50 ? 'badge-success' : 'badge-slate' }}">
                            {{ $row['conversion_rate'] }}%
                        </span>
                    </td>
                    <td align="center" style="color: #d97706;">{{ number_format($row['pending']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" align="center" style="color: #64748b; padding: 14px;">No territory breakdown data available for this selection.</td>
                </tr>
            @endforelse

            @if (!empty($jurisdictionRows))
                @php $t = $jurisdictionTotals; @endphp
                <tr class="totals-row">
                    <td colspan="4"><b>TOTAL PORTFOLIO SUMMARY</b></td>
                    <td align="center"><b>{{ number_format($t['allocated']) }}</b></td>
                    <td align="center" style="color: #0056D2;"><b>{{ number_format($t['activated']) }}</b></td>
                    <td align="right" style="color: #15803d;"><b>{{ number_format($t['sales_volume'], 2) }}</b></td>
                    <td align="center"><span class="badge badge-info">{{ $t['conversion_rate'] }}%</span></td>
                    <td align="center" style="color: #d97706;"><b>{{ number_format($t['pending']) }}</b></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div style="height: 10px;"></div>

    <!-- Section 4: Itemized SIM Activations -->
    <div class="section-wrap">
        <div class="section-title">Itemized Activated SIMs (Recent Sales Records)</div>
        <div class="section-desc">Line-item audit of SIM activations recorded within the reporting window.</div>
    </div>
    <table class="styled-table" cellpadding="6">
        <thead>
            <tr>
                <th width="12%"><b>SIM Number</b></th>
                <th width="10%"><b>Category</b></th>
                <th width="7%" align="center"><b>Network</b></th>
                <th width="15%"><b>Customer Name</b></th>
                <th width="11%"><b>Customer Phone</b></th>
                <th width="13%"><b>Activated Date</b></th>
                <th width="13%"><b>Activated By</b></th>
                <th width="10%"><b>Territory</b></th>
                <th width="9%" align="right"><b>Amount (NGN)</b></th>
            </tr>
        </thead>
        <tbody>
            @php
                $displayLimit = 150;
                $displayList = $activatedSimsList->take($displayLimit);
            @endphp
            @forelse ($displayList as $sim)
                @php
                    $customerName = $sim->customer?->full_name
                        ?? ($sim->user && $sim->user_id !== $sim->partner_id ? trim($sim->user->first_name . ' ' . $sim->user->last_name) : '—');
                    $customerPhone = $sim->customer?->phone_number
                        ?? ($sim->user && $sim->user_id !== $sim->partner_id ? $sim->user->phone : '—');
                    $activatedBy = $sim->user ? trim($sim->user->first_name . ' ' . $sim->user->last_name) : 'Direct';
                    $activatedDate = $sim->customer?->created_at?->format('Y-m-d H:i') ?? $sim->updated_at?->format('Y-m-d H:i');
                    $jurisdiction = $controller->resolveSimJurisdictionLabel($sim);
                    $price = $controller->getSimPrice($sim, $priceMap);
                @endphp
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td><b>{{ $sim->number }}</b></td>
                    <td>{{ $sim->category }}</td>
                    <td align="center"><span class="badge badge-slate">{{ strtoupper($sim->provider) }}</span></td>
                    <td>{{ $customerName }}</td>
                    <td style="color: #64748b;">{{ $customerPhone }}</td>
                    <td style="color: #64748b;">{{ $activatedDate }}</td>
                    <td>{{ $activatedBy }}</td>
                    <td style="color: #64748b;">{{ $jurisdiction }}</td>
                    <td align="right" style="color: #15803d; font-weight: bold;">{{ number_format($price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" align="center" style="color: #64748b; padding: 14px;">No activated SIM sales found in this period matching the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($activatedSimsList->count() > $displayLimit)
        <p style="font-size: 6.8pt; color: #64748b; text-align: center; margin-top: 6px;">
            Showing first {{ $displayLimit }} of {{ number_format($activatedSimsList->count()) }} activated SIM records. Export as CSV for the unrestricted raw dataset.
        </p>
    @endif

</body>
</html>
