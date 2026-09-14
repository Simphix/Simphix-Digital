<x-app-layout>
    @php
        // Shared by both the super_admin console (admin.manage.support.*)
        // and the support-desk console (support-desk.tickets.*).
        $onSupportDesk = request()->routeIs('support-desk.*');
        $ticketsIndexRoute = $onSupportDesk ? 'support-desk.tickets.index' : 'admin.manage.support.index';
        $ticketReplyRoute = $onSupportDesk ? 'support-desk.tickets.reply' : 'admin.manage.support.reply';
        $ticketStatusRoute = $onSupportDesk ? 'support-desk.tickets.status' : 'admin.manage.support.status';
        $userShowRoute = $onSupportDesk ? 'support-desk.users.show' : 'admin.manage.users.show';
    @endphp
    @push('styles')
    <style>
        /* Modern thin scrollbar styling */
        .chat-scroll-area::-webkit-scrollbar {
            width: 5px;
        }
        .chat-scroll-area::-webkit-scrollbar-track {
            background: transparent;
        }
        .chat-scroll-area::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .chat-scroll-area::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    @endpush

    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route($ticketsIndexRoute) }}"
                   class="w-10 h-10 rounded-2xl bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-all shadow-sm">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2">
                        Ticket #{{ $ticket->id }} — {{ $ticket->subject }}
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">Submitted by {{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $ticket->status_badge }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ ucfirst($ticket->status) }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $ticket->priority_badge }}">
                    {{ ucfirst($ticket->priority) }} Priority
                </span>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            
            <!-- Left/Main Column: Chat Conversation Stream -->
            <div class="lg:col-span-8 flex flex-col">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col h-[680px] overflow-hidden">
                    
                    <!-- Chat Header Mockup -->
                    <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-[#0056D2] flex items-center justify-center font-extrabold text-sm uppercase">
                                    {{ substr($ticket->user->first_name, 0, 1) }}{{ substr($ticket->user->last_name, 0, 1) }}
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</h4>
                                <span class="text-xs text-slate-400 font-semibold block">{{ $ticket->user->email }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-indigo-50 border border-indigo-100/50 rounded-lg text-xs font-bold text-[#0056D2] uppercase tracking-wider flex items-center gap-1.5">
                                <i data-lucide="tag" class="w-3 h-3"></i>
                                {{ $ticket->category }}
                            </span>
                        </div>
                    </div>

                    <!-- Chat Scroll Window -->
                    <div class="space-y-6 flex-grow overflow-y-auto p-6 chat-scroll-area flex-1 h-0" id="messageContainer">
                        @php $lastDate = null; @endphp
                        @foreach($ticket->messages as $msg)
                            {{-- Day Separator --}}
                            @php $msgDate = $msg->created_at->format('F d, Y'); @endphp
                            @if($msgDate !== $lastDate)
                                <div class="flex justify-center my-4">
                                    <span class="px-3.5 py-1 bg-slate-100/80 text-slate-500 text-xs font-extrabold rounded-full uppercase tracking-wider border border-slate-200/30 shadow-sm">
                                        {{ $msg->created_at->isToday() ? 'Today' : ($msg->created_at->isYesterday() ? 'Yesterday' : $msgDate) }}
                                    </span>
                                </div>
                                @php $lastDate = $msgDate; @endphp
                            @endif

                            @if($msg->is_admin)
                                <!-- Admin Reply (Right) -->
                                <div class="flex items-end gap-3 max-w-[85%] ml-auto flex-row-reverse animate-in slide-in-from-right duration-250">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#0056D2] to-[#0049b8] text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0 mb-1">
                                        AD
                                    </div>
                                    <div class="space-y-1 text-right">
                                        <div class="flex items-center gap-2 justify-end">
                                            <span class="text-xs font-bold text-slate-800">Support Agent (Me)</span>
                                        </div>
                                        <div class="bg-gradient-to-br from-[#0056D2] to-[#5a6eab] text-white rounded-3xl rounded-tr-none px-4 py-3 shadow-md text-sm text-left whitespace-pre-wrap leading-relaxed">
                                            {{ $msg->message }}
                                        </div>
                                        <span class="block text-xs text-slate-400 font-semibold pr-1">
                                            Admin • {{ $msg->created_at->format('h:i A') }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <!-- User Message (Left) -->
                                <div class="flex items-end gap-3 max-w-[85%] animate-in slide-in-from-left duration-250">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-[#0056D2] flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0 mb-1">
                                        {{ substr($ticket->user->first_name, 0, 1) }}{{ substr($ticket->user->last_name, 0, 1) }}
                                    </div>
                                    <div class="space-y-1">
                                        <div class="bg-slate-100 text-slate-800 rounded-3xl rounded-tl-none px-4 py-3 shadow-sm border border-slate-200/50 text-sm whitespace-pre-wrap leading-relaxed">
                                            {{ $msg->message }}
                                        </div>
                                        <div class="flex items-center gap-1.5 pl-1">
                                            <span class="text-xs font-bold text-slate-400">Customer</span>
                                            <span class="text-xs text-slate-300 font-semibold">•</span>
                                            <span class="text-xs text-slate-400 font-semibold">
                                                {{ $msg->created_at->format('h:i A') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Message Reply Form -->
                    <div class="p-6 bg-slate-50/50 border-t border-slate-100">
                        @if($ticket->status === \App\Models\Ticket::STATUS_CLOSED)
                            <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-800 text-xs font-semibold text-center mb-4">
                                This ticket is closed. You can reply or reopen it using the actions sidebar.
                            </div>
                        @endif

                        <form method="POST" action="{{ route($ticketReplyRoute, $ticket) }}" class="space-y-4">
                            @csrf
                            <div class="relative">
                                <x-textarea-input name="message" rows="3" required
                                          class="rounded-2xl shadow-inner"
                                          placeholder="Type your official administrative response here..." />
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400 font-semibold">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500 inline mr-1"></i>
                                    Secure Staff Channel
                                </span>
                                <x-primary-button type="submit" class="!bg-emerald-600 hover:!bg-emerald-700">
                                    <i data-lucide="reply" class="w-4 h-4"></i>
                                    <span>Send Agent Reply</span>
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            <!-- Right Column: Sidebar Details & Customer Card -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                <!-- Customer Details Card -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50">Customer Profile</h3>

                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 text-[#0056D2] flex items-center justify-center font-extrabold text-sm uppercase">
                            {{ substr($ticket->user->first_name, 0, 1) }}{{ substr($ticket->user->last_name, 0, 1) }}
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-800">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</h4>
                            <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 rounded-full text-xs font-extrabold uppercase border border-slate-200 mt-1.5 inline-block">
                                {{ $ticket->user->role }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-3.5 text-xs">
                        <div class="flex justify-between">
                            <span class="font-semibold text-slate-400">Email Address</span>
                            <span class="font-bold text-slate-700">{{ $ticket->user->email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold text-slate-400">Phone Number</span>
                            <span class="font-bold text-slate-700">{{ $ticket->user->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold text-slate-400">Wallet Balance</span>
                            <span class="font-bold text-emerald-600">₦{{ number_format($ticket->user->wallet->balance ?? 0, 2) }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-50">
                        <a href="{{ route($userShowRoute, $ticket->user) }}" target="_blank"
                           class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-xl transition-all">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400"></i>
                            View Complete Profile
                        </a>
                    </div>
                </div>

                <!-- Lifecycle Progress Tracker -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50">Ticket Lifecycle</h3>
                    
                    <div class="space-y-6 relative pl-6 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
                        {{-- Step 1 --}}
                        <div class="relative">
                            <span class="absolute -left-6 top-0.5 w-6 h-6 rounded-full bg-emerald-500 border-4 border-white flex items-center justify-center shadow-sm">
                                <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                            </span>
                            <span class="block text-xs font-bold text-slate-700">Ticket Created</span>
                            <span class="text-xs text-slate-400 font-medium mt-0.5 block">{{ $ticket->created_at->format('M d, h:i A') }}</span>
                        </div>

                        {{-- Step 2 --}}
                        <div class="relative">
                            @php
                                $reviewed = ($ticket->status !== \App\Models\Ticket::STATUS_OPEN || $ticket->messages->count() > 1);
                            @endphp
                            <span class="absolute -left-6 top-0.5 w-6 h-6 rounded-full border-4 border-white flex items-center justify-center shadow-sm {{ $reviewed ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                @if($reviewed)
                                    <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                @endif
                            </span>
                            <span class="block text-xs font-bold {{ $reviewed ? 'text-slate-700' : 'text-slate-400' }}">Under Review</span>
                            <span class="text-xs text-slate-400 font-medium mt-0.5 block">Assigned to support team</span>
                        </div>

                        {{-- Step 3 --}}
                        <div class="relative">
                            @php
                                $responded = $ticket->status === \App\Models\Ticket::STATUS_RESPONDED || $ticket->messages->where('is_admin', true)->isNotEmpty();
                            @endphp
                            <span class="absolute -left-6 top-0.5 w-6 h-6 rounded-full border-4 border-white flex items-center justify-center shadow-sm {{ $responded ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                @if($responded)
                                    <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                @endif
                            </span>
                            <span class="block text-xs font-bold {{ $responded ? 'text-slate-700' : 'text-slate-400' }}">Agent Responded</span>
                            <span class="text-xs text-slate-400 font-medium mt-0.5 block">Response message sent</span>
                        </div>

                        {{-- Step 4 --}}
                        <div class="relative">
                            @php
                                $closed = $ticket->status === \App\Models\Ticket::STATUS_CLOSED;
                            @endphp
                            <span class="absolute -left-6 top-0.5 w-6 h-6 rounded-full border-4 border-white flex items-center justify-center shadow-sm {{ $closed ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                @if($closed)
                                    <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                @endif
                            </span>
                            <span class="block text-xs font-bold {{ $closed ? 'text-slate-700' : 'text-slate-400' }}">Resolved / Closed</span>
                            <span class="text-xs text-slate-400 font-medium mt-0.5 block">Closed by admin or user</span>
                        </div>
                    </div>
                </div>

                <!-- Action Panel Card -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50">Ticket Actions</h3>

                    <div class="space-y-4">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Change Status</span>
                            
                            @if($ticket->status !== \App\Models\Ticket::STATUS_CLOSED)
                                <form method="POST" action="{{ route($ticketStatusRoute, $ticket) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="closed">
                                    <button type="submit"
                                            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-rose-50 border border-rose-100 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl transition-all">
                                        <i data-lucide="archive" class="w-4 h-4"></i>
                                        Close Ticket
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route($ticketStatusRoute, $ticket) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="open">
                                    <button type="submit"
                                            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-emerald-50 border border-emerald-100 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-xl transition-all">
                                        <i data-lucide="folder-open" class="w-4 h-4"></i>
                                        Re-open Ticket
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto scroll chat to the bottom
            const container = document.getElementById('messageContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    </script>
    @endpush
</x-app-layout>
