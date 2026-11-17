<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ActivatedSimsExportMail;
use App\Models\SimExportSettings;
use App\Services\ActivatedSimsExportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Admin-configurable on/off + recipient for the nightly "email every
 * activated SIM number to an inbox" job (see the `sims:export-activated`
 * console command and ActivatedSimsExportMail). Surfaced inline on the
 * SIMs Operations page — includes instant on-demand delivery testing.
 */
class ActivatedSimsExportSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $rawEmail = $request->input('recipient_email', '');
        $emails = array_values(array_filter(
            array_map('trim', preg_split('/[,;\s]+/', (string) $rawEmail))
        ));

        if (empty($emails)) {
            return back()->withErrors(['recipient_email' => 'At least one recipient email address is required.']);
        }

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['recipient_email' => "Invalid email address format: {$email}"]);
            }
        }

        SimExportSettings::current()->update([
            'recipient_email' => implode(', ', $emails),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Nightly activated-SIMs export settings updated successfully.');
    }

    public function sendNow(Request $request, ActivatedSimsExportService $exporter): RedirectResponse
    {
        $settings = SimExportSettings::current();
        $targetEmail = $request->input('recipient_email') ?: $settings->recipient_email;

        if (!$targetEmail) {
            return back()->with('error', 'No recipient email configured. Please enter an email address first.');
        }

        $recipients = array_values(array_filter(
            array_map('trim', preg_split('/[,;\s]+/', (string) $targetEmail)),
            fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        ));

        if (empty($recipients)) {
            return back()->with('error', 'Invalid recipient email address format.');
        }

        $period = $request->input('period', 'yesterday');
        $timezone = 'Africa/Lagos';

        if ($period === 'all') {
            $since = null;
            $until = null;
            $periodLabel = 'all-time';
            $reportDate = null;
        } elseif ($period === 'today') {
            $targetDate = now($timezone);
            $since = $targetDate->copy()->startOfDay()->setTimezone('UTC');
            $until = now()->setTimezone('UTC');
            $periodLabel = $targetDate->format('M d, Y') . ' (Today)';
            $reportDate = $targetDate;
        } elseif ($period === '24h') {
            $until = now();
            $since = now()->subHours(24);
            $periodLabel = 'past 24 hours';
            $reportDate = now($timezone);
        } else {
            // Default yesterday full calendar day
            $targetDate = now($timezone)->subDay();
            $since = $targetDate->copy()->startOfDay()->setTimezone('UTC');
            $until = $targetDate->copy()->endOfDay()->setTimezone('UTC');
            $periodLabel = $targetDate->format('M d, Y');
            $reportDate = $targetDate;
        }

        $count = $exporter->count($since, $until);
        $csv = $exporter->toCsvString($since, $until);

        try {
            Mail::to($recipients)->send(
                new ActivatedSimsExportMail($csv, $count, now($timezone), $since, $until, $periodLabel, $reportDate)
            );
        } catch (\Throwable $e) {
            Log::error('Manual activated-SIMs export failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to deliver export email: ' . $e->getMessage());
        }

        $recipientList = implode(', ', $recipients);
        return back()->with('success', "Activated SIMs export email ({$periodLabel}) successfully delivered to {$recipientList} ({$count} numbers).");
    }
}
