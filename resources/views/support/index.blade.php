<x-app-layout>
    <title>SimPhix - Support Center</title>

    @push('styles')
    <style>
        .channel-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
        }
        .channel-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -8px rgba(66, 81, 124, 0.12);
        }
        .category-option {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        .category-option.active {
            border-color: #0056D2 !important;
            background-color: rgb(243 244 246) !important;
            box-shadow: 0 0 0 2px rgba(66, 81, 124, 0.15) !important;
        }
        .priority-btn {
            transition: all 0.2s ease-in-out;
        }
        .priority-btn.active {
            background-color: #0056D2 !important;
            color: #ffffff !important;
            border-color: #0056D2 !important;
        }
    </style>
    @endpush

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm animate-pulse">
                        <i data-lucide="help-circle" class="w-5 h-5"></i>
                    </div>
                    Support Center
                </h1>
                <p class="text-sm text-slate-500 mt-1">Need help? Get in touch with our team or create a support ticket.</p>
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

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 space-y-2 shadow-sm animate-in fade-in duration-300">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                    <div class="text-sm font-bold">Please correct the following errors:</div>
                </div>
                <ul class="text-xs list-disc pl-8 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Direct Support Channels Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- WhatsApp Card -->
            <a href="https://wa.me/2347048932365?text=Hello%20SimPhix%20Support,%20I%20need%20assistance." target="_blank"
               class="channel-card rounded-xl border border-slate-200/80 p-6 flex items-center gap-4 hover:border-emerald-200 group">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center border border-emerald-100/50 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.725 1.451 5.486.002 9.95-4.437 9.954-9.899.002-2.647-1.03-5.132-2.903-7.005C16.552 1.826 14.07 .797 11.44 .797c-5.49 0-9.958 4.437-9.96 9.898-.001 1.702.469 3.361 1.36 4.816L1.81 21.17l5.837-1.53M18.847 15.3c-.31-.155-1.837-.905-2.115-1.006-.279-.101-.482-.152-.684.152-.202.304-.785.987-.962 1.189-.177.202-.355.228-.666.073-1.096-.548-1.825-1.015-2.548-2.257-.193-.332.193-.309.553-1.026.061-.122.03-.228-.015-.304-.045-.076-.482-1.162-.66-1.593-.173-.418-.349-.36-.482-.367-.125-.007-.27-.008-.415-.008-.146 0-.383.055-.584.275-.202.22-1.77 1.728-1.77 4.212s1.808 4.88 2.06 5.22c.253.34 3.563 5.44 8.63 7.636 1.206.52 2.148.83 2.88 1.062 1.213.385 2.316.33 3.19.2.975-.145 3.011-1.23 3.431-2.42.42-1.19.42-2.21.295-2.42-.125-.21-.462-.31-.772-.465z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 font-display flex items-center gap-1.5">
                        WhatsApp Support
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-ping"></span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Chat directly on WhatsApp.</p>
                </div>
            </a>

            <!-- Call Card -->
            <a href="tel:+2347048932365"
               class="channel-card rounded-xl border border-slate-200/80 p-6 flex items-center gap-4 hover:border-indigo-200 group">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-[#0056D2] flex items-center justify-center border border-indigo-100/50 group-hover:scale-110 transition-transform">
                    <i data-lucide="phone-call" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-800 font-display">Phone Call Support</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Speak with a customer representative.</p>
                </div>
            </a>

            <!-- Email Card -->
            <a href="mailto:Support@smartsimsub.com"
               class="channel-card rounded-xl border border-slate-200/80 p-6 flex items-center gap-4 hover:border-blue-200 group">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center border border-blue-100/50 group-hover:scale-110 transition-transform">
                    <i data-lucide="mail" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-800 font-display">Email Support</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Drop a mail to Support@smartsimsub.com</p>
                </div>
            </a>
        </div>

        <!-- Support Tickets List & Form -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            
            <!-- Left Side: Create Support Ticket Form -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-full">
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-50">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2]">
                                <i data-lucide="message-square" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800 font-display">Create Support Ticket</h3>
                                <p class="text-xs text-slate-400">Our support agents typically respond in minutes.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('support.store') }}" class="space-y-5">
                            @csrf
                            <input type="hidden" name="category" id="selectedCategory" value="{{ old('category', 'general') }}">
                            <input type="hidden" name="priority" id="selectedPriority" value="{{ old('priority', 'medium') }}">

                            {{-- Subject Input --}}
                            <div class="space-y-1.5">
                                <x-input-label for="subject" value="Subject" />
                                <x-text-input type="text" id="subject" name="subject" :value="old('subject')" required
                                       class="font-semibold" placeholder="e.g. Wallet Funding issue" />
                            </div>

                            {{-- Interactive Category Picker --}}
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Category</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="category-option p-3 rounded-xl border border-slate-200 hover:bg-slate-50 flex flex-col justify-between" 
                                         data-value="general">
                                        <i data-lucide="info" class="w-4 h-4 text-indigo-500 mb-1.5"></i>
                                        <div>
                                            <span class="block text-xs font-bold text-slate-700">General</span>
                                            <span class="text-xs text-slate-400 font-medium">Inquiry / feedback</span>
                                        </div>
                                    </div>
                                    <div class="category-option p-3 rounded-xl border border-slate-200 hover:bg-slate-50 flex flex-col justify-between" 
                                         data-value="technical">
                                        <i data-lucide="cpu" class="w-4 h-4 text-emerald-500 mb-1.5"></i>
                                        <div>
                                            <span class="block text-xs font-bold text-slate-700">Technical</span>
                                            <span class="text-xs text-slate-400 font-medium">App bugs / downtime</span>
                                        </div>
                                    </div>
                                    <div class="category-option p-3 rounded-xl border border-slate-200 hover:bg-slate-50 flex flex-col justify-between" 
                                         data-value="billing">
                                        <i data-lucide="credit-card" class="w-4 h-4 text-rose-500 mb-1.5"></i>
                                        <div>
                                            <span class="block text-xs font-bold text-slate-700">Billing</span>
                                            <span class="text-xs text-slate-400 font-medium">Funding / charges</span>
                                        </div>
                                    </div>
                                    <div class="category-option p-3 rounded-xl border border-slate-200 hover:bg-slate-50 flex flex-col justify-between" 
                                         data-value="upgrade">
                                        <i data-lucide="arrow-up-circle" class="w-4 h-4 text-amber-500 mb-1.5"></i>
                                        <div>
                                            <span class="block text-xs font-bold text-slate-700">Upgrade</span>
                                            <span class="text-xs text-slate-400 font-medium">Role verification</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Interactive Segmented Priority Selector --}}
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Priority</label>
                                <div class="flex bg-slate-50 rounded-xl p-1 border border-slate-200">
                                    <button type="button" class="priority-btn flex-1 py-2 text-center text-xs font-bold rounded-lg" data-value="low">
                                        Low
                                    </button>
                                    <button type="button" class="priority-btn flex-1 py-2 text-center text-xs font-bold rounded-lg" data-value="medium">
                                        Medium
                                    </button>
                                    <button type="button" class="priority-btn flex-1 py-2 text-center text-xs font-bold rounded-lg" data-value="high">
                                        High
                                    </button>
                                </div>
                            </div>

                            {{-- Message Input --}}
                            <div class="space-y-1.5">
                                <x-input-label for="message" value="Message Description" />
                                <x-textarea-input id="message" name="message" rows="4" required
                                          placeholder="Describe your issue or inquiry in detail...">{{ old('message') }}</x-textarea-input>
                            </div>

                            {{-- Submit Button --}}
                            <div class="pt-2">
                                <x-primary-button type="submit" class="w-full">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                    <span>Submit Ticket</span>
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Side: Ticket History -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-full">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2]">
                                    <i data-lucide="history" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800 font-display">My Tickets History</h3>
                                    <p class="text-xs text-slate-400">View and respond to your active or past support tickets</p>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100">
                                        <th class="py-3 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Ticket</th>
                                        <th class="py-3 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Category</th>
                                        <th class="py-3 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Priority</th>
                                        <th class="py-3 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                                        <th class="py-3 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @forelse($tickets as $ticket)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3.5 px-4">
                                                <div class="font-bold text-sm text-slate-800 truncate max-w-[150px]">
                                                    {{ $ticket->subject }}
                                                </div>
                                                <div class="text-xs font-semibold text-slate-400 mt-0.5">
                                                    ID: #{{ $ticket->id }} • {{ $ticket->created_at->format('M d, Y') }}
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 text-xs font-bold text-slate-600">
                                                {{ ucfirst($ticket->category) }}
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border {{ $ticket->priority_badge }}">
                                                    {{ ucfirst($ticket->priority) }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-bold border {{ $ticket->status_badge }}">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                                    {{ ucfirst($ticket->status) }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 text-right">
                                                <a href="{{ route('support.show', $ticket) }}" 
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0056D2]/5 hover:bg-[#0056D2]/10 text-[#0056D2] font-bold text-xs rounded-xl transition-all">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-12 text-center text-slate-400">
                                                <div class="bg-slate-50 rounded-2xl w-14 h-14 flex items-center justify-center mb-3 mx-auto border border-slate-100">
                                                    <i data-lucide="message-square-off" class="w-6 h-6 text-slate-400"></i>
                                                </div>
                                                <h6 class="font-bold text-slate-800 text-sm">No Tickets Found</h6>
                                                <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto">
                                                    You haven't submitted any support tickets yet.
                                                </p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        {{ $tickets->withQueryString()->links('vendor.pagination.custom') }}
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Interactive Category Picker Logic
            const categories = document.querySelectorAll('.category-option');
            const hiddenCategory = document.getElementById('selectedCategory');

            categories.forEach(opt => {
                // Set initial active state based on hidden input
                if (opt.dataset.value === hiddenCategory.value) {
                    opt.classList.add('active');
                }

                opt.addEventListener('click', function () {
                    categories.forEach(o => o.classList.remove('active'));
                    this.classList.add('active');
                    hiddenCategory.value = this.dataset.value;
                });
            });

            // Interactive Segmented Priority Logic
            const priorityButtons = document.querySelectorAll('.priority-btn');
            const hiddenPriority = document.getElementById('selectedPriority');

            priorityButtons.forEach(btn => {
                // Set initial active state
                if (btn.dataset.value === hiddenPriority.value) {
                    btn.classList.add('active');
                }

                btn.addEventListener('click', function () {
                    priorityButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    hiddenPriority.value = this.dataset.value;
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
