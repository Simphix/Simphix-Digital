<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Support-desk ticket triage — same underlying Ticket/TicketMessage data
 * and mail notifications as Admin\SupportController, reachable by the
 * `support` role without the rest of the super_admin console. Renders the
 * existing admin.manage.support views (see routes/web.php for the
 * `support-desk.*` route names those views branch on).
 */
class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ticket::with(['user', 'messages']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        $totalTickets    = Ticket::count();
        $openTickets     = Ticket::where('status', Ticket::STATUS_OPEN)->count();
        $respondedTickets = Ticket::where('status', Ticket::STATUS_RESPONDED)->count();
        $closedTickets   = Ticket::where('status', Ticket::STATUS_CLOSED)->count();

        return view('admin.manage.support.index', compact(
            'tickets',
            'totalTickets',
            'openTickets',
            'respondedTickets',
            'closedTickets'
        ));
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['messages.user', 'user']);

        return view('admin.manage.support.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::beginTransaction();
        try {
            $ticketMessage = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id'   => Auth::id(),
                'message'   => $request->message,
                'is_admin'  => true,
            ]);

            $ticket->status = Ticket::STATUS_RESPONDED;
            $ticket->save();

            DB::commit();

            try {
                \Illuminate\Support\Facades\Mail::to($ticket->user->email)->send(new \App\Mail\AdminRepliedMail($ticket, $ticketMessage));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send AdminRepliedMail for Ticket #' . $ticket->id . ': ' . $e->getMessage());
            }

            return redirect()->back()->with('success', 'Support ticket reply sent successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to send reply. Please try again.');
        }
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:open,responded,closed'],
        ]);

        $ticket->status = $request->status;
        $ticket->save();

        $action = match ($request->status) {
            Ticket::STATUS_CLOSED => 'closed',
            Ticket::STATUS_OPEN => 'reopened',
            default => 'updated',
        };

        if ($request->status === Ticket::STATUS_CLOSED) {
            try {
                \Illuminate\Support\Facades\Mail::to($ticket->user->email)->send(new \App\Mail\TicketClosedMail($ticket));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send TicketClosedMail for Ticket #' . $ticket->id . ': ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Ticket #' . $ticket->id . ' status has been successfully ' . $action . '.');
    }
}
