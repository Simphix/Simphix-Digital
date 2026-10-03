<x-app-layout>
    <title>SmartSIM Support Desk - Failed Activations</title>

    <div class="space-y-6 max-w-7xl mx-auto">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Failed Activations</h1>
            <p class="text-xs text-slate-400 mt-1">Activation bonus data top-ups that failed at the vendor. Retry one once the cause is cleared — a retry already in flight can't be queued twice.</p>
        </div>

        @if (session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl flex items-center gap-2 text-xs font-semibold">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl flex items-center gap-2 text-xs font-semibold">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500 flex-shrink-0"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Failed</span>
                <p class="text-2xl font-extrabold text-rose-600 font-display mt-1">{{ number_format($failedCount) }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Retrying</span>
                <p class="text-2xl font-extrabold text-amber-600 font-display mt-1">{{ number_format($retryingCount) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase bg-slate-50/50">
                            <th class="py-3 px-5">SIM Number</th>
                            <th class="py-3 px-5">Network</th>
                            <th class="py-3 px-5">Status</th>
                            <th class="py-3 px-5">Reason</th>
                            <th class="py-3 px-5">Failed At</th>
                            <th class="py-3 px-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($failedActivations as $failed)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-5 font-semibold text-slate-800">{{ $failed->phone_number }}</td>
                                <td class="py-3 px-5 text-slate-600 uppercase">{{ $failed->network }}</td>
                                <td class="py-3 px-5">
                                    @if ($failed->status === 'retrying')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-700">Retrying…</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-100 text-red-700">Failed</span>
                                    @endif
                                </td>
                                <td class="py-3 px-5 text-slate-600 max-w-xs">{{ $failed->description }}</td>
                                <td class="py-3 px-5 text-slate-400 font-medium whitespace-nowrap">{{ $failed->created_at->format('M d, Y H:i') }}</td>
                                <td class="py-3 px-5 text-right whitespace-nowrap">
                                    @if ($failed->status === 'failed')
                                        <form action="{{ route('support-desk.failed-activations.retry', $failed) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-2.5 py-1 rounded-lg transition-colors">Retry</button>
                                        </form>
                                    @else
                                        <span class="text-slate-300 font-semibold">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">No failed activation top-ups. Nothing needs attention.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($failedActivations->hasPages())
                <div class="p-5 border-t border-slate-100 bg-slate-50/30">{{ $failedActivations->links('vendor.pagination.custom') }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
