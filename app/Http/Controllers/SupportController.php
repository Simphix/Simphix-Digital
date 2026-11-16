<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupportController extends Controller
{
    /**
     * Display a listing of user support tickets and new ticket form.
     */
    public function index(): View
    {
        $tickets = Ticket::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('support.index', compact('tickets'));
    }

    /**
     * Store a newly created support ticket and its first message.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'subject'  => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:technical,billing,general,upgrade'],
            'priority' => ['required', 'string', 'in:low,medium,high'],
            'message'  => ['required', 'string', 'max:5000'],
        ]);

        DB::beginTransaction();
        try {
            $ticket = Ticket::create([
                'user_id'  => Auth::id(),
                'subject'  => $request->subject,
                'category' => $request->category,
                'priority' => $request->priority,
                'status'   => Ticket::STATUS_OPEN,
            ]);

            $ticketMessage = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id'   => Auth::id(),
                'message'   => $request->message,
                'is_admin'  => false,
            ]);

            DB::commit();

            try {
                \Illuminate\Support\Facades\Mail::to('support@simphiix.com')->send(new \App\Mail\AdminNotificationMail($ticket, $ticketMessage, 'created'));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send admin ticket created notification: ' . $e->getMessage());
            }

            return redirect()->route('support.show', $ticket)
                ->with('success', 'Support ticket #' . $ticket->id . ' has been created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create support ticket. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified ticket conversation thread.
     */
    public function show(Ticket $ticket): View
    {
        abort_if($ticket->user_id !== Auth::id(), 403, 'Unauthorized access to this ticket.');

        $ticket->load(['messages.user', 'user']);

        return view('support.show', compact('ticket'));
    }

    /**
     * Post a reply to the specified ticket conversation.
     */
    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_if($ticket->user_id !== Auth::id(), 403, 'Unauthorized access to this ticket.');

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::beginTransaction();
        try {
            $ticketMessage = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id'   => Auth::id(),
                'message'   => $request->message,
                'is_admin'  => false,
            ]);

            // Re-open ticket if closed, or keep open (waiting for admin response)
            $ticket->status = Ticket::STATUS_OPEN;
            $ticket->save();

            DB::commit();

            try {
                \Illuminate\Support\Facades\Mail::to('support@simphiix.com')->send(new \App\Mail\AdminNotificationMail($ticket, $ticketMessage, 'replied'));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send admin ticket reply notification: ' . $e->getMessage());
            }

            return redirect()->back()->with('success', 'Your reply has been posted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to post reply. Please try again.');
        }
    }
}
