<x-app-layout>
    <title>SimPhix - {{ $def['label'] }} Registration</title>

    @php
        $kind = $def['kind'];
        $isTrustee = $kind === 'trustees';
        $isLtd = $kind === 'shareholders';
        $oldPeople = old('people');
        $initialPeople = $oldPeople ? array_values($oldPeople) : array_fill(0, $def['people_min'], new stdClass());
        $canAfford = $balance >= $price;
        $label = fn ($t) => '<label class="block text-xs font-bold text-slate-600 mb-1">' . $t . '</label>';
        $inputCls = 'w-full text-sm rounded-lg border border-slate-200 px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary';
        $fileCls = 'block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary hover:file:bg-primary/20';
    @endphp

    <div class="max-w-5xl mx-auto space-y-6">
        <a href="{{ route('cac.overview') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to CAC Registration
        </a>

        <div class="flex items-center gap-3 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <i data-lucide="{{ $def['icon'] }}" class="w-6 h-6"></i>
            </div>
            <div class="flex-1">
                <h1 class="text-xl font-extrabold font-display text-slate-900">{{ $def['label'] }} Registration</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ $def['tagline'] }}</p>
            </div>
            <div class="text-right shrink-0">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fee</span>
                <span class="text-xl font-extrabold text-primary font-display">₦{{ number_format($price, 2) }}</span>
            </div>
        </div>

        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 text-sm font-semibold">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800">
                <p class="text-sm font-bold mb-1">Please fix the following:</p>
                <ul class="list-disc pl-5 space-y-0.5 text-xs font-semibold">
                    @foreach (collect($errors->all())->unique()->take(12) as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <p class="text-[11px] mt-2 text-rose-600">File uploads must be re-selected after an error.</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <form method="POST" action="{{ route('cac.store', $def['slug']) }}" enctype="multipart/form-data"
                  class="lg:col-span-2 space-y-6"
                  x-data="{
                      people: @js($initialPeople),
                      max: 15,
                      min: {{ $def['people_min'] }},
                      addPerson() { if (this.people.length < this.max) this.people.push({}); },
                      removePerson(i) { if (this.people.length > this.min) this.people.splice(i, 1); }
                  }">
                @csrf

                <!-- Names -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100">1. {{ $isTrustee ? 'Proposed names' : 'Name choices' }}</h3>
                    <p class="text-xs text-slate-400">In order of preference; CAC approves one.{{ $isTrustee ? ' The name must not include “Limited” or “Ltd”.' : '' }}</p>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>{!! $label('1st choice') !!}<input type="text" name="names[0]" value="{{ old('names.0') }}" required class="{{ $inputCls }}"></div>
                        <div>{!! $label('2nd choice') !!}<input type="text" name="names[1]" value="{{ old('names.1') }}" required class="{{ $inputCls }}"></div>
                    </div>
                </div>

                <!-- Objectives (trustees) -->
                @if ($isTrustee)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100">2. Aims &amp; objectives</h3>
                        <p class="text-xs text-slate-400">Activities must be non-profit and for public benefit (charity, education, health, religious, community development, youth empowerment…).</p>
                        <textarea name="objectives" rows="5" required class="{{ $inputCls }}" placeholder="Describe the aims and objectives…">{{ old('objectives') }}</textarea>
                    </div>
                @endif

                <!-- People -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-800 font-display">{{ $isTrustee ? '3' : '2' }}. {{ $def['people_label'] }}{{ $kind === 'individual' ? ' details' : 's' }}</h3>
                        @if ($kind !== 'individual')
                            <button type="button" @click="addPerson()" x-show="people.length < max"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-primary bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-lg transition-colors">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add {{ strtolower($def['people_label']) }}
                            </button>
                        @endif
                    </div>
                    @if ($isTrustee)
                        <p class="text-xs text-slate-400 -mt-2">Minimum 2 trustees (3–5 recommended), all 18 years or older.</p>
                    @endif
                    @if ($isLtd)
                        <p class="text-xs text-slate-400 -mt-2">Add every shareholder. With more than one, each needs a shareholding percentage and the total must equal 100%.</p>
                    @endif

                    <template x-for="(p, i) in people" :key="i">
                        <div class="border border-slate-200 rounded-xl p-4 space-y-4 bg-slate-50/40">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">{{ $def['people_label'] }} <span x-text="i + 1"></span></span>
                                @if ($kind !== 'individual')
                                    <button type="button" @click="removePerson(i)" x-show="people.length > min" class="text-xs font-bold text-rose-600 hover:underline">Remove</button>
                                @endif
                            </div>

                            @if ($isTrustee)
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2">{!! $label('Full name') !!}<input type="text" :name="`people[${i}][full_name]`" x-model="p.full_name" required class="{{ $inputCls }}"></div>
                                    <div>{!! $label('Phone number') !!}<input type="text" :name="`people[${i}][phone]`" x-model="p.phone" required class="{{ $inputCls }}"></div>
                                    <div>{!! $label('Email address') !!}<input type="email" :name="`people[${i}][email]`" x-model="p.email" required class="{{ $inputCls }}"></div>
                                    <div>{!! $label('Occupation') !!}<input type="text" :name="`people[${i}][occupation]`" x-model="p.occupation" required class="{{ $inputCls }}"></div>
                                    <div>{!! $label('Date of birth (18+)') !!}<input type="date" :name="`people[${i}][dob]`" x-model="p.dob" required max="{{ now()->subYears(18)->format('Y-m-d') }}" class="{{ $inputCls }}"></div>
                                    <div class="sm:col-span-2">{!! $label('Residential address') !!}<textarea rows="2" :name="`people[${i}][address]`" x-model="p.address" required class="{{ $inputCls }}"></textarea></div>
                                    <div class="sm:col-span-2">{!! $label('Means of ID') !!}
                                        <select :name="`people[${i}][id_type]`" x-model="p.id_type" required class="{{ $inputCls }}">
                                            <option value="">Select…</option>
                                            @foreach (\App\Support\CacRequirements::ID_TYPES as $v => $l)
                                                <option value="{{ $v }}">{{ $l }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>{!! $label('Upload ID (image/PDF)') !!}<input type="file" :name="`files[people][${i}][id_file]`" accept=".jpg,.jpeg,.png,.pdf" required class="{{ $fileCls }}"></div>
                                    <div>{!! $label('Passport photograph (white background)') !!}<input type="file" :name="`files[people][${i}][passport]`" accept=".jpg,.jpeg,.png,.pdf" required class="{{ $fileCls }}"></div>
                                </div>
                            @else
                                <div class="grid sm:grid-cols-3 gap-4">
                                    <div>{!! $label('First name') !!}<input type="text" :name="`people[${i}][first_name]`" x-model="p.first_name" required class="{{ $inputCls }}"></div>
                                    <div>{!! $label('Surname') !!}<input type="text" :name="`people[${i}][surname]`" x-model="p.surname" required class="{{ $inputCls }}"></div>
                                    <div>{!! $label('Other names') !!}<input type="text" :name="`people[${i}][other_names]`" x-model="p.other_names" class="{{ $inputCls }}"></div>
                                    <div>{!! $label('Phone number') !!}<input type="text" :name="`people[${i}][phone]`" x-model="p.phone" required class="{{ $inputCls }}"></div>
                                    <div class="sm:col-span-2">{!! $label('Email address') !!}<input type="email" :name="`people[${i}][email]`" x-model="p.email" required class="{{ $inputCls }}"></div>
                                    <div class="sm:col-span-3">{!! $label('Residential address' . ($isLtd ? ' (house number, street name & Local Government)' : '')) !!}<textarea rows="2" :name="`people[${i}][address]`" x-model="p.address" required class="{{ $inputCls }}"></textarea></div>
                                    <div>{!! $label('NIN (11 digits)') !!}<input type="text" inputmode="numeric" maxlength="11" :name="`people[${i}][nin]`" x-model="p.nin" required class="{{ $inputCls }}"></div>
                                    <div>{!! $label('State of origin') !!}<input type="text" :name="`people[${i}][state_of_origin]`" x-model="p.state_of_origin" required class="{{ $inputCls }}"></div>
                                    <div>{!! $label('LGA') !!}<input type="text" :name="`people[${i}][lga]`" x-model="p.lga" required class="{{ $inputCls }}"></div>
                                    <div>{!! $label('Date of birth') !!}<input type="date" :name="`people[${i}][dob]`" x-model="p.dob" required max="{{ now()->subYears(18)->format('Y-m-d') }}" class="{{ $inputCls }}"></div>
                                    @if ($isLtd)
                                        <div x-show="people.length > 1">{!! $label('Shareholding %') !!}<input type="number" step="0.01" min="0.01" max="100" :name="`people[${i}][percent]`" x-model="p.percent" :required="people.length > 1" class="{{ $inputCls }}"></div>
                                    @endif
                                </div>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>{!! $label('Passport photograph') !!}<input type="file" :name="`files[people][${i}][passport]`" accept=".jpg,.jpeg,.png,.pdf" required class="{{ $fileCls }}"></div>
                                    <div>{!! $label('Image of NIN slip') !!}<input type="file" :name="`files[people][${i}][nin_slip]`" accept=".jpg,.jpeg,.png,.pdf" required class="{{ $fileCls }}"></div>
                                    <div>{!! $label('Image of signature') !!}<input type="file" :name="`files[people][${i}][signature]`" accept=".jpg,.jpeg,.png,.pdf" required class="{{ $fileCls }}"></div>
                                    @if ($isLtd)
                                        <div>{!! $label('ID card') !!}<input type="file" :name="`files[people][${i}][id_card]`" accept=".jpg,.jpeg,.png,.pdf" required class="{{ $fileCls }}"></div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </template>
                </div>

                <!-- Chairman & Secretary -->
                @if ($isTrustee)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100">4. Chairman &amp; Secretary</h3>
                        <p class="text-xs text-slate-400">Chosen from the trustees above.</p>
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach (['chairman' => 'Chairman of Trustees', 'secretary' => 'Secretary'] as $field => $title)
                                <div>{!! $label($title) !!}
                                    <select name="{{ $field }}" required class="{{ $inputCls }}">
                                        <option value="">Select trustee…</option>
                                        <template x-for="(p, i) in people" :key="i">
                                            <option :value="i" :selected="String(i) === '{{ old($field) }}'" x-text="'Trustee ' + (i + 1) + (p.full_name ? ' — ' + p.full_name : '')"></option>
                                        </template>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Business / registered address -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100">{{ $isTrustee ? '5. Registered address' : '3. Business details' }}</h3>
                    @if ($isTrustee)
                        <div>{!! $label('Physical address in Nigeria (no P.O. Box)') !!}<textarea name="registered_address" rows="2" required class="{{ $inputCls }}">{{ old('registered_address') }}</textarea></div>
                    @else
                        <div>{!! $label('Business address' . ($isLtd ? ' (shop/house number, street name & Local Government)' : '')) !!}<textarea name="business_address" rows="2" required class="{{ $inputCls }}">{{ old('business_address') }}</textarea></div>
                        <div>{!! $label($isLtd ? 'Nature of business(es)' : 'Type of business') !!}<input type="text" name="business_type" value="{{ old('business_type') }}" required class="{{ $inputCls }}"></div>
                    @endif
                </div>

                <!-- Witness & secretary (LTD) -->
                @if ($isLtd)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100">4. Witness</h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>{!! $label('Name') !!}<input type="text" name="witness[name]" value="{{ old('witness.name') }}" required class="{{ $inputCls }}"></div>
                            <div>{!! $label('Phone number') !!}<input type="text" name="witness[phone]" value="{{ old('witness.phone') }}" required class="{{ $inputCls }}"></div>
                            <div>{!! $label('Email address') !!}<input type="email" name="witness[email]" value="{{ old('witness.email') }}" required class="{{ $inputCls }}"></div>
                            <div>{!! $label('Signature (image)') !!}<input type="file" name="files[witness_signature]" accept=".jpg,.jpeg,.png,.pdf" required class="{{ $fileCls }}"></div>
                            <div class="sm:col-span-2">{!! $label('Home address') !!}<textarea name="witness[address]" rows="2" required class="{{ $inputCls }}">{{ old('witness.address') }}</textarea></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100">5. Company secretary <span class="text-slate-400 font-medium">(optional)</span></h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>{!! $label('Name') !!}<input type="text" name="secretary_details[name]" value="{{ old('secretary_details.name') }}" class="{{ $inputCls }}"></div>
                            <div>{!! $label('Phone number') !!}<input type="text" name="secretary_details[phone]" value="{{ old('secretary_details.phone') }}" class="{{ $inputCls }}"></div>
                            <div class="sm:col-span-2">{!! $label('Email address') !!}<input type="email" name="secretary_details[email]" value="{{ old('secretary_details.email') }}" class="{{ $inputCls }}"></div>
                            <div class="sm:col-span-2">{!! $label('Address') !!}<textarea name="secretary_details[address]" rows="2" class="{{ $inputCls }}">{{ old('secretary_details.address') }}</textarea></div>
                        </div>
                    </div>
                @endif

                <!-- Documents (trustees) -->
                @if ($isTrustee)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 font-display pb-3 border-b border-slate-100">6. Documents</h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>{!! $label('Minutes of meeting (appointing trustees & approving name, trustees, objectives, constitution)') !!}<input type="file" name="files[minutes]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required class="{{ $fileCls }}"></div>
                            <div>{!! $label('Constitution') !!}<input type="file" name="files[constitution]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required class="{{ $fileCls }}"></div>
                            <div class="sm:col-span-2">{!! $label('CAC forms signed by Chairman & Secretary, with Declaration of Compliance (Form IT)') !!}<input type="file" name="files[cac_forms]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required class="{{ $fileCls }}"></div>
                        </div>
                    </div>
                @endif

                <!-- Submit -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 font-semibold">Wallet balance</span>
                        <span class="font-extrabold {{ $canAfford ? 'text-slate-800' : 'text-rose-600' }}">₦{{ number_format($balance, 2) }}</span>
                    </div>
                    @unless ($canAfford)
                        <p class="text-xs font-semibold text-rose-600">Your wallet doesn't cover the ₦{{ number_format($price, 2) }} fee. <a href="{{ route('wallet') }}" class="underline">Fund your wallet</a> first.</p>
                    @endunless
                    <p class="text-[11px] text-slate-400">The fee is debited when you submit and refunded automatically if the registration is rejected or cancelled.</p>
                    <button type="submit" @disabled(!$canAfford)
                            class="w-full inline-flex items-center justify-center gap-2 bg-primary hover:bg-[#0049b8] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm px-4 py-3 rounded-xl transition-colors">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Submit &amp; Pay ₦{{ number_format($price, 2) }}
                    </button>
                </div>
            </form>

            <!-- Requirements sidebar -->
            <aside class="space-y-4 lg:sticky lg:top-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-800 font-display mb-3 flex items-center gap-2"><i data-lucide="list-checks" class="w-4 h-4 text-primary"></i> What you'll need</h3>
                    <ul class="space-y-2">
                        @foreach ($def['checklist'] as $item)
                            <li class="flex items-start gap-2 text-xs text-slate-600"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5"></i><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-800 font-display mb-3 flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4 text-primary"></i> Timeline</h3>
                    <ul class="space-y-2">
                        @foreach ($def['timeline'] as $t)
                            <li class="text-xs text-slate-600">• {{ $t }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-800 font-display mb-3 flex items-center gap-2"><i data-lucide="award" class="w-4 h-4 text-primary"></i> You'll receive</h3>
                    <ul class="space-y-1.5">
                        @foreach ($def['deliverables'] as $d)
                            <li class="text-xs text-slate-600">• {{ $d }}</li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
