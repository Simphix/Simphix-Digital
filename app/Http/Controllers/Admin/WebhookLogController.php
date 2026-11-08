<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FintavaWebhookController;
use App\Http\Controllers\PaymentWebhookController;
use App\Models\WebhookLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WebhookLogController extends Controller
{
    /**
     * List every recorded webhook attempt (across all providers), so a
     * failure is something an admin can see and act on instead of only
     * existing as a line in the Laravel log.
     */
    public function index(Request $request): View
    {
        $query = WebhookLog::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        $webhookLogs = $query->latest()->paginate(15)->appends($request->query());

        $totalCount = WebhookLog::count();
        $successCount = WebhookLog::where('status', 'success')->count();
        // Unmatched deposits are money nobody was credited for, so they count here too.
        $failedCount = WebhookLog::whereIn('status', ['failed', 'unmatched'])->count();

        return view('admin.webhooks.index', compact('webhookLogs', 'totalCount', 'successCount', 'failedCount'));
    }

    /**
     * Re-run a previously-failed webhook's stored payload through the exact
     * same processing path a live webhook uses. Safe to re-run: the
     * underlying idempotency checks in each provider's processAndLog()
     * already skip anything already completed, so this can only finish what
     * an earlier attempt failed to, never double-credit a wallet.
     *
     * Provider-aware: 'fintava' rows (current provider) go through
     * FintavaWebhookController; older 'palmpay' historical rows still go
     * through the now-dormant PaymentWebhookController (no live route left,
     * but the class stays so a decommissioned-vendor log can still be
     * manually replayed for record-keeping if ever needed).
     */
    public function retry(Request $request, WebhookLog $webhookLog): RedirectResponse
    {
        if ($webhookLog->status === 'invalid_signature') {
            // The signature was never proven, so this payload may be forged and
            // replaying it can credit real money. It needs an admin to state
            // they confirmed the event in the Fintava dashboard, and only
            // Fintava rows qualify (the only provider whose secret can be wrong).
            if ($webhookLog->provider !== 'fintava') {
                return back()->with('error', 'Only Fintava webhooks rejected for their signature can be retried.');
            }

            if (! $request->boolean('confirm_verified')) {
                return back()->with('error', 'Confirm you have verified this event in the Fintava dashboard before retrying a webhook with an invalid signature.');
            }

            Log::warning('Admin retried a Fintava webhook that failed signature verification', [
                'webhook_log_id' => $webhookLog->id,
                'event' => $webhookLog->event_type,
                'admin_id' => $request->user()?->id,
            ]);
        } elseif (! in_array($webhookLog->status, ['failed', 'unmatched'], true)) {
            return back()->with('error', 'Only failed, unmatched or invalid-signature webhook attempts can be retried.');
        }

        if (!in_array($webhookLog->provider, ['fintava', 'palmpay'], true)) {
            return back()->with('error', "No retry handler registered for provider '{$webhookLog->provider}'.");
        }

        match ($webhookLog->provider) {
            'fintava' => app(FintavaWebhookController::class)->processAndLog($webhookLog->payload, $webhookLog),
            'palmpay' => app(PaymentWebhookController::class)->processAndLog($webhookLog->payload, $webhookLog),
        };

        return $webhookLog->fresh()->status === 'success'
            ? back()->with('success', "Webhook #{$webhookLog->id} retried successfully.")
            : back()->with('error', "Retry failed: {$webhookLog->fresh()->error_message}");
    }
}
