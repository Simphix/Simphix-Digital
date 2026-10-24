<x-app-layout>
    <title>SimPhix Admin - CAC Registrations</title>

    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800">CAC Registrations</h1>
                <p class="text-xs text-slate-400 mt-1">Review applications, respond, upload deliverables and move each registration through its status pipeline.</p>
            </div>
            @if ($pricingService)
                <a href="{{ route('admin.services.show', $pricingService) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-sm transition-all">
                    <i data-lucide="badge-dollar-sign" class="w-4 h-4"></i> Configure Pricing
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-xs font-semibold">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach (['total' => ['Total', 'text-slate-800'], 'new' => ['New (Submitted)', 'text-amber-600'], 'in_progress' => ['In Progress', 'text-blue-600'], 'completed' => ['Completed', 'text-emerald-600']] as $k => [$l, $c])
                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $l }}</span>
                    <p class="text-2xl font-extrabold font-display mt-1 {{ $c }}">{{ number_format($stats[$k]) }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form method="GET" class="p-5 border-b border-slate-100 bg-slate-50/30 flex flex-col md:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference, name, email or phone…"
                       class="flex-grow text-sm rounded-lg border border-slate-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20">
                <select name="status" class="text-xs rounded-lg border border-slate-200 px-3 py-2">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\CacRegistration::STATUSES as $v => $l)
                        <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
                <select name="category" class="text-xs rounded-lg border border-slate-200 px-3 py-2">
                    <option value="">All types</option>
                    @foreach (\App\Support\CacRequirements::all() as $k => $c)
                        <option value="{{ $k }}" @selected(request('category') === $k)>{{ $c['label'] }}</option>
                    @endforeach
                </select>
                <button class="text-xs font-bold px-4 py-2 rounded-lg bg-primary text-white hover:bg-[#0049b8]">Filter</button>
                @if (request()->hasAny(['search', 'status', 'category']))
                    <a href="{{ route('admin.cac.index') }}" class="text-xs font-bold px-4 py-2 rounded-lg bg-slate-100 text-slate-600 text-center">Reset</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">Reference</th>
                            <th class="py-4 px-6">Applicant</th>
                            <th class="py-4 px-6">Type</th>
                            <th class="py-4 px-6">Submitted</th>
                            <th class="py-4 px-6 text-right">Fee</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($registrations as $r)
                            <tr class="hover:bg-slate-50/30">
                                <td class="py-4 px-6 font-mono text-xs font-bold">{{ $r->reference }}</td>
                                <td class="py-4 px-6 text-xs"><span class="block font-bold text-slate-800">{{ $r->user->first_name }} {{ $r->user->last_name }}</span><span class="text-slate-400">{{ $r->user->email }}</span></td>
                                <td class="py-4 px-6 text-xs"><span class="block font-bold">{{ $r->category_label }}</span><span class="text-slate-400">{{ $r->form_data['names'][0] ?? '' }}</span></td>
                                <td class="py-4 px-6 text-xs text-slate-500">{{ $r->submitted_at?->format('M d, Y') }}</td>
                                <td class="py-4 px-6 text-right text-xs font-bold">₦{{ number_format($r->amount, 2) }}</td>
                                <td class="py-4 px-6"><span class="inline-flex px-2.5 py-1 text-xs font-extrabold border rounded-full {{ $r->status_badge }}">{{ $r->status_label }}</span></td>
                                <td class="py-4 px-6 text-right"><a href="{{ route('admin.cac.show', $r) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/5 hover:bg-primary/10 text-primary font-bold text-xs rounded-xl"><i data-lucide="settings-2" class="w-3.5 h-3.5"></i> Manage</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-14 text-slate-400 text-sm">No registrations found.</td></tr>
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
