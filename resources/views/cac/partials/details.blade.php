@php
    $d = $registration->form_data;
    $kind = \App\Support\CacRequirements::get($registration->category)['kind'] ?? 'individual';
    $people = $d['people'] ?? [];
    $row = fn ($k, $v) => $v !== null && $v !== '' ? '<div class="flex justify-between gap-4 text-xs py-1.5 border-b border-slate-50 last:border-0"><span class="font-semibold text-slate-400 shrink-0">' . e($k) . '</span><span class="font-bold text-slate-700 text-right break-words">' . nl2br(e($v)) . '</span></div>' : '';
@endphp

<div class="space-y-5">
    <div>
        <h4 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Name choices</h4>
        {!! $row('1st choice', $d['names'][0] ?? null) !!}
        {!! $row('2nd choice', $d['names'][1] ?? null) !!}
    </div>

    @if (!empty($d['objectives']))
        <div>
            <h4 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Objectives</h4>
            <p class="text-xs text-slate-700 whitespace-pre-line">{{ $d['objectives'] }}</p>
        </div>
    @endif

    @foreach ($people as $i => $p)
        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3">
            <h4 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-1">
                {{ ['individual' => 'Applicant', 'shareholders' => 'Shareholder', 'trustees' => 'Trustee'][$kind] }} {{ $kind === 'individual' ? '' : $i + 1 }}
                @if ($kind === 'trustees')
                    @if (($d['chairman'] ?? -1) === $i) <span class="ml-1 text-primary normal-case">· Chairman</span> @endif
                    @if (($d['secretary'] ?? -1) === $i) <span class="ml-1 text-primary normal-case">· Secretary</span> @endif
                @endif
            </h4>
            {!! $row('Full name', $p['full_name'] ?? trim(($p['first_name'] ?? '') . ' ' . ($p['other_names'] ?? '') . ' ' . ($p['surname'] ?? ''))) !!}
            {!! $row('Phone', $p['phone'] ?? null) !!}
            {!! $row('Email', $p['email'] ?? null) !!}
            {!! $row('Address', $p['address'] ?? null) !!}
            {!! $row('NIN', $p['nin'] ?? null) !!}
            {!! $row('State of origin', $p['state_of_origin'] ?? null) !!}
            {!! $row('LGA', $p['lga'] ?? null) !!}
            {!! $row('Date of birth', $p['dob'] ?? null) !!}
            {!! $row('Occupation', $p['occupation'] ?? null) !!}
            {!! $row('Means of ID', isset($p['id_type']) ? (\App\Support\CacRequirements::ID_TYPES[$p['id_type']] ?? $p['id_type']) : null) !!}
            {!! $row('Shareholding', isset($p['percent']) && $p['percent'] !== '' ? rtrim(rtrim(number_format((float) $p['percent'], 2), '0'), '.') . '%' : null) !!}
        </div>
    @endforeach

    <div>
        <h4 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Business</h4>
        {!! $row('Business address', $d['business_address'] ?? null) !!}
        {!! $row('Registered address', $d['registered_address'] ?? null) !!}
        {!! $row($kind === 'shareholders' ? 'Nature of business' : 'Type of business', $d['business_type'] ?? null) !!}
    </div>

    @if (!empty($d['witness']))
        <div>
            <h4 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Witness</h4>
            @foreach (['name' => 'Name', 'phone' => 'Phone', 'email' => 'Email', 'address' => 'Address'] as $k => $l)
                {!! $row($l, $d['witness'][$k] ?? null) !!}
            @endforeach
        </div>
    @endif

    @if (!empty($d['secretary_details']))
        <div>
            <h4 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Company secretary</h4>
            @foreach (['name' => 'Name', 'phone' => 'Phone', 'email' => 'Email', 'address' => 'Address'] as $k => $l)
                {!! $row($l, $d['secretary_details'][$k] ?? null) !!}
            @endforeach
        </div>
    @endif
</div>
