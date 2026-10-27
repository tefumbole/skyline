@extends('beyond.layout')
@section('title', 'Verify · '.$reservation->reference)
@section('body_class', 'bg-[#0A0A0A]')
@section('content')
@php $table = $reservation->table; @endphp
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold">One day · {{ $reservation->event_date->format('l, j F Y') }}</p>
        <h1 class="font-serif text-3xl mt-2">{{ $reservation->reference }}</h1>
        <p>{{ $reservation->customer_name }} · {{ $table ? $table->code : '' }} · {{ optional(optional($table)->section)->name }} · {{ $table ? $table->capacity : '' }} seats</p>
        <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
            <div>Listed</div><div>{{ number_format($reservation->list_price) }} FCFA</div>
            <div>Discount</div><div>{{ number_format($reservation->discount_amount) }} FCFA</div>
            <div>Final</div><div>{{ number_format($reservation->final_price) }} FCFA</div>
            <div>Deposit due</div><div>{{ number_format($reservation->deposit_due) }} FCFA</div>
            <div>Paying now</div><div>{{ number_format($reservation->amount_due) }} FCFA</div>
            <div>Balance after payment</div><div>{{ number_format($reservation->final_price - $reservation->amount_due) }} FCFA</div>
        </dl>
        @if ($reservation->status === 'held' && $reservation->hold_expires_at)
            <p class="text-sm text-brand-gold mt-4">This hold expires {{ $reservation->hold_expires_at->timezone('Africa/Douala')->format('H:i') }} Cameroon time.</p>
        @endif
        @if ($reservation->status !== 'held')
            <p class="mt-4">This hold is {{ $reservation->status }}. <a class="text-brand-gold underline" href="{{ route('coth.index') }}">Choose another table</a>.</p>
        @elseif (! $reservation->phone_verified_at)
            @if (session('not_permitted'))<div class="mt-4 text-sm">{{ session('not_permitted') }}</div>@endif
            @if (session('message'))<div class="mt-4 text-sm">{{ session('message') }}</div>@endif
            <form method="POST" action="{{ route('coth.otp', $reservation->public_token) }}" class="mt-6 space-y-3">
                @csrf
                <label class="block text-sm">Enter the code sent to {{ $reservation->phone }}</label>
                <input name="otp" inputmode="numeric" maxlength="6" required class="w-full rounded-md bg-black border border-white/15 px-3 py-2 tracking-[0.3em] text-center">
                <button class="bg-brand-gold text-[#0A0A0A] font-bold rounded-full px-5 py-3" type="submit">Verify phone</button>
            </form>
            <form method="POST" action="{{ route('coth.resend', $reservation->public_token) }}" class="mt-3">
                @csrf
                <button class="text-sm text-brand-gold underline" type="submit">Resend code</button>
            </form>
        @else
            @if (session('not_permitted'))<div class="mt-4 text-sm">{{ session('not_permitted') }}</div>@endif
            <p class="text-sm mt-4">The deposit is non-refundable. Payment confirms this table for the date above only.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                @if ($momo)
                    <form method="POST" action="{{ route('coth.pay', $reservation->public_token) }}">@csrf<input type="hidden" name="method" value="momo"><button class="bg-brand-gold text-[#0A0A0A] font-bold rounded-full px-5 py-3" type="submit">Pay with MoMo</button></form>
                @else
                    <p class="text-sm text-white/60">MoMo is not configured.</p>
                @endif
                @if ($card)
                    <form method="POST" action="{{ route('coth.pay', $reservation->public_token) }}">@csrf<input type="hidden" name="method" value="card"><button class="border border-brand-gold text-brand-gold font-bold rounded-full px-5 py-3" type="submit">Pay with Visa / card</button></form>
                @else
                    <p class="text-sm text-white/60">Card payment is not configured.</p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
