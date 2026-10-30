<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SimPhix - Sales Performance &amp; Territory Report</title>
    <style>
        body {
            font-family: helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 8pt;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0056D2;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0056D2;
            letter-spacing: 0.5px;
        }
        .meta-text {
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.4;
        }
        .section-header {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
            border-left: 3px solid #0056D2;
            padding-left: 6px;
            margin-top: 10px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            margin-bottom: 10px;
        }
        .kpi-cell {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: center;
        }
        .kpi-label {
            font-size: 6.5pt;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 11pt;
            font-weight: bold;
            color: #0056D2;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 12px;
        }
        .data-table th {
            background-color: #0056D2;
            color: #ffffff;
            font-weight: bold;
            font-size: 7.5pt;
            padding: 5px 6px;
        }
        .data-table td {
            font-size: 7.5pt;
            padding: 4.5px 6px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .data-table tr.even td {
            background-color: #f8fafc;
        }
        .data-table tr.totals-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
            border-top: 1.5px solid #cbd5e1;
            border-bottom: 1.5px solid #cbd5e1;
        }
        .badge {
            font-size: 6.5pt;
            font-weight: bold;
            padding: 2px 4px;
            border-radius: 3px;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        .badge-danger  { background-color: #fee2e2; color: #b91c1c; }
        .badge-info    { background-color: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td width="35%">
                @if (file_exists(public_path('assets/images/logo/logo1.png')))
                    <img src="{{ public_path('assets/images/logo/logo1.png') }}" height="32" /><br/>
                @else
                    <span style="font-size:16pt; font-weight:bold; color:#0056D2;">SimPhix</span><br/>
                @endif
                <span style="font-size:7pt; color:#64748b;">Empowering Businesses Through Smart Connectivity</span>
            </td>
            <td width="65%" align="right">
                <div class="header-title">SALES PERFORMANCE &amp; TERRITORY REPORT</div>
                <div class="meta-text">
                    <b>Generated For:</b> {{ $user->name }} ({{ ucfirst(str_replace('_', ' ', $user->role)) }}) &nbsp;|&nbsp;
                    <b>Scope:</b> {{ $user->jurisdictionLabel() ?? 'All Managed Territories' }}<br/>
                    <b>Reporting Period:</b> {{ $rangeLabel }} &nbsp;|&nbsp;
                    <b>Date Generated:</b> {{ now()->format('F d, Y · h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Executive Overview KPIs -->
    <div class="section-header">Executive Summary KPIs</div>
    <table class="kpi-table" cellpadding="4" cellspacing="3">
        <tr>
            <td class="kpi-cell" width="12.5%">
                <div class="kpi-label">Allocated SIMs</div>
                <div class="kpi-value" style="color:#0f172a;">{{ number_format($totalAllocatedSims) }}</div>
            </td>
            <td class="kpi-cell" width="12.5%">
                <div class="kpi-label">Activated Sales</div>
                <div class="kpi-value" style="color:#0056D2;">{{ number_format($activatedSalesCount) }}</div>
            </td>
            <td class="kpi-cell" width="16%">
                <div class="kpi-label">Sales Volume</div>
                <div class="kpi-value" style="color:#059669; font-size:10pt;">NGN {{ number_format($totalSalesVolume, 2) }}</div>
            </td>
            <td class="kpi-cell" width="11%">
                <div class="kpi-label">Conversion</div>
                <div class="kpi-value" style="color:#2563eb;">{{ $overallConversionRate }}%</div>
            </td>
            <td class="kpi-cell" width="13%">
                <div class="kpi-label">Direct Sales</div>
                <div class="kpi-value" style="font-size:9pt; color:#334155;">{{ number_format($directSalesCount) }} <span style="font-size:6.5pt; color:#64748b;">(NGN {{ number_format($directSalesVolume) }})</span></div>
            </td>
            <td class="kpi-cell" width="13%">
                <div class="kpi-label">Downline Sales</div>
                <div class="kpi-value" style="font-size:9pt; color:#334155;">{{ number_format($downlineSalesCount) }} <span style="font-size:6.5pt; color:#64748b;">(NGN {{ number_format($downlineSalesVolume) }})</span></div>
            </td>
            <td class="kpi-cell" width="11%">
                <div class="kpi-label">Commissions</div>
                <div class="kpi-value" style="color:#7c3aed; font-size:9.5pt;">NGN {{ number_format($commissionThisPeriod, 2) }}</div>
            </td>
            <td class="kpi-cell" width="11%">
                <div class="kpi-label">Pending / Dormant</div>
                <div class="kpi-value" style="font-size:9pt;">
                    <span style="color:#d97706;">{{ number_format($pendingCount) }}</span>
                    <span style="font-size:7.5pt; color:#94a3b8;">/</span>
                    <span style="color:#dc2626;">{{ number_format($dormantCount) }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Category & Network Breakdown Tables (2-column layout) -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
        <tr>
            <td width="58%" valign="top">
                <div class="section-header">Category Breakdown</div>
                <table class="data-table" cellpadding="3">
                    <thead>
                        <tr>
                            <th width="32%"><b>Category</b></th>
                            <th width="18%" align="center"><b>Allocated</b></th>
                            <th width="18%" align="center"><b>Activated</b></th>
                            <th width="18%" align="right"><b>Volume (NGN)</b></th>
                            <th width="14%" align="center"><b>Conv. %</b></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categoryBreakdown as $cat)
                            <tr class="{{ $loop->even ? 'even' : '' }}">
                                <td><b>{{ $cat['category'] }}</b></td>
                                <td align="center">{{ number_format($cat['allocated']) }}</td>
                                <td align="center" style="color:#0056D2; font-weight:bold;">{{ number_format($cat['activated']) }}</td>
                                <td align="right" style="color:#059669;">{{ number_format($cat['volume'], 2) }}</td>
                                <td align="center">{{ $cat['conversion_rate'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" align="center" style="color:#64748b; padding:8px;">No category sales in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td width="4%"></td>
            <td width="38%" valign="top">
                <div class="section-header">Network / Provider Breakdown</div>
                <table class="data-table" cellpadding="3">
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
                                <td align="center">{{ number_format($prov['allocated']) }}</td>
                                <td align="center" style="color:#0056D2; font-weight:bold;">{{ number_format($prov['activated']) }}</td>
                                <td align="center">{{ $share }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" align="center" style="color:#64748b; padding:8px;">No network sales recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Territory & Downline Breakdown Table -->
    <div class="section-header">Territory &amp; Downline Breakdown</div>
    <table class="data-table" cellpadding="3.5">
        <thead>
            <tr>
                <th width="24%"><b>Territory / Coverage</b></th>
                <th width="14%"><b>Level / Type</b></th>
                <th width="17%"><b>Assigned Lead</b></th>
                <th width="12%"><b>Contact Phone</b></th>
                <th width="7%" align="center"><b>Alloc.</b></th>
                <th width="7%" align="center"><b>Active</b></th>
                <th width="10%" align="right"><b>Volume (NGN)</b></th>
                <th width="5%" align="center"><b>Conv. %</b></th>
                <th width="4%" align="center"><b>Pend.</b></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jurisdictionRows as $row)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td><b>{{ $row['jurisdiction'] }}</b></td>
                    <td>{{ $row['type'] }}</td>
                    <td>{{ $row['lead_name'] }}</td>
                    <td>{{ $row['lead_phone'] }}</td>
                    <td align="center">{{ number_format($row['allocated']) }}</td>
                    <td align="center" style="color:#0056D2; font-weight:bold;">{{ number_format($row['activated']) }}</td>
                    <td align="right" style="color:#059669; font-weight:bold;">{{ number_format($row['sales_volume'], 2) }}</td>
                    <td align="center">{{ $row['conversion_rate'] }}%</td>
                    <td align="center" style="color:#d97706;">{{ number_format($row['pending']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" align="center" style="color:#64748b; padding:8px;">No territory breakdown data available.</td>
                </tr>
            @endforelse

            @if (!empty($jurisdictionRows))
                @php $t = $jurisdictionTotals; @endphp
                <tr class="totals-row">
                    <td colspan="4"><b>TOTAL PORTFOLIO SUMMARY</b></td>
                    <td align="center"><b>{{ number_format($t['allocated']) }}</b></td>
                    <td align="center" style="color:#0056D2;"><b>{{ number_format($t['activated']) }}</b></td>
                    <td align="right" style="color:#059669;"><b>{{ number_format($t['sales_volume'], 2) }}</b></td>
                    <td align="center"><b>{{ $t['conversion_rate'] }}%</b></td>
                    <td align="center" style="color:#d97706;"><b>{{ number_format($t['pending']) }}</b></td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Itemized Sales Records -->
    <div class="section-header">Itemized Activated SIMs (Recent Sales Records)</div>
    <table class="data-table" cellpadding="3">
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
                    <td align="center">{{ strtoupper($sim->provider) }}</td>
                    <td>{{ $customerName }}</td>
                    <td>{{ $customerPhone }}</td>
                    <td>{{ $activatedDate }}</td>
                    <td>{{ $activatedBy }}</td>
                    <td>{{ $jurisdiction }}</td>
                    <td align="right" style="color:#059669; font-weight:bold;">{{ number_format($price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" align="center" style="color:#64748b; padding:8px;">No activated SIM sales found in this period matching the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($activatedSimsList->count() > $displayLimit)
        <p style="font-size:7pt; color:#64748b; text-align:center; margin-top:4px;">
            Showing first {{ $displayLimit }} of {{ number_format($activatedSimsList->count()) }} activated SIM records. Export as CSV for the full unrestricted dataset.
        </p>
    @endif

</body>
</html>
