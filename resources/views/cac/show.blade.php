<x-app-layout>
    <title>SimPhix - CAC {{ $registration->reference }}</title>

    @php
        $deliverables = $registration->documents->where('is_deliverable', true);
        $myDocs = $registration->documents->where('is_deliverable', false);
    @endphp

    <div class="max-w-5xl mx-auto space-y-6">
        <a href="{{ route('cac.history') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to My Registrations
        </a>

        <div class="flex flex-col sm:flex-row sm:items-center gap-4 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex-1 min-w-0">
                <p class="font-mono text-xs font-bold text-slate-400">{{ $registration->reference }}</p>
                <h1 class="text-xl font-extrabold font-display text-slate-900">{{ $registration->category_label }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ $registration->form_data['names'][0] ?? '' }} · submitted {{ $registration->submitted_at?->format('M d, Y') }} · fee ₦{{ number_format($registration->amount, 2) }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1.5 text-xs font-extrabold border rounded-full {{ $registration->status_badge }}">{{ $registration->status_label }}</span>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 text-sm font-semibold">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 text-sm font-semibold">{{ session('error') }}</div>
        @endif
        @if ($registration->status === 'action_required')
            <div class="bg-amber-50 border border-amber-100 rounded-lg p-4 text-amber-800 text-sm font-semibold flex items-start gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                Our team needs something from you — read the latest note below and reply.
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 space-y-6">
                <!-- Documents from us -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100 mb-3 flex items-center gap-2"><i data-lucide="download" class="w-4 h-4 text-primary"></i> Your documents</h3>
                    <div class="space-y-2">
                        @forelse ($deliverables as $doc)
                            <a href="{{ route('cac.download', [$registration, $doc]) }}" class="flex items-center justify-between gap-3 p-3 rounded-xl border border-emerald-100 bg-emerald-50/40 hover:bg-emerald-50 transition-all">
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-slate-800 truncate">{{ $doc->label }}</span>
                                    <span class="block text-xs text-slate-500 truncate">{{ $doc->original_name }} · {{ $doc->created_at->format('M d, Y') }}</span>
                                </span>
                                <i data-lucide="download" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                            </a>
                        @empty
                            <p class="text-xs text-slate-400">Documents such as your certificate will appear here once uploaded by our team.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4">Progress &amp; notes</h3>
                    <div class="space-y-5 relative pl-6 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
                        @foreach ($registration->events as $e)
                            <div class="relative">
                                <span class="absolute -left-6 top-0.5 w-6 h-6 rounded-full border-4 border-white flex items-center justify-center shadow-sm {{ $e->type === 'status' ? 'bg-primary' : ($e->is_admin ? 'bg-emerald-500' : 'bg-slate-300') }}"></span>
                                <span class="block text-xs font-bold text-slate-700">
                                    @if ($e->type === 'status')
                                        {{ $e->status_from ? (\App\Models\CacRegistration::STATUSES[$e->status_from] ?? $e->status_from) . ' → ' : '' }}{{ \App\Models\CacRegistration::STATUSES[$e->status_to] ?? $e->status_to }}
                                    @elseif ($e->type === 'upload')
                                        Documents uploaded
                                    @else
                                        {{ $e->is_admin ? 'Message from SimPhix' : 'You' }}
                                    @endif
                                </span>
                                <span class="block text-[11px] text-slate-400 mt-0.5">{{ $e->created_at->format('M d, Y h:i A') }}</span>
                                @if ($e->message)
                                    <p class="text-xs text-slate-600 mt-1 whitespace-pre-line bg-slate-50 border border-slate-100 rounded-lg p-2.5">{{ $e->message }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @unless ($registration->isFinal())
                        <form method="POST" action="{{ route('cac.reply', $registration) }}" enctype="multipart/form-data" class="mt-6 pt-5 border-t border-slate-100 space-y-3">
                            @csrf
                            <label class="block text-xs font-bold text-slate-600">Send a message or additional document</label>
                            <textarea name="message" rows="3" required class="w-full text-sm rounded-lg border border-slate-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>
                            @error('message') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary">
                            @error('attachment') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            <button class="inline-flex items-center gap-2 bg-primary hover:bg-[#0049b8] text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-colors"><i data-lucide="send" class="w-3.5 h-3.5"></i> Send</button>
                        </form>
                    @endunless
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-800 font-display mb-3">Submitted details</h3>
                    @include('cac.partials.details', ['registration' => $registration])
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-800 font-display mb-3">Files you uploaded</h3>
                    <div class="space-y-1.5">
                        @forelse ($myDocs as $doc)
                            <a href="{{ route('cac.download', [$registration, $doc]) }}" class="flex items-center justify-between gap-2 text-xs text-slate-600 hover:text-primary">
                                <span class="truncate">{{ $doc->label }}</span><i data-lucide="download" class="w-3 h-3 shrink-0"></i>
                            </a>
                        @empty
                            <p class="text-xs text-slate-400">None.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
