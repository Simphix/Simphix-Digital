@props(['name', 'id' => null, 'error' => false, 'length' => 4])
@php $inputId = $id ?? $name; @endphp

{{--
    Digit-box entry, {{ $length }} boxes by default 4 (PIN) — pass :length="6"
    for an OTP code. Digits auto-advance to the next box on input and move
    back on backspace when the current box is empty. The real value is kept
    in a hidden input named {{ $name }} so existing form submits / JS reading
    document.getElementById('{{ $inputId }}').value keep working unchanged.

    External code can:
      - focus the first box: document.getElementById('{{ $inputId }}_1').focus()
      - clear all boxes:     document.getElementById('{{ $inputId }}_wrap').dispatchEvent(new CustomEvent('pin-reset'))
--}}
<div id="{{ $inputId }}_wrap"
     x-data="{
        length: {{ (int) $length }},
        digits: Array({{ (int) $length }}).fill(''),
        get pinValue() { return this.digits.join(''); },
        boxes() { return Array.from({ length: this.length }, (_, i) => this.$refs['box' + (i + 1)]); },
        handleInput(i, e) {
            const val = e.target.value.replace(/[^0-9]/g, '').slice(-1);
            this.digits[i] = val;
            e.target.value = val;
            if (val && i < this.length - 1) this.boxes()[i + 1].focus();
        },
        handleKeydown(i, e) {
            if (e.key === 'Backspace' && !e.target.value && i > 0) {
                const prev = this.boxes()[i - 1];
                this.digits[i - 1] = '';
                prev.value = '';
                prev.focus();
                e.preventDefault();
            }
        },
        handlePaste(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, this.length);
            text.split('').forEach((d, idx) => { this.digits[idx] = d; if (this.boxes()[idx]) this.boxes()[idx].value = d; });
            const nextIndex = Math.min(text.length, this.length - 1);
            this.boxes()[nextIndex]?.focus();
        },
        reset() {
            this.digits = Array(this.length).fill('');
            this.boxes().forEach(b => { if (b) b.value = ''; });
        }
     }"
     x-on:pin-reset="reset()"
     class="flex items-center justify-center gap-3">
    <input type="hidden" id="{{ $inputId }}" name="{{ $name }}" x-bind:value="pinValue">
    @for ($i = 0; $i < $length; $i++)
        <input id="{{ $inputId }}_{{ $i + 1 }}" x-ref="box{{ $i + 1 }}"
               type="password" inputmode="numeric" maxlength="1" autocomplete="off"
               x-on:input="handleInput({{ $i }}, $event)"
               x-on:keydown="handleKeydown({{ $i }}, $event)"
               x-on:paste="handlePaste($event)"
               @class([
                   'w-14 h-14 text-center text-2xl font-bold rounded-xl transition-all focus:outline-none focus:ring-2',
                   'border border-rose-400 focus:border-rose-400 focus:ring-rose-100' => $error,
                   'border border-slate-200 focus:border-primary focus:ring-primary/20' => !$error,
               ])>
    @endfor
</div>
