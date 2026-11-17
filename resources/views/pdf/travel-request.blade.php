<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: helvetica, sans-serif; font-size: 9pt; color: #1e293b; line-height: 1.4; }
        .header-table { width: 100%; border-bottom: 2px solid #0056D2; padding-bottom: 10px; margin-bottom: 12px; }
        .brand-title { font-size: 16pt; font-weight: bold; color: #0056D2; margin: 0; }
        .brand-sub { font-size: 8pt; color: #64748b; margin: 0; }
        .ref-box { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right; }
        .ref-code { font-family: courier, monospace; font-size: 11pt; font-weight: bold; color: #0f172a; }
        
        .section-title { font-size: 10pt; font-weight: bold; color: #0056D2; background-color: #f8fafc; border-left: 3px solid #0056D2; padding: 4px 8px; margin-top: 10px; margin-bottom: 6px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .data-table td { padding: 4px 6px; border: 1px solid #e2e8f0; font-size: 8.5pt; }
        .label-cell { background-color: #f8fafc; font-weight: bold; color: #475569; width: 28%; }
        .value-cell { background-color: #ffffff; color: #0f172a; width: 72%; }
        
        .footer-note { font-size: 7.5pt; color: #64748b; text-align: center; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 6px; }
    </style>
</head>
<body>

    <!-- Header Block -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 60%;">
                <div class="brand-title">SimPhix Digital Services</div>
                <div class="brand-sub">Travel &amp; Tours Desk · Holy Pilgrimages, Flights &amp; Vacation Packages</div>
                <div style="font-size: 11pt; font-weight: bold; color: #0f172a; margin-top: 5px;">
                    TRAVEL &amp; TOURS BOOKING DOSSIER
                </div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="ref-box">
                    <div style="font-size: 7.5pt; color: #64748b; text-transform: uppercase;">Booking Reference</div>
                    <div class="ref-code">{{ $request->reference }}</div>
                    <div style="font-size: 8pt; color: #0f172a; margin-top: 3px;">
                        Status: <strong>{{ strtoupper($request->status_label) }}</strong>
                    </div>
                    <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">
                        Date: {{ $request->created_at->format('M d, Y · h:i A') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Booking Overview -->
    <div class="section-title">1. PACKAGE &amp; TRAVEL DETAILS</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell">Package Name</td>
            <td class="value-cell" colspan="3"><strong>{{ $request->package_name }}</strong></td>
        </tr>
        <tr>
            <td class="label-cell" style="width: 25%;">Package Type</td>
            <td class="value-cell" style="width: 25%;">{{ ucfirst($request->package_type) }}</td>
            <td class="label-cell" style="width: 25%;">Travelers Count</td>
            <td class="value-cell" style="width: 25%;"><strong>{{ $request->number_of_travelers }} Traveler(s)</strong></td>
        </tr>
        <tr>
            <td class="label-cell">Target Departure Date</td>
            <td class="value-cell" colspan="3">{{ $request->departure_date ? \Carbon\Carbon::parse($request->departure_date)->format('M d, Y') : 'Flexible / To Be Scheduled' }}</td>
        </tr>
        @if ($request->notes)
            <tr>
                <td class="label-cell">Traveler Notes / Requests</td>
                <td class="value-cell" colspan="3">{{ $request->notes }}</td>
            </tr>
        @endif
    </table>

    <!-- Traveler Information -->
    <div class="section-title">2. PRIMARY CONTACT INFORMATION</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell" style="width: 25%;">Full Name</td>
            <td class="value-cell" style="width: 25%;"><strong>{{ $request->name }}</strong></td>
            <td class="label-cell" style="width: 25%;">Phone Number</td>
            <td class="value-cell" style="width: 25%; font-family: courier, monospace;">{{ $request->phone }}</td>
        </tr>
        <tr>
            <td class="label-cell">Email Address</td>
            <td class="value-cell" colspan="3">{{ $request->email }}</td>
        </tr>
        @if ($request->referral_name || $request->referral_phone)
            <tr>
                <td class="label-cell">Referred By</td>
                <td class="value-cell" colspan="3">{{ $request->referral_name }} ({{ $request->referral_phone }})</td>
            </tr>
        @endif
    </table>

    <!-- Processing & Audit Log -->
    <div class="section-title">3. ADMINISTRATIVE REVIEW &amp; STATUS</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell" style="width: 25%;">Current Status</td>
            <td class="value-cell" style="width: 25%;"><strong>{{ $request->status_label }}</strong></td>
            <td class="label-cell" style="width: 25%;">Contacted At</td>
            <td class="value-cell" style="width: 25%;">{{ $request->contacted_at ? $request->contacted_at->format('M d, Y · h:i A') : '—' }}</td>
        </tr>
        @if ($request->customer_notes)
            <tr>
                <td class="label-cell">Customer Communication</td>
                <td class="value-cell" colspan="3">{{ $request->customer_notes }}</td>
            </tr>
        @endif
        @if ($request->admin_notes)
            <tr>
                <td class="label-cell">Internal Desk Notes</td>
                <td class="value-cell" colspan="3">{{ $request->admin_notes }}</td>
            </tr>
        @endif
    </table>

    <div class="footer-note">
        SimPhix Travel &amp; Tours Booking System · Generated on {{ now()->format('Y-m-d H:i:s') }} WAT · Reference: {{ $request->reference }}
    </div>

</body>
</html>
