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
                <div class="brand-sub">Business Loans &amp; Working Capital Financing Desk</div>
                <div style="font-size: 11pt; font-weight: bold; color: #0f172a; margin-top: 5px;">
                    BUSINESS LOAN APPLICATION DOSSIER
                </div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="ref-box">
                    <div style="font-size: 7.5pt; color: #64748b; text-transform: uppercase;">Application Reference</div>
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

    <!-- Loan Details -->
    <div class="section-title">1. LOAN FACILITY DETAILS</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell" style="width: 25%;">Amount Requested</td>
            <td class="value-cell" style="width: 25%; font-size: 10pt; font-weight: bold; color: #0056D2;">
                ₦{{ number_format((float) $request->amount_requested, 2) }}
            </td>
            <td class="label-cell" style="width: 25%;">Requested Tenure</td>
            <td class="value-cell" style="width: 25%;"><strong>{{ $request->tenure_label ?? $request->loan_tenure }}</strong></td>
        </tr>
        <tr>
            <td class="label-cell">Moniepoint Account No.</td>
            <td class="value-cell" colspan="3" style="font-family: courier, monospace; font-weight: bold;">
                {{ $request->moniepoint_account_number }}
            </td>
        </tr>
        @if ($request->purpose)
            <tr>
                <td class="label-cell">Loan Purpose / Utilization</td>
                <td class="value-cell" colspan="3">{{ $request->purpose }}</td>
            </tr>
        @endif
    </table>

    <!-- Applicant / Business Information -->
    <div class="section-title">2. BORROWER &amp; BUSINESS PARTICULARS</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell" style="width: 25%;">Applicant Name</td>
            <td class="value-cell" style="width: 25%;"><strong>{{ $request->name }}</strong></td>
            <td class="label-cell" style="width: 25%;">Business Name</td>
            <td class="value-cell" style="width: 25%;"><strong>{{ $request->business_name }}</strong></td>
        </tr>
        <tr>
            <td class="label-cell" style="width: 25%;">Phone Number</td>
            <td class="value-cell" style="width: 25%; font-family: courier, monospace;">{{ $request->phone }}</td>
            <td class="label-cell" style="width: 25%;">Email Address</td>
            <td class="value-cell" style="width: 25%;">{{ $request->email }}</td>
        </tr>
        @if ($request->referral_name || $request->referral_phone)
            <tr>
                <td class="label-cell">Referral Partner</td>
                <td class="value-cell" colspan="3">{{ $request->referral_name }} ({{ $request->referral_phone }})</td>
            </tr>
        @endif
    </table>

    <!-- Review & Audit Log -->
    <div class="section-title">3. UNDERWRITING &amp; REVIEW AUDIT</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell" style="width: 25%;">Application Status</td>
            <td class="value-cell" style="width: 25%;"><strong>{{ $request->status_label }}</strong></td>
            <td class="label-cell" style="width: 25%;">Resolution Date</td>
            <td class="value-cell" style="width: 25%;">{{ $request->resolved_at ? $request->resolved_at->format('M d, Y · h:i A') : 'Pending' }}</td>
        </tr>
        @if ($request->customer_notes)
            <tr>
                <td class="label-cell">Borrower Instructions</td>
                <td class="value-cell" colspan="3">{{ $request->customer_notes }}</td>
            </tr>
        @endif
        @if ($request->admin_notes)
            <tr>
                <td class="label-cell">Credit Underwriter Notes</td>
                <td class="value-cell" colspan="3">{{ $request->admin_notes }}</td>
            </tr>
        @endif
    </table>

    <div class="footer-note">
        SimPhix Business Loan Processing System · Generated on {{ now()->format('Y-m-d H:i:s') }} WAT · Reference: {{ $request->reference }}
    </div>

</body>
</html>
