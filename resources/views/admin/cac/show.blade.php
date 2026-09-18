<x-app-layout>
    <title>SmartSIM Admin - CAC {{ $registration->reference }}</title>

    @php $locked = in_array($registration->status, \App\Models\CacRegistration::REFUND_STATUSES, true); @endphp

    <div class="max-w-6xl mx-auto space-y-6">
        <a href="{{ route('admin.cac.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to CAC Registrations
        </a>

        <div class="flex flex-col sm:flex-row sm:items-center gap-4 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex-1 min-w-0">
                <p class="font-mono text-xs font-bold text-slate-400">{{ $registration->reference }}</p>
                <h1 class="text-xl font-extrabold font-display text-slate-900">{{ $registration->category_label }} — {{ $registration->form_data['names'][0] ?? '' }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    <a href="{{ route('admin.manage.users.show', $registration->user) }}" class="font-bold text-primary hover:underline">{{ $registration->user->first_name }} {{ $registration->user->last_name }}</a>
                    · {{ $registration->user->email }} · {{ $registration->user->phone }} · fee ₦{{ number_format($registration->amount, 2) }} ({{ $registration->transaction_ref }})
                </p>
            </div>
            <span class="inline-flex items-center px-3 py-1.5 text-xs font-extrabold border rounded-full {{ $registration->status_badge }}">{{ $registration->status_label }}</span>
        </div>

        @if (session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-xs font-semibold">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl text-xs font-semibold">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl text-xs font-semibold">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 space-y-6">
                <!-- Status -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4">Update status</h3>
                    @if ($locked)
                        <p class="text-xs text-slate-500">This registration was {{ $registration->status }} and the fee refunded — it is closed.</p>
                    @else
                        <form method="POST" action="{{ route('admin.cac.status', $registration) }}" class="space-y-3"
                              x-data="{ s: '{{ $registration->status }}' }"
                              @submit="if (['rejected','cancelled'].includes(s) && !confirm('This refunds the fee to the user\'s wallet and closes the registration. Continue?')) $event.preventDefault()">
                            @csrf
                            <select name="status" x-model="s" class="w-full text-sm rounded-lg border border-slate-200 px-3 py-2">
                                @foreach (\App\Models\CacRegistration::STATUSES as $v => $l)
                                    <option value="{{ $v }}">{{ $l }}{{ in_array($v, \App\Models\CacRegistration::REFUND_STATUSES) ? ' (refunds fee)' : '' }}</option>
                                @endforeach
                            </select>
                            <textarea name="message" rows="3" placeholder="Message shown to the applicant (optional)…" class="w-full text-sm rounded-lg border border-slate-200 px-3 py-2"></textarea>
                            <button class="inline-flex items-center gap-2 bg-primary hover:bg-[#0049b8] text-white font-bold text-xs px-4 py-2.5 rounded-xl"><i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Update &amp; notify</button>
                        </form>
                    @endif
                </div>

                <!-- Upload -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4">Upload documents for the applicant</h3>
                    <form method="POST" action="{{ route('admin.cac.upload', $registration) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="text" name="label" placeholder="Label, e.g. Certificate of Incorporation" class="w-full text-sm rounded-lg border border-slate-200 px-3 py-2">
                        <input type="file" name="files[]" multiple required accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.zip" class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary">
                        <textarea name="message" rows="2" placeholder="Optional note to include…" class="w-full text-sm rounded-lg border border-slate-200 px-3 py-2"></textarea>
                        <button class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl"><i data-lucide="upload" class="w-3.5 h-3.5"></i> Upload &amp; notify</button>
                    </form>
                    <div class="mt-4 space-y-1.5">
                        @foreach ($registration->documents->where('uploaded_by', 'admin') as $doc)
                            <div class="flex items-center justify-between gap-2 text-xs p-2 rounded-lg bg-emerald-50/50 border border-emerald-100">
                                <a href="{{ route('admin.cac.download', [$registration, $doc]) }}" class="font-bold text-slate-700 hover:text-primary truncate">{{ $doc->label }} — {{ $doc->original_name }}</a>
                                <form method="POST" action="{{ route('admin.cac.documents.destroy', [$registration, $doc]) }}" onsubmit="return confirm('Remove this file?')">@csrf @method('DELETE')<button class="text-rose-600 font-bold">Remove</button></form>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Notes + timeline -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4">Notes &amp; history</h3>
                    <form method="POST" action="{{ route('admin.cac.notes', $registration) }}" class="space-y-2 mb-6">
                        @csrf
                        <textarea name="message" rows="2" required placeholder="Write a note…" class="w-full text-sm rounded-lg border border-slate-200 px-3 py-2"></textarea>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600"><input type="checkbox" name="visible_to_user" value="1" checked class="rounded border-slate-300"> Visible to applicant (emails them)</label>
                            <button class="text-xs font-bold px-4 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-900">Add note</button>
                        </div>
                    </form>
                    <div class="space-y-5 relative pl-6 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
                        @foreach ($registration->events as $e)
                            <div class="relative">
                                <span class="absolute -left-6 top-0.5 w-6 h-6 rounded-full border-4 border-white shadow-sm {{ $e->type === 'status' ? 'bg-primary' : ($e->is_admin ? 'bg-emerald-500' : 'bg-slate-300') }}"></span>
                                <span class="block text-xs font-bold text-slate-700">
                                    @if ($e->type === 'status') {{ $e->status_from ? (\App\Models\CacRegistration::STATUSES[$e->status_from] ?? $e->status_from) . ' → ' : '' }}{{ \App\Models\CacRegistration::STATUSES[$e->status_to] ?? $e->status_to }}
                                    @elseif ($e->type === 'upload') Documents uploaded
                                    @else {{ $e->is_admin ? 'Admin note' : 'Applicant' }} @endif
                                    <span class="font-medium text-slate-400">· {{ $e->user?->first_name }}</span>
                                    @unless ($e->visible_to_user) <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100 rounded">internal</span> @endunless
                                </span>
                                <span class="block text-[11px] text-slate-400">{{ $e->created_at->format('M d, Y h:i A') }}</span>
                                @if ($e->message)<p class="text-xs text-slate-600 mt-1 whitespace-pre-line bg-slate-50 border border-slate-100 rounded-lg p-2.5">{{ $e->message }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-800 font-display mb-3">Submitted details</h3>
                    @include('cac.partials.details', ['registration' => $registration])
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-800 font-display mb-3">Applicant uploads</h3>
                    <div class="space-y-1.5">
                        @forelse ($registration->documents->where('uploaded_by', 'user') as $doc)
                            <a href="{{ route('admin.cac.download', [$registration, $doc]) }}" class="flex items-center justify-between gap-2 text-xs text-slate-600 hover:text-primary">
                                <span class="truncate">{{ $doc->label }} <span class="text-slate-400">({{ $doc->original_name }})</span></span><i data-lucide="download" class="w-3 h-3 shrink-0"></i>
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
