@extends('beyond.layout')
@section('title', 'Payment · '.$reservation->reference)
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
    .coth-pay-card {
        border: 1px solid rgba(255,255,255,.14); background: rgba(255,255,255,.03);
        transition: border-color .15s ease, background .15s ease, transform .15s ease;
        cursor: pointer;
    }
    .coth-pay-card:hover { border-color: rgba(197,160,89,.7); transform: translateY(-2px); }
    .coth-pay-card.is-selected { border-color: #E8C878; background: rgba(197,160,89,.1); }
    .coth-pay-card.is-disabled { opacity: .5; pointer-events: none; }
</style>
@endpush
@section('content')
<div class="coth-shell text-white min-h-[80vh] py-6 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <p class="text-brand-gold font-serif text-xl tracking-[0.25em] uppercase m-0">CHILL ON THE HILL</p>
            <a href="{{ route('coth.verify', $reservation->public_token) }}" class="text-sm text-brand-gold">&larr; Back to verification</a>
        </div>

        <div class="mt-4 mb-6">
            @include('beyond.coth._stepper', ['current' => 3])
        </div>

        @if (session('not_permitted'))
            <div class="max-w-5xl mx-auto rounded-xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm mb-4">{{ session('not_permitted') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <div class="lg:col-span-3 coth-card rounded-3xl p-6 lg:p-8">
                <h1 class="font-serif text-3xl m-0">Choose how to pay</h1>
                <p class="text-white/70 mt-1 mb-4">
                    @if ($reservation->pay_choice === 'full')
                        You chose to pay the full amount.
                    @else
                        You chose to pay the 20% deposit now.
                    @endif
                    Paying <span class="font-semibold text-brand-gold">{{ number_format((int) $reservation->amount_due) }} FCFA</span> confirms your table for the date shown.
                </p>

                @if ($reservation->status === 'held' && $reservation->hold_expires_at)
                    <p class="text-sm text-brand-gold mb-4">Hold expires at {{ $reservation->hold_expires_at->timezone('Africa/Douala')->format('H:i') }} Cameroon time ({{ $reservation->hold_expires_at->diffForHumans() }}).</p>
                @endif

                <form method="POST" action="{{ route('coth.pay', $reservation->public_token) }}" id="coth-pay-form" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @csrf
                    <input type="hidden" name="method" id="coth-pay-method" value="momo">

                    <label class="coth-pay-card rounded-2xl p-5 is-selected {{ $momo ? '' : 'is-disabled' }}" data-coth-pay="momo">
                        <input type="radio" name="method_radio" value="momo" class="sr-only" @if($momo) checked @endif @if(!$momo) disabled @endif>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-yellow-400 text-black font-black text-lg">$</span>
                            <span class="text-brand-gold font-bold text-sm">Mobile Money</span>
                        </div>
                        <h3 class="font-serif text-lg mt-3 mb-1">MTN / Orange / Yoomee</h3>
                        <p class="text-xs text-white/60">Pay through Campay via your preferred mobile wallet.</p>
                        @if (! $momo)<p class="text-xs text-red-300 mt-2">Not configured.</p>@endif
                    </label>

                    <label class="coth-pay-card rounded-2xl p-5 {{ $card ? '' : 'is-disabled' }}" data-coth-pay="card">
                        <input type="radio" name="method_radio" value="card" class="sr-only" @if(!$card) disabled @endif>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center justify-center w-14 h-10 rounded-md bg-white text-blue-900 font-black text-sm tracking-[.14em]">VISA</span>
                            <span class="text-brand-gold font-bold text-sm">Card</span>
                        </div>
                        <h3 class="font-serif text-lg mt-3 mb-1">Visa / Mastercard</h3>
                        <p class="text-xs text-white/60">Pay with any Visa or Mastercard via Stripe.</p>
                        @if (! $card)<p class="text-xs text-red-300 mt-2">Not configured.</p>@endif
                    </label>

                    <button class="sm:col-span-2 w-full bg-brand-gold text-[#0A0A0A] font-bold uppercase tracking-[0.12em] rounded-full px-5 py-3 mt-2" type="submit">
                        Pay {{ number_format((int) $reservation->amount_due) }} FCFA
                    </button>
                </form>

                <p class="text-xs text-white/55 mt-5">By continuing you accept that the deposit is non-refundable and that this booking covers only the date shown.</p>
            </div>

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
    var cards = document.querySelectorAll('[data-coth-pay]');
    var hidden = document.getElementById('coth-pay-method');
    if (!cards.length || !hidden) return;

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            if (card.classList.contains('is-disabled')) return;
            cards.forEach(function (c) { c.classList.remove('is-selected'); });
            card.classList.add('is-selected');
            hidden.value = card.getAttribute('data-coth-pay');
            var radio = card.querySelector('input[type=radio]');
            if (radio) radio.checked = true;
        });
    });
})();
</script>
@endpush
