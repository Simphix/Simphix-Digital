<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only webhook log visibility for the support desk — every recorded
 * webhook attempt across all providers, so an agent can check whether a
 * payment/notification actually landed while investigating a ticket.
 * Deliberately no `retry` action here (unlike Admin\WebhookLogController) —
 * re-running a stored payload through payment processing is a financial
 * reconciliation action, out of scope for this console.
 */
class WebhookLogController extends Controller
{
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
}
