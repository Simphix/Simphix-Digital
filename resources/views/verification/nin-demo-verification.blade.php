<x-app-layout>
    <title>SimPhix - {{ $title ?? 'Demographic Verification' }}</title>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    NIN Demographic Verification
                </h1>
                <p class="text-sm text-slate-500 mt-1">Verify identity details using demographic information instantly.</p>
            </div>
        </div>

        {{-- Main Grid --}}
        <div class="row g-4">
            <!-- Form Card -->
            <div class="col-12 col-lg-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden h-100 flex flex-col justify-between">
                    <div>
                        <div class="bg-white px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/10 text-primary">
                                    <i data-lucide="contact" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold font-display text-slate-800">Verify Identity</h3>
                                    <p class="text-xs text-slate-400 mt-0.5">Enter details exactly as they appear on the NIN record.</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-primary bg-primary/10 px-2.5 py-1 rounded-full uppercase tracking-wider">
                                Demographic
                            </span>
                        </div>

                        <div class="p-6 space-y-6">
                            {{-- Alerts --}}
                            @if (session('error'))
                                <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                                    <div class="text-xs font-semibold">{{ session('error') }}</div>
                                </div>
                            @endif

                            @if (session('status') && session('message'))
                                @if (session('status') === 'success')
                                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                                        <div class="text-xs font-semibold">{{ session('message') }}</div>
                                    </div>
                                @else
                                    <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                                        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                                        <div class="text-xs font-semibold">{{ session('message') }}</div>
                                    </div>
                                @endif
                            @endif

                            @if ($errors->any())
                                <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 space-y-2 shadow-sm animate-in fade-in duration-300">
                                    <div class="flex items-start gap-3">
                                        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                                        <div class="text-xs font-bold">Please correct the following errors:</div>
                                    </div>
                                    <ul class="text-xs list-disc pl-8 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('nin.demo.store') }}" class="space-y-6 m-0">
                                @csrf
                                
                                {{-- Names Grid --}}
                                <div class="row g-3 text-start">
                                    <div class="col-12 col-md-6">
                                        <div class="space-y-1.5">
                                            <x-input-label value="First Name *" />
                                            <x-text-input name="firstName" type="text"
                                                placeholder="Enter First Name" required :value="old('firstName')" />
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="space-y-1.5">
                                            <x-input-label value="Last Name *" />
                                            <x-text-input name="lastName" type="text"
                                                placeholder="Enter Last Name" required :value="old('lastName')" />
                                        </div>
                                    </div>
                                </div>

                                {{-- Gender & DOB Grid --}}
                                <div class="row g-3 text-start">
                                    <div class="col-12 col-md-6">
                                        <div class="space-y-1.5">
                                            <x-input-label value="Gender *" />
                                            <x-select-input name="gender" required>
                                                <option value="" disabled selected>Select Gender</option>
                                                <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Male</option>
                                                <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Female</option>
                                            </x-select-input>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="space-y-1.5">
                                            <x-input-label value="Date of Birth *" />
                                            <x-text-input name="dateOfBirth" type="text"
                                                placeholder="DD-MM-YYYY" required :value="old('dateOfBirth')" />
                                            <small class="text-xs text-slate-500 block mt-0.5">Format: 20-02-1966</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="bg-primary/5 border border-primary/10 rounded-lg p-4 flex justify-between items-center">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                                <i data-lucide="tag" class="w-4 h-4"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-800">Service Fee</span>
                                        </div>
                                        <span class="text-base font-extrabold font-display text-[#0056D2]">₦{{ number_format($demoPrice ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[11px] px-1">
                                        <span class="text-slate-400 font-medium">Your current balance:</span>
                                        <span class="font-bold text-slate-700">
                                            Wallet: <strong class="text-emerald-600 font-display">₦{{ number_format($wallet->balance ?? 0, 2) }}</strong>
                                        </span>
                                    </div>
                                </div>

                                <x-primary-button type="submit" class="w-full font-display">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                    <span>Verify Demographic</span>
                                </x-primary-button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification Info Card -->
            <div class="col-12 col-lg-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden h-100 flex flex-col justify-between min-h-[400px]">
                    <div>
                        <div class="bg-gradient-to-r from-vibrant to-[#008f4c] px-6 py-5 border-b border-slate-100 text-white flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center border border-white/10">
                                    <i data-lucide="file-check-2" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold font-display text-white">Verification Result</h3>
                                    <p class="text-xs text-slate-100 mt-0.5">Details of the queried identity record.</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 flex-grow">
                            @if (session('verification') && isset(session('verification')['data']))
                                <div class="space-y-6">
                                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-center gap-3 shadow-sm animate-in fade-in duration-300">
                                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0"></i>
                                        <div class="text-xs font-bold">Verification Successful!</div>
                                    </div>

                                    {{-- Passport Frame --}}
                                    <div class="flex flex-col items-center">
                                        <div class="relative group">
                                            <div class="absolute inset-0 bg-gradient-to-tr from-vibrant to-[#008f4c] rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-300"></div>
                                            <div class="relative p-1 bg-white border border-slate-200 rounded-xl shadow-md overflow-hidden w-36 h-40 flex items-center justify-center">
                                                @if (!empty(session('verification')['data']['photo']))
                                                    <img src="data:image/jpeg;base64,{{ session('verification')['data']['photo'] }}"
                                                        alt="ID Photo" class="w-full h-full object-cover rounded-lg">
                                                @else
                                                    <div class="w-full h-full bg-slate-50 flex flex-col items-center justify-center text-slate-400 rounded-lg">
                                                        <i data-lucide="user" class="w-10 h-10 mb-1"></i>
                                                        <span class="text-xs font-bold uppercase">No Image</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2.5">Passport Photograph</span>
                                    </div>

                                    {{-- Details List --}}
                                    <div class="bg-slate-50 border border-slate-100 rounded-lg p-4 divide-y divide-slate-200/50">
                                        <div class="flex justify-between items-center py-2 text-xs">
                                            <span class="text-slate-500 font-medium">NIN Number</span>
                                            <span class="font-bold text-[#0056D2] font-display tracking-wider">{{ session('verification')['data']['nin'] }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 text-xs">
                                            <span class="text-slate-500 font-medium">First Name</span>
                                            <span class="font-semibold text-slate-800">{{ session('verification')['data']['firstName'] }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 text-xs">
                                            <span class="text-slate-500 font-medium">Last Name</span>
                                            <span class="font-semibold text-slate-800">{{ session('verification')['data']['surname'] }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 text-xs">
                                            <span class="text-slate-500 font-medium">Middle Name</span>
                                            <span class="font-semibold text-slate-800">{{ session('verification')['data']['middleName'] ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 text-xs">
                                            <span class="text-slate-500 font-medium">Date of Birth</span>
                                            <span class="font-semibold text-slate-800">{{ session('verification')['data']['birthDate'] ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 text-xs">
                                            <span class="text-slate-500 font-medium">Gender</span>
                                            <span class="font-semibold text-slate-800">{{ strtoupper(session('verification')['data']['gender'] ?? 'N/A') }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 text-xs">
                                            <span class="text-slate-500 font-medium">Phone</span>
                                            <span class="font-semibold text-slate-800">{{ session('verification')['data']['telephoneNo'] ?? 'N/A' }}</span>
                                        </div>
                                    </div>

                                    {{-- Slips Download Section --}}
                                    <div class="border-t border-slate-100 pt-6 space-y-3.5">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Download Slip (Charges Apply)</h4>
                                        <div class="row g-2 justify-content-center">
                                            <div class="col-4">
                                                <button onclick="confirmDownload('{{ route('nin.demo.regularSlip', session('verification')['data']['nin']) }}', 'Regular Slip', {{ $regularSlipPrice ?? 0 }})"
                                                        class="w-100 flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/60 text-slate-700 rounded-xl transition duration-200 font-display space-y-1">
                                                    <i data-lucide="file-text" class="w-4.5 h-4.5 text-slate-500"></i>
                                                    <span class="text-xs font-bold mt-1">Regular</span>
                                                    <span class="text-xs font-bold text-slate-400">₦{{ number_format($regularSlipPrice ?? 0, 2) }}</span>
                                                </button>
                                            </div>
                                            <div class="col-4">
                                                <button onclick="confirmDownload('{{ route('nin.demo.standardSlip', session('verification')['data']['nin']) }}', 'Standard Slip', {{ $standardSlipPrice ?? 0 }})"
                                                        class="w-100 flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/60 text-slate-700 rounded-xl transition duration-200 font-display space-y-1">
                                                    <i data-lucide="file-check" class="w-4.5 h-4.5 text-slate-500"></i>
                                                    <span class="text-xs font-bold mt-1">Standard</span>
                                                    <span class="text-xs font-bold text-slate-400">₦{{ number_format($standardSlipPrice ?? 0, 2) }}</span>
                                                </button>
                                            </div>
                                            <div class="col-4">
                                                <button onclick="confirmDownload('{{ route('nin.demo.premiumSlip', session('verification')['data']['nin']) }}', 'Premium Slip', {{ $premiumSlipPrice ?? 0 }})"
                                                        class="w-100 flex flex-col items-center justify-center p-3 bg-primary/10 hover:bg-primary/15 border border-primary/20 text-primary rounded-xl transition duration-200 font-display space-y-1">
                                                    <i data-lucide="award" class="w-4.5 h-4.5 text-[#0056D2]"></i>
                                                    <span class="text-xs font-bold mt-1">Premium</span>
                                                    <span class="text-xs font-bold text-primary/70">₦{{ number_format($premiumSlipPrice ?? 0, 2) }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-12 flex-grow flex flex-col items-center justify-center space-y-4">
                                    <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100 shadow-inner">
                                        <i data-lucide="search" class="w-7 h-7 animate-pulse"></i>
                                    </div>
                                    <div class="space-y-1 max-w-xs mx-auto">
                                        <h4 class="font-bold text-slate-800 text-sm">Awaiting Verification</h4>
                                        <p class="text-xs text-slate-500 leading-relaxed">
                                            Enter details on the left and submit to view the verified demographic identity profile.
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slip Download Script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function confirmDownload(url, type, price) {
            Swal.fire({
                title: 'Confirm Download',
                text: `You will be charged ₦${price.toLocaleString()} for the ${type}. Do you want to proceed?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0056D2',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Proceed!',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'rounded-xl border border-slate-200 shadow-xl p-6',
                    title: 'text-lg font-bold font-display text-slate-900',
                    htmlContainer: 'text-xs text-slate-500 mt-2',
                    confirmButton: 'px-5 py-2.5 bg-[#0056D2] hover:bg-[#003a8c] text-white font-bold text-xs rounded-xl shadow-md transition-all duration-200 mx-2 focus:outline-none',
                    cancelButton: 'px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl shadow-md transition-all duration-200 mx-2 focus:outline-none'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    </script>
</x-app-layout>
