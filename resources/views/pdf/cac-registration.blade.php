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
                <div class="brand-sub">Corporate Affairs Commission (CAC) Business Registration Desk</div>
                <div style="font-size: 11pt; font-weight: bold; color: #0f172a; margin-top: 5px;">
                    CAC BUSINESS REGISTRATION DOSSIER
                </div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="ref-box">
                    <div style="font-size: 7.5pt; color: #64748b; text-transform: uppercase;">Filing Reference</div>
                    <div class="ref-code">{{ $registration->reference }}</div>
                    <div style="font-size: 8pt; color: #0f172a; margin-top: 3px;">
                        Category: <strong>{{ $registration->category_label }}</strong>
                    </div>
                    <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">
                        Submitted: {{ $registration->submitted_at ? $registration->submitted_at->format('M d, Y · h:i A') : $registration->created_at->format('M d, Y') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Filing Overview -->
    <div class="section-title">1. FILING INFORMATION</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell" style="width: 25%;">Registration Status</td>
            <td class="value-cell" style="width: 25%;"><strong>{{ $registration->status_label }}</strong></td>
            <td class="label-cell" style="width: 25%;">Service Fee Paid</td>
            <td class="value-cell" style="width: 25%;"><strong>₦{{ number_format((float) $registration->amount, 2) }}</strong></td>
        </tr>
        <tr>
            <td class="label-cell">Payment Transaction Ref</td>
            <td class="value-cell" colspan="3" style="font-family: courier, monospace;">{{ $registration->transaction_ref ?? '—' }}</td>
        </tr>
    </table>

    <!-- Applicant / User Info -->
    <div class="section-title">2. APPLICANT DETAILS</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell" style="width: 25%;">Applicant Name</td>
            <td class="value-cell" style="width: 25%;"><strong>{{ $registration->user?->name ?? '—' }}</strong></td>
            <td class="label-cell" style="width: 25%;">Phone Number</td>
            <td class="value-cell" style="width: 25%; font-family: courier, monospace;">{{ $registration->user?->phone ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Email Address</td>
            <td class="value-cell" colspan="3">{{ $registration->user?->email ?? '—' }}</td>
        </tr>
    </table>

    <!-- Form Data / Proposed Business Details -->
    @if ($registration->form_data && is_array($registration->form_data))
        <div class="section-title">3. SUBMITTED REGISTRATION PARTICULARS</div>
        <table class="data-table" cellpadding="0" cellspacing="0">
            @foreach ($registration->form_data as $field => $val)
                @if (is_scalar($val) && !empty($val))
                    <tr>
                        <td class="label-cell">{{ ucwords(str_replace(['_', '-'], ' ', $field)) }}</td>
                        <td class="value-cell">{{ $val }}</td>
                    </tr>
                @endif
            @endforeach
        </table>
    @endif

    <!-- Uploaded Documents Checklist -->
    <div class="section-title">4. ATTACHED DOCUMENTS &amp; DELIVERABLES</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr style="background-color: #f1f5f9;">
                <td style="font-weight: bold; width: 40%;">Document Label</td>
                <td style="font-weight: bold; width: 35%;">File Name</td>
                <td style="font-weight: bold; width: 25%;">Uploader</td>
            </tr>
        </thead>
        <tbody>
            @forelse ($registration->documents as $doc)
                <tr>
                    <td>{{ $doc->label }}</td>
                    <td style="font-family: courier, monospace; font-size: 8pt;">{{ $doc->original_name }}</td>
                    <td>{{ ucfirst($doc->uploaded_by) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: #94a3b8;">No documents recorded for this filing.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        SimPhix CAC Corporate Filing System · Generated on {{ now()->format('Y-m-d H:i:s') }} WAT · Reference: {{ $registration->reference }}
    </div>

</body>
</html>
