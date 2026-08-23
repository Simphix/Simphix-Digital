<x-app-layout>
    <title>SmartSIM - Bulk Collect SIM</title>

    <div class="max-w-3xl mx-auto space-y-6"
         x-data="{
            numbers: '',
            validating: false,
            validated: false,
            resolved: [],
            errors: [],
            userQuery: '',
            userResults: [],
            searching: false,
            selectedUser: null,

            async validateNumbers() {
                if (!this.numbers.trim()) return;
                this.validating = true;
                const res = await fetch('{{ route('sims.bulk-collect.resolve-numbers') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ numbers: this.numbers }),
                });
                const data = await res.json();
                this.resolved = data.resolved;
                this.errors = data.errors;
                this.validated = true;
                this.validating = false;
                this.selectedUser = null;
            },

            async searchUsers() {
                if (this.userQuery.trim().length < 2) { this.userResults = []; return; }
                this.searching = true;
                const res = await fetch('{{ route('sims.downline-users.search') }}?q=' + encodeURIComponent(this.userQuery));
                this.userResults = await res.json();
                this.searching = false;
            },

            selectUser(u) {
                this.selectedUser = u;
                this.userResults = [];
                this.userQuery = u.name + ' · ' + (u.phone || u.email);
            },

            submit() {
                const f = document.createElement('form');
                f.method = 'POST';
                f.action = '{{ route('sims.bulk-collect.execute') }}';
                let inputs = '<input type=\'hidden\' name=\'_token\' value=\'{{ csrf_token() }}\'>';
                inputs += '<input type=\'hidden\' name=\'user_id\' value=\'' + this.selectedUser.id + '\'>';
                this.resolved.forEach(s => { inputs += '<input type=\'hidden\' name=\'sim_ids[]\' value=\'' + s.id + '\'>'; });
                f.innerHTML = inputs;
                document.body.appendChild(f);
                f.submit();
            }
         }">

        <a href="{{ route('sims.inventory') }}"
           class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-[#0056D2] transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to Inventory
        </a>

        <div class="flex items-center gap-3 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-100/50 flex items-center justify-center text-rose-600 shrink-0">
                <i data-lucide="user-minus" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-xl font-extrabold font-display text-slate-900">Bulk Collect SIM</h1>
                <p class="text-sm text-slate-500 mt-0.5">Paste a batch of SIM numbers currently down your line, review what's valid, then pick which downline user to reclaim them from.</p>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-amber-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('warning') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Step 1: Paste & Validate -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100">1. SIM Numbers</h3>

            <x-textarea-input x-model="numbers" rows="10" placeholder="08030000000&#10;08031111111&#10;08032222222"
                      class="rounded-xl font-semibold placeholder:font-normal" />
            <p class="text-xs text-slate-400">Separate numbers by comma or new lines.</p>

            <button type="button" @click="validateNumbers()" :disabled="validating || !numbers.trim()"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-colors">
                <i data-lucide="loader-2" class="w-3.5 h-3.5" x-show="validating" x-cloak></i>
                <span x-text="validating ? 'Validating…' : 'Validate Numbers'"></span>
            </button>

            <template x-if="validated">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <p class="text-xs font-bold text-emerald-600 uppercase mb-2">Valid (<span x-text="resolved.length"></span>)</p>
                        <div class="max-h-48 overflow-y-auto space-y-1">
                            <template x-for="s in resolved" :key="s.id">
                                <div class="text-xs font-medium text-slate-700 bg-emerald-50 border border-emerald-100 rounded-lg px-2.5 py-1.5" x-text="s.number"></div>
                            </template>
                            <p x-show="resolved.length === 0" class="text-xs text-slate-400 italic">None</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-rose-600 uppercase mb-2">Rejected (<span x-text="errors.length"></span>)</p>
                        <div class="max-h-48 overflow-y-auto space-y-1">
                            <template x-for="(e, i) in errors" :key="i">
                                <div class="text-xs font-medium text-slate-700 bg-rose-50 border border-rose-100 rounded-lg px-2.5 py-1.5" x-text="e"></div>
                            </template>
                            <p x-show="errors.length === 0" class="text-xs text-slate-400 italic">None</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Step 2: Pick user & complete -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4" x-show="validated && resolved.length > 0" x-cloak>
            <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100">2. Collect From</h3>
            <p class="text-xs text-slate-400 -mt-2">Numbers not actually held by the downline user you pick below will be skipped and reported, not collected from the wrong holder.</p>

            <div class="relative space-y-1.5">
                <x-input-label value="Search Your Downline by Name, Email, or Phone" />
                <x-text-input type="text" x-model="userQuery" @input.debounce.400ms="searchUsers()"
                              placeholder="Start typing…" autocomplete="off" class="rounded-xl font-medium" />

                <div x-show="userResults.length > 0" x-cloak
                     class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                    <template x-for="u in userResults" :key="u.id">
                        <button type="button" @click="selectUser(u)"
                                class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-50 last:border-0">
                            <span class="block text-xs font-bold text-slate-700" x-text="u.name"></span>
                            <span class="block text-[11px] text-slate-400" x-text="(u.phone || '') + ' · ' + u.email + ' (' + u.role + ')'"></span>
                        </button>
                    </template>
                </div>
            </div>

            <button type="button" @click="submit()" :disabled="!selectedUser"
                    class="inline-flex items-center gap-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-colors">
                <i data-lucide="user-minus" class="w-3.5 h-3.5"></i>
                Collect <span x-text="resolved.length"></span> SIM(s) from Selected User
            </button>
        </div>
    </div>
</x-app-layout>
