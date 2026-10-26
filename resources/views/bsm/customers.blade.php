<x-app-layout>
<div class="space-y-6" x-data="{
    showQuickActivateModal: false,
    selectedCustomer: null
}">

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                <p class="text-sm font-medium">{{ session('success') }}</p>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
                <p class="text-sm font-medium">{{ session('error') }}</p>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    <!-- Header & Actions -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-emerald-50 text-emerald-600">
                        <i data-lucide="user-check" class="w-6 h-6"></i>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 font-display">Customer Handling Desk</h1>
                        <p class="text-sm text-slate-500">End-Customer KYC records, verified phone numbers, and expedited onboarding</p>
                    </div>
                </div>
            </div>

            <!-- Global Actions -->
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" @click="showQuickActivateModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold shadow-sm hover:bg-primary-dark transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Quick Onboard & Activate</span>
                </button>
            </div>
        </div>

        <!-- Filter Controls Form -->
        <form method="GET" action="{{ route('bsm.customers') }}" class="mt-6 pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <input type="text" name="customer_search" value="{{ $customerSearch }}" placeholder="Search by customer name, phone number, email or SIM number…" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary pl-9 pr-3 py-2.5 bg-slate-50">
                <div class="absolute left-3 top-3 text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">
                    Search
                </button>
                @if ($customerSearch)
                    <a href="{{ route('bsm.customers') }}" class="px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-600 transition" title="Clear Search">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Customers Directory Table -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Registered End-Customers</h2>
                <p class="text-xs text-slate-500">Showing verified customer profiles linked to active SIM cards</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-lg">
                Total: {{ number_format($totalCustomers) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">Customer Full Name</th>
                        <th class="py-2.5 px-3">Phone & Email</th>
                        <th class="py-2.5 px-3">Associated SIM</th>
                        <th class="py-2.5 px-3">Network</th>
                        <th class="py-2.5 px-3">Registered By</th>
                        <th class="py-2.5 px-3">Onboarded Date</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($customers as $cust)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-3">
                                <p class="font-bold text-slate-900">{{ $cust->full_name }}</p>
                                <p class="text-[10px] text-slate-400">NIN: {{ $cust->nin ? substr($cust->nin, 0, 4) . '***' . substr($cust->nin, -2) : '—' }}</p>
                            </td>
                            <td class="py-3 px-3">
                                <p class="font-semibold text-slate-800">{{ $cust->phone_number }}</p>
                                <p class="text-[10px] text-slate-400">{{ $cust->email ?? '—' }}</p>
                            </td>
                            <td class="py-3 px-3">
                                @if ($cust->sim)
                                    <span class="font-mono font-bold text-slate-900">{{ $cust->sim->number }}</span>
                                    <span class="block text-[10px] text-slate-400">{{ $cust->sim->category }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @if ($cust->sim)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                        {{ $cust->sim->provider }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @if ($cust->partner)
                                    <p class="font-semibold text-slate-800">{{ $cust->partner->name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $cust->partner->role)) }}</p>
                                @else
                                    <span class="text-slate-400">Direct BSM Desk</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-slate-500 font-medium">
                                {{ $cust->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button type="button" @click="selectedCustomer = {{ Illuminate\Support\Js::from($cust) }}" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                                    Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">No customer profiles found. Use Quick Customer Onboard to register customers.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100">
            {{ $customers->links() }}
        </div>
    </div>

    <!-- Quick Onboard Modal -->
    <div x-show="showQuickActivateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @click.self="showQuickActivateModal = false">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900 font-display">Expedited Customer Activation</h3>
                <button type="button" @click="showQuickActivateModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <p class="text-xs text-slate-500">Directly onboard an end-customer and activate a held or unassigned SIM immediately.</p>

            <form method="POST" action="{{ route('bsm.customers.quick-activate') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Available SIM <span class="text-rose-500">*</span></label>
                    <select name="sim_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5">
                        <option value="">Select SIM number...</option>
                        @foreach ($availableUnassignedSims as $uSim)
                            <option value="{{ $uSim->id }}">{{ $uSim->number }} - {{ $uSim->category }} ({{ strtoupper($uSim->provider) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="full_name" required placeholder="e.g. John Okon" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Phone <span class="text-rose-500">*</span></label>
                        <input type="text" name="phone_number" required placeholder="e.g. 08012345678" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Email</label>
                        <input type="email" name="email" placeholder="customer@example.com" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NIN (Optional)</label>
                        <input type="text" name="nin" maxlength="11" placeholder="11-digit NIN" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Residential / Business Address</label>
                    <textarea name="address" rows="2" placeholder="Street, City, State..." class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showQuickActivateModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-primary hover:bg-primary-dark text-white font-display">Onboard &amp; Activate Now</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div x-show="selectedCustomer !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @click.self="selectedCustomer = null">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900 font-display">Customer Profile</h3>
                <button type="button" @click="selectedCustomer = null" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <template x-if="selectedCustomer">
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="font-bold text-slate-400 uppercase text-[10px]">Full Name</span>
                        <p class="font-bold text-slate-900 text-sm" x-text="selectedCustomer.full_name"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="font-bold text-slate-400 uppercase text-[10px]">Phone Number</span>
                            <p class="font-semibold text-slate-800" x-text="selectedCustomer.phone_number"></p>
                        </div>
                        <div>
                            <span class="font-bold text-slate-400 uppercase text-[10px]">Email</span>
                            <p class="font-semibold text-slate-800" x-text="selectedCustomer.email || '—'"></p>
                        </div>
                    </div>
                    <div>
                        <span class="font-bold text-slate-400 uppercase text-[10px]">Residential Address</span>
                        <p class="font-medium text-slate-700" x-text="selectedCustomer.address || '—'"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                        <div>
                            <span class="font-bold text-slate-400 uppercase text-[10px]">NIN</span>
                            <p class="font-medium text-slate-700" x-text="selectedCustomer.nin || '—'"></p>
                        </div>
                        <div>
                            <span class="font-bold text-slate-400 uppercase text-[10px]">Date of Birth</span>
                            <p class="font-medium text-slate-700" x-text="selectedCustomer.date_of_birth || '—'"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
</x-app-layout>
