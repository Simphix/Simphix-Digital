<x-app-layout>
    <title>SmartSIM Support Desk - User Lookup</title>

    <div class="space-y-8 max-w-4xl mx-auto">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">User Lookup</h1>
            <p class="text-xs text-slate-400 mt-1">Look up one account by its exact email, phone, or account ID. There is no general user listing here.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <form method="GET" action="{{ route('support-desk.users.index') }}" class="flex flex-col sm:flex-row items-stretch gap-3">
                <div class="relative flex-grow">
                    <x-text-input type="text" name="search" :value="$search"
                           class="pl-11 pr-4 rounded-xl font-semibold shadow-sm"
                           placeholder="Email, phone, or account ID" />
                    <div class="absolute left-4 top-3.5 text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                </div>
                <x-primary-button type="submit" class="!text-xs shrink-0">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    Look Up
                </x-primary-button>
                @if ($search !== '')
                    <a href="{{ route('support-desk.users.index') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-xl transition-all duration-150 shrink-0">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Clear
                    </a>
                @endif
            </form>
        </div>

        @if ($search === '')
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm text-center py-16 text-slate-400">
                <i data-lucide="user" class="w-10 h-10 mx-auto text-slate-300"></i>
                <p class="text-sm font-semibold text-slate-600 mt-3">Enter a user's exact email, phone, or account ID to look them up.</p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                @forelse ($users as $u)
                    <a href="{{ route('support-desk.users.show', $u) }}"
                       class="flex items-center justify-between gap-4 p-5 border-b border-slate-50 last:border-b-0 hover:bg-slate-50/50 transition-all">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-[#0056D2] flex items-center justify-center font-extrabold text-xs uppercase shrink-0">
                                {{ substr($u->first_name, 0, 1) }}{{ substr($u->last_name, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <span class="block text-sm font-bold text-slate-800 truncate">{{ $u->first_name }} {{ $u->last_name }}</span>
                                <span class="block text-xs text-slate-400 truncate">{{ $u->email }} • {{ $u->phone ?? 'no phone' }}</span>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 rounded-full text-xs font-extrabold uppercase border border-slate-200 shrink-0">{{ str_replace('_', ' ', $u->role) }}</span>
                    </a>
                @empty
                    <div class="text-center py-16 text-slate-400">
                        <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 border border-slate-100 shadow-inner mx-auto">
                            <i data-lucide="search-x" class="w-8 h-8"></i>
                        </div>
                        <h6 class="font-extrabold text-slate-800 font-display mt-4">No match found</h6>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed max-w-sm mx-auto">No account matches that exact email, phone, or ID.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</x-app-layout>
