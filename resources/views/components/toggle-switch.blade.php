@props(['name' => null, 'checked' => false, 'id' => null])
@php $inputId = $id ?? $name; @endphp

{{-- Presentational only — pairs with x-model or plain :checked/name for a
     form submit; the surrounding form decides what happens on change. --}}
<label {{ $attributes->merge(['class' => 'relative inline-flex items-center cursor-pointer']) }}>
    <input type="checkbox"
           @if ($name) name="{{ $name }}" @endif
           @if ($inputId) id="{{ $inputId }}" @endif
           value="1"
           @checked($checked)
           class="peer sr-only">
    <div class="w-11 h-6 bg-slate-200 rounded-full peer-checked:bg-emerald-500 transition-colors duration-200
                after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                after:h-5 after:w-5 after:shadow-sm after:transition-transform after:duration-200
                peer-checked:after:translate-x-5"></div>
</label>
