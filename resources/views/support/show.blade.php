<x-app-layout>
    <title>SimPhix - Ticket #{{ $ticket->id }}</title>

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

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('support') }}" 
                   class="w-10 h-10 rounded-2xl bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-all shadow-sm">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2">
                        Ticket #{{ $ticket->id }} — {{ $ticket->subject }}
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">Submitted on {{ $ticket->created_at->format('M d, Y \a\t h:i A') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border {{ $ticket->status_badge }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ ucfirst($ticket->status) }}
                </span>
                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold border {{ $ticket->priority_badge }}">
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
                                    SP
                                </div>
                                <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></span>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">Dedicated Support Agent</h4>
                                <span class="text-xs text-slate-400 font-semibold block">Usually replies within minutes</span>
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
                                <!-- Admin Message (Left) -->
                                <div class="flex items-end gap-3 max-w-[85%] animate-in slide-in-from-left duration-250">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#0056D2] to-[#0049b8] text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0 mb-1">
                                        AD
                                    </div>
                                    <div class="space-y-1">
                                        <div class="bg-slate-100 text-slate-800 rounded-3xl rounded-bl-none px-4 py-3 shadow-sm border border-slate-200/50 text-sm whitespace-pre-wrap leading-relaxed">
                                            {{ $msg->message }}
                                        </div>
                                        <div class="flex items-center gap-1.5 pl-1">
                                            <span class="text-xs font-bold text-slate-400">Agent</span>
                                            <span class="text-xs text-slate-300 font-semibold">•</span>
                                            <span class="text-xs text-slate-400 font-semibold">
                                                {{ $msg->created_at->format('h:i A') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- User Message (Right) -->
                                <div class="flex items-end gap-3 max-w-[85%] ml-auto flex-row-reverse animate-in slide-in-from-right duration-250">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-[#0056D2] flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0 mb-1">
                                        {{ substr($ticket->user->first_name, 0, 1) }}{{ substr($ticket->user->last_name, 0, 1) }}
                                    </div>
                                    <div class="space-y-1 text-right">
                                        <div class="bg-gradient-to-br from-[#0056D2] to-[#5a6eab] text-white rounded-3xl rounded-br-none px-4 py-3 shadow-md text-sm text-left whitespace-pre-wrap leading-relaxed">
                                            {{ $msg->message }}
                                        </div>
                                        <span class="block text-xs text-slate-400 font-semibold pr-1">
                                            Me • {{ $msg->created_at->format('h:i A') }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Message Reply Form -->
                    <div class="p-6 bg-slate-50/50 border-t border-slate-100">
                        @if($ticket->status === \App\Models\Ticket::STATUS_CLOSED)
                            <div class="p-4 bg-amber-50 border border-amber-100 rounded-2xl text-amber-800 text-xs font-semibold text-center mb-4 flex items-center justify-center gap-2">
                                <i data-lucide="info" class="w-4 h-4 text-amber-500 shrink-0"></i>
                                <span>This support ticket has been closed. Sending a reply will automatically reopen this ticket.</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('support.reply', $ticket) }}" class="space-y-4">
                            @csrf
                            <div class="relative">
                                <x-textarea-input name="message" rows="3" required
                                          class="rounded-2xl py-3.5 shadow-inner"
                                          placeholder="Type your response to support here..." />
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400 font-semibold flex items-center gap-1">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    Secure Support Thread
                                </span>
                                <x-primary-button type="submit">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                    <span>Send Reply</span>
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            <!-- Right Column: Sidebar Details -->
            <div class="lg:col-span-4 flex flex-col gap-6">
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
                            <span class="text-xs text-slate-400 font-medium mt-0.5 block">Assigned to official support team</span>
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
                            <span class="text-xs text-slate-400 font-medium mt-0.5 block">Official resolution provided</span>
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
                            <span class="text-xs text-slate-400 font-medium mt-0.5 block">Ticket resolved and archived</span>
                        </div>
                    </div>
                </div>

                <!-- Ticket Details Sidebar Card -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50">Ticket Summary</h3>

                    <div class="space-y-4">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Category</span>
                            <span class="font-bold text-sm text-slate-800 mt-0.5 block capitalize">{{ $ticket->category }}</span>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Last Activity</span>
                            <span class="font-semibold text-xs text-slate-600 mt-0.5 block">{{ $ticket->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-50">
                        <h4 class="font-bold text-xs text-slate-800 mb-2">Need immediate assistance?</h4>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">You can reach out to our hotlines directly via WhatsApp or phone call.</p>
                        
                        <div class="space-y-2">
                            <a href="https://wa.me/2347048932365" target="_blank"
                               class="flex items-center justify-center gap-2 py-2.5 px-4 bg-emerald-50 border border-emerald-100 hover:bg-emerald-100/50 text-emerald-700 font-bold text-xs rounded-xl transition-all">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.725 1.451 5.486.002 9.95-4.437 9.954-9.899.002-2.647-1.03-5.132-2.903-7.005C16.552 1.826 14.07 .797 11.44 .797c-5.49 0-9.958 4.437-9.96 9.898-.001 1.702.469 3.361 1.36 4.816L1.81 21.17l5.837-1.53M18.847 15.3c-.31-.155-1.837-.905-2.115-1.006-.279-.101-.482-.152-.684.152-.202.304-.785.987-.962 1.189-.177.202-.355.228-.666.073-1.096-.548-1.825-1.015-2.548-2.257-.193-.332.193-.309.553-1.026.061-.122.03-.228-.015-.304-.045-.076-.482-1.162-.66-1.593-.173-.418-.349-.36-.482-.367-.125-.007-.27-.008-.415-.008-.146 0-.383.055-.584.275-.202.22-1.77 1.728-1.77 4.212s1.808 4.88 2.06 5.22c.253.34 3.563 5.44 8.63 7.636 1.206.52 2.148.83 2.88 1.062 1.213.385 2.316.33 3.19.2.975-.145 3.011-1.23 3.431-2.42.42-1.19.42-2.21.295-2.42-.125-.21-.462-.31-.772-.465z"/></svg>
                                WhatsApp Support
                            </a>
                            <a href="tel:+2347048932365"
                               class="flex items-center justify-center gap-2 py-2.5 px-4 bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-xl transition-all">
                                <i data-lucide="phone-call" class="w-4 h-4 text-slate-500"></i>
                                Call Support Line
                            </a>
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
