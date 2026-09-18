<x-app-layout>
    <title>SmartSIM - My CAC Registrations</title>

    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('cac.overview') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition-colors mb-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> CAC Registration
                </a>
                <h1 class="text-2xl font-bold font-display text-slate-800">My Registrations</h1>
                <p class="text-sm text-slate-500 mt-1">Track every CAC registration you've submitted, read notes from our team and download your documents.</p>
            </div>
            <a href="{{ route('cac.overview') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-[#0049b8] text-white font-bold text-xs rounded-xl shadow-sm transition-all shrink-0">
                <i data-lucide="plus" class="w-4 h-4"></i> New Registration
            </a>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 text-sm font-semibold">{{ session('success') }}</div>
        @endif

        <form method="GET" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center gap-3">
            <select name="status" onchange="this.form.submit()" class="text-xs rounded-lg border border-slate-200 px-3 py-2 text-slate-700">
                <option value="">All statuses</option>
                @foreach (\App\Models\CacRegistration::STATUSES as $v => $l)
                    <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <select name="category" onchange="this.form.submit()" class="text-xs rounded-lg border border-slate-200 px-3 py-2 text-slate-700">
                <option value="">All types</option>
                @foreach (\App\Support\CacRequirements::all() as $k => $c)
                    <option value="{{ $k }}" @selected(request('category') === $k)>{{ $c['label'] }}</option>
                @endforeach
            </select>
            @if (request('status') || request('category'))
                <a href="{{ route('cac.history') }}" class="text-xs font-bold text-slate-400 hover:text-slate-600">Clear filters</a>
            @endif
        </form>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">Reference</th>
                            <th class="py-4 px-6">Type</th>
                            <th class="py-4 px-6">Submitted</th>
                            <th class="py-4 px-6 text-right">Fee</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($registrations as $r)
                            <tr class="hover:bg-slate-50/30">
                                <td class="py-4 px-6 font-mono text-xs font-bold text-slate-800">{{ $r->reference }}</td>
                                <td class="py-4 px-6 text-xs">
                                    <span class="block font-bold">{{ $r->category_label }}</span>
                                    <span class="block text-slate-400">{{ $r->form_data['names'][0] ?? '' }}</span>
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500">{{ $r->submitted_at?->format('M d, Y h:i A') }}</td>
                                <td class="py-4 px-6 text-right text-xs font-bold">₦{{ number_format($r->amount, 2) }}</td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold border rounded-full {{ $r->status_badge }}">{{ $r->status_label }}</span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('cac.show', $r) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/5 hover:bg-primary/10 text-primary font-bold text-xs rounded-xl transition-all">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i> Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-14 text-slate-400 text-sm">No registrations yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($registrations->hasPages())
                <div class="p-6 border-t border-slate-100 bg-slate-50/30">{{ $registrations->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
