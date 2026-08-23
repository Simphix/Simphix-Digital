<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentWebhookController;
use App\Models\WebhookLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $failedCount = WebhookLog::where('status', 'failed')->count();

        return view('admin.webhooks.index', compact('webhookLogs', 'totalCount', 'successCount', 'failedCount'));
    }

    /**
     * Re-run a previously-failed webhook's stored payload through the exact
     * same processing path a live webhook uses. Safe to re-run: the
     * underlying transaction-ref check in PaymentWebhookController already
     * skips anything already completed, so this can only finish what an
     * earlier attempt failed to, never double-credit a wallet.
     */
    public function retry(WebhookLog $webhookLog): RedirectResponse
    {
        if ($webhookLog->status !== 'failed') {
            return back()->with('error', 'Only failed webhook attempts can be retried.');
        }

        app(PaymentWebhookController::class)->processAndLog($webhookLog->payload, $webhookLog);

        return $webhookLog->fresh()->status === 'success'
            ? back()->with('success', "Webhook #{$webhookLog->id} retried successfully.")
            : back()->with('error', "Retry failed: {$webhookLog->fresh()->error_message}");
    }
}
