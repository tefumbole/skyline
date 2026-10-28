@extends('beyond.layout')
@section('title', 'Verify phone · '.$reservation->reference)
@section('body_class', 'bg-[#0A0F2A]')
@push('head')
<style>
    .coth-page { background: #0A0F2A; }
    .coth-shell { background: linear-gradient(180deg, #0A0F2A 0%, #0A0A14 100%); }
    .coth-card {
        border: 1px solid rgba(197,160,89,.35);
        background: rgba(255,255,255,.03);
        box-shadow: 0 18px 40px rgba(0,0,0,.45);
    }
    .coth-otp input {
        width: 46px; height: 56px; text-align: center; font-size: 22px; font-weight: 700;
        background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; color: #fff; letter-spacing: .14em;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .coth-otp input:focus { outline: none; border-color: #E8C878; box-shadow: 0 0 0 2px rgba(232,200,120,.35); }
</style>
@endpush
@section('content')
<div class="coth-shell text-white min-h-[80vh] py-6 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <p class="text-brand-gold font-serif text-xl tracking-[0.25em] uppercase m-0">CHILL ON THE HILL</p>
            <a href="{{ route('coth.book', ['date' => $reservation->event_date->toDateString(), 'table' => optional($reservation->table)->code]) }}" class="text-sm text-brand-gold">&larr; Back to details</a>
        </div>

        <div class="mt-4 mb-6">
            @include('beyond.coth._stepper', ['current' => 2])
        </div>

        @if (session('not_permitted'))
            <div class="max-w-5xl mx-auto rounded-xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm mb-4">{{ session('not_permitted') }}</div>
        @endif
        @if (session('message'))
            <div class="max-w-5xl mx-auto rounded-xl border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-sm mb-4">{{ session('message') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            {{-- Form column --}}
            <div class="lg:col-span-3 coth-card rounded-3xl p-6 lg:p-8">
                <h1 class="font-serif text-3xl m-0">Verify your phone</h1>
                <p class="text-white/70 mt-1 mb-4">We've sent a 6-digit code via WhatsApp to <span class="font-semibold text-white">+{{ $reservation->phone }}</span>.</p>

                @if ($reservation->status === 'held' && $reservation->hold_expires_at)
                    <p class="text-sm text-brand-gold mb-4">
                        Hold expires at <span class="font-semibold">{{ $reservation->hold_expires_at->timezone('Africa/Douala')->format('H:i') }}</span> Cameroon time
                        ({{ $reservation->hold_expires_at->diffForHumans() }}).
                    </p>
                @endif

                <form id="coth-otp-form" method="POST" action="{{ route('coth.otp', $reservation->public_token) }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="otp" id="coth-otp-hidden" value="">
                    <label class="block">
                        <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55 mb-2">Enter 6-digit code</span>
                        <div class="coth-otp flex gap-2" aria-label="One-time passcode">
                            @for ($i = 0; $i < 6; $i++)
                                <input type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" data-coth-otp data-idx="{{ $i }}" @if($i === 0) autofocus @endif>
                            @endfor
                        </div>
                    </label>

                    <button class="w-full bg-brand-gold text-[#0A0A0A] font-bold uppercase tracking-[0.12em] rounded-full px-5 py-3" type="submit">Verify &amp; continue to payment</button>
                </form>

                <form method="POST" action="{{ route('coth.resend', $reservation->public_token) }}" class="mt-3">
                    @csrf
                    <button class="text-sm text-brand-gold underline" type="submit">Resend code</button>
                </form>

                <p class="text-xs text-white/55 mt-6">Not your number?
                    <a class="text-brand-gold underline" href="{{ route('coth.book', ['date' => $reservation->event_date->toDateString(), 'table' => optional($reservation->table)->code]) }}">Change it</a>
                    — your hold is kept while you fix the details.
                </p>
            </div>

            {{-- Selection column --}}
            <div class="lg:col-span-2">
                @include('beyond.coth._selection', ['reservation' => $reservation])
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var boxes = Array.prototype.slice.call(document.querySelectorAll('[data-coth-otp]'));
    var hidden = document.getElementById('coth-otp-hidden');
    var form = document.getElementById('coth-otp-form');
    if (!boxes.length || !hidden || !form) return;

    function value() { return boxes.map(function (b) { return b.value.replace(/\D/g, ''); }).join(''); }
    function sync() { hidden.value = value(); }

    boxes.forEach(function (box, i) {
        box.addEventListener('input', function (e) {
            var v = (e.target.value || '').replace(/\D/g, '');
            e.target.value = v.slice(-1); // keep last digit only
            sync();
            if (e.target.value && i < boxes.length - 1) {
                boxes[i + 1].focus();
            }
            if (value().length === 6) {
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !e.target.value && i > 0) {
                boxes[i - 1].focus();
            }
            if (e.key === 'ArrowLeft' && i > 0) { boxes[i - 1].focus(); }
            if (e.key === 'ArrowRight' && i < boxes.length - 1) { boxes[i + 1].focus(); }
        });
        box.addEventListener('paste', function (e) {
            var data = (e.clipboardData || window.clipboardData).getData('text') || '';
            var digits = data.replace(/\D/g, '').slice(0, boxes.length);
            if (!digits) return;
            e.preventDefault();
            for (var j = 0; j < digits.length && (i + j) < boxes.length; j++) {
                boxes[i + j].value = digits[j];
            }
            sync();
            var next = Math.min(i + digits.length, boxes.length - 1);
            boxes[next].focus();
            if (value().length === 6) {
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    });
})();
</script>
@endpush
