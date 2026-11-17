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
        .value-cell-half { background-color: #ffffff; color: #0f172a; width: 22%; }
        
        .status-badge { display: inline-block; padding: 3px 8px; font-weight: bold; font-size: 8pt; text-transform: uppercase; border-radius: 3px; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-approved, .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-processing { background-color: #e0e7ff; color: #3730a3; }
        .status-rejected { background-color: #ffe4e6; color: #9f1239; }
        
        .footer-note { font-size: 7.5pt; color: #64748b; text-align: center; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 6px; }
    </style>
</head>
<body>

    <!-- Header Block -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 60%;">
                <div class="brand-title">SimPhix Digital Services</div>
                <div class="brand-sub">Official Citizen Support &amp; e-Passport Application Processing Desk</div>
                <div style="font-size: 11pt; font-weight: bold; color: #0f172a; margin-top: 5px;">
                    APPLICATION FOR NIGERIAN INTERNATIONAL PASSPORT
                </div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="ref-box">
                    <div style="font-size: 7.5pt; color: #64748b; text-transform: uppercase;">Application Reference</div>
                    <div class="ref-code">{{ $request->reference }}</div>
                    <div style="font-size: 8pt; color: #0f172a; margin-top: 3px;">
                        Type: <strong>{{ strtoupper($request->application_type) }} PASSPORT</strong>
                    </div>
                    <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">
                        Date: {{ $request->created_at->format('M d, Y · h:i A') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Application Summary -->
    <div class="section-title">1. APPLICATION OVERVIEW &amp; STATUS</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell">Application Status</td>
            <td class="value-cell" style="width: 32%;">
                <strong>{{ $request->status_label }}</strong>
            </td>
            <td class="label-cell" style="width: 18%;">Application Type</td>
            <td class="value-cell" style="width: 22%;">
                <strong>{{ $request->application_type_label }}</strong>
            </td>
        </tr>
        @if ($request->application_type === 'renewal' && $request->previous_passport_number)
            <tr>
                <td class="label-cell">Previous Passport No.</td>
                <td class="value-cell" colspan="3">
                    <strong style="font-family: courier, monospace;">{{ $request->previous_passport_number }}</strong>
                </td>
            </tr>
        @endif
        @if ($request->capturing_center || $request->capturing_date)
            <tr>
                <td class="label-cell">NIS Capturing Center</td>
                <td class="value-cell" style="width: 32%;">
                    {{ $request->capturing_center ?? 'To Be Scheduled' }}
                </td>
                <td class="label-cell" style="width: 18%;">Capturing Date</td>
                <td class="value-cell" style="width: 22%;">
                    {{ $request->capturing_date ? $request->capturing_date->format('M d, Y · h:i A') : 'Pending Confirmation' }}
                </td>
            </tr>
        @endif
    </table>

    <!-- Applicant Personal Information (Exact Format requested) -->
    <div class="section-title">2. APPLICANT PERSONAL DATA</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell">National Identity Number (NIN)</td>
            <td class="value-cell" colspan="3" style="font-family: courier, monospace; font-weight: bold; font-size: 10pt;">
                {{ $request->nin }}
            </td>
        </tr>
        <tr>
            <td class="label-cell">Surname</td>
            <td class="value-cell" style="width: 32%;"><strong>{{ strtoupper($request->surname) }}</strong></td>
            <td class="label-cell" style="width: 18%;">First Name</td>
            <td class="value-cell" style="width: 22%;"><strong>{{ strtoupper($request->first_name) }}</strong></td>
        </tr>
        <tr>
            <td class="label-cell">Middle Name</td>
            <td class="value-cell" style="width: 32%;">{{ strtoupper($request->middle_name ?? '—') }}</td>
            <td class="label-cell" style="width: 18%;">Gender / Sex</td>
            <td class="value-cell" style="width: 22%;">{{ strtoupper($request->gender) }}</td>
        </tr>
        <tr>
            <td class="label-cell">Date of Birth</td>
            <td class="value-cell" style="width: 32%;">{{ $request->date_of_birth ? $request->date_of_birth->format('d/m/Y') : '—' }}</td>
            <td class="label-cell" style="width: 18%;">Place of Birth</td>
            <td class="value-cell" style="width: 22%;">{{ $request->place_of_birth }}</td>
        </tr>
        <tr>
            <td class="label-cell">State of Origin</td>
            <td class="value-cell" style="width: 32%;">{{ $request->state_of_origin }}</td>
            <td class="label-cell" style="width: 18%;">L.G.A.</td>
            <td class="value-cell" style="width: 22%;">{{ $request->lga }}</td>
        </tr>
        <tr>
            <td class="label-cell">Hometown</td>
            <td class="value-cell" style="width: 32%;">{{ $request->hometown }}</td>
            <td class="label-cell" style="width: 18%;">Occupation</td>
            <td class="value-cell" style="width: 22%;">{{ $request->occupation }}</td>
        </tr>
        <tr>
            <td class="label-cell">Mobile Number</td>
            <td class="value-cell" style="width: 32%; font-family: courier, monospace;">{{ $request->phone }}</td>
            <td class="label-cell" style="width: 18%;">E-mail Address</td>
            <td class="value-cell" style="width: 22%;">{{ $request->email }}</td>
        </tr>
        <tr>
            <td class="label-cell">Residential Address</td>
            <td class="value-cell" colspan="3">{{ $request->residential_address }}</td>
        </tr>
    </table>

    <!-- Next of Kin Information -->
    <div class="section-title">3. NEXT OF KIN DETAILS</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label-cell">Next of Kin Name</td>
            <td class="value-cell" style="width: 32%;"><strong>{{ $request->next_of_kin_name }}</strong></td>
            <td class="label-cell" style="width: 18%;">Relationship</td>
            <td class="value-cell" style="width: 22%;">{{ $request->next_of_kin_relationship }}</td>
        </tr>
        <tr>
            <td class="label-cell">Next of Kin Phone</td>
            <td class="value-cell" style="width: 32%; font-family: courier, monospace;">{{ $request->next_of_kin_phone }}</td>
            <td class="label-cell" style="width: 18%;">Address</td>
            <td class="value-cell" style="width: 22%;">{{ $request->next_of_kin_address }}</td>
        </tr>
    </table>

    <!-- Additional Uploaded Documents Checklist (E-COPY ONLY) -->
    <div class="section-title">4. ATTACHED DOCUMENTS CHECKLIST (E-COPY)</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 20%; text-align: center; background-color: #f8fafc;">
                <strong>Passport Photo</strong><br>
                <span style="font-size: 7.5pt; color: {{ $request->passport_photo_path ? '#16a34a' : '#dc2626' }};">
                    {{ $request->passport_photo_path ? '✓ ATTACHED' : '✗ MISSING' }}
                </span>
            </td>
            <td style="width: 20%; text-align: center; background-color: #f8fafc;">
                <strong>State of Origin Cert</strong><br>
                <span style="font-size: 7.5pt; color: {{ $request->state_origin_cert_path ? '#16a34a' : '#dc2626' }};">
                    {{ $request->state_origin_cert_path ? '✓ ATTACHED' : '✗ MISSING' }}
                </span>
            </td>
            <td style="width: 20%; text-align: center; background-color: #f8fafc;">
                <strong>Birth Certificate</strong><br>
                <span style="font-size: 7.5pt; color: {{ $request->birth_cert_path ? '#16a34a' : '#dc2626' }};">
                    {{ $request->birth_cert_path ? '✓ ATTACHED' : '✗ MISSING' }}
                </span>
            </td>
            <td style="width: 20%; text-align: center; background-color: #f8fafc;">
                <strong>NIN Document</strong><br>
                <span style="font-size: 7.5pt; color: {{ $request->nin_doc_path ? '#16a34a' : '#dc2626' }};">
                    {{ $request->nin_doc_path ? '✓ ATTACHED' : '✗ MISSING' }}
                </span>
            </td>
            <td style="width: 20%; text-align: center; background-color: #f8fafc;">
                <strong>Signature on Paper</strong><br>
                <span style="font-size: 7.5pt; color: {{ $request->signature_path ? '#16a34a' : '#dc2626' }};">
                    {{ $request->signature_path ? '✓ ATTACHED' : '✗ MISSING' }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Administrative Notes & Processing Trail -->
    @if ($request->admin_notes || $request->customer_notes)
        <div class="section-title">5. PROCESSING NOTES &amp; INSTRUCTIONS</div>
        <table class="data-table" cellpadding="0" cellspacing="0">
            @if ($request->customer_notes)
                <tr>
                    <td class="label-cell">Applicant Instructions</td>
                    <td class="value-cell">{{ $request->customer_notes }}</td>
                </tr>
            @endif
            @if ($request->admin_notes)
                <tr>
                    <td class="label-cell">Official Desk Notes</td>
                    <td class="value-cell">{{ $request->admin_notes }}</td>
                </tr>
            @endif
        </table>
    @endif

    <div class="footer-note">
        This document serves as an official electronic application dossier for Nigerian International Passport registration.<br>
        Generated on {{ now()->format('Y-m-d H:i:s') }} WAT · SimPhix Secure Platform · Verified Reference: {{ $request->reference }}
    </div>

</body>
</html>
