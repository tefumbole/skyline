@extends('beyond.layout')
@section('title', $reservation->reference)
@section('body_class', 'bg-[#24160F]')
@section('content')
@php
    $table = $reservation->table;
    $extraQty = (int) $reservation->extra_chairs_qty;
    $totalGuests = ($table ? (int) $table->capacity : 0) + $extraQty;
    $visuals = app(\App\Services\Coth\CothVisualLibrary::class);
    $chip = $table ? $visuals->chipFor($table) : '';
@endphp
<div class="text-white min-h-[70vh] py-10 px-4" style="background:#24160F;color:#F6EFE4;">
    <div class="max-w-5xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold">Chill on the Hill</p>
        <h1 class="font-serif text-3xl mt-2">{{ $reservation->status === 'confirmed' ? 'Reservation confirmed' : 'Reservation '.$reservation->status }}</h1>
        <p class="text-lg">{{ $reservation->event_date->format('l, j F Y') }} · one day</p>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mt-5">
            <div class="lg:col-span-3 rounded-3xl border border-brand-gold/30 p-6" style="background:#3C2A1E;">
                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <div class="text-white/60">Reference</div><div>{{ $reservation->reference }}</div>
                    <div class="text-white/60">Name</div><div>{{ $reservation->customer_name }}</div>
                    <div class="text-white/60">Phone</div><div>+{{ $reservation->phone }}</div>
                    <div class="text-white/60">Section</div><div>{{ optional(optional($table)->section)->name }}</div>
                    <div class="text-white/60">Table</div>
                    <div>
                        {{ $table ? $table->code : '' }} · {{ $table ? $table->capacity : '' }} seats
                        @if ($chip)<span class="text-brand-gold">· {{ $chip }}</span>@endif
                    </div>

                    @if ($extraQty > 0)
                        <div class="text-white/60">Extra chairs</div>
                        <div>{{ $extraQty }} × {{ number_format((int) $reservation->extra_chair_rate) }} FCFA</div>
                        <div class="text-white/60">Guests</div>
                        <div>{{ $totalGuests }} ({{ $table ? $table->capacity : 0 }} included + {{ $extraQty }} extra)</div>
                    @endif

                    <div class="text-white/60 border-t border-white/10 pt-2 mt-2">Listed table</div>
                    <div class="border-t border-white/10 pt-2 mt-2">{{ number_format((int) $reservation->list_price) }} FCFA</div>
                    @if ((int) $reservation->discount_amount > 0)
                        <div class="text-emerald-300">Discount</div>
                        <div class="text-emerald-300">−{{ number_format((int) $reservation->discount_amount) }} FCFA</div>
                    @endif
                    <div class="text-white/60">Final table</div><div>{{ number_format((int) $reservation->final_price) }} FCFA</div>
                    @if ($extraQty > 0)
                        <div class="text-white/60">Chairs total</div><div>{{ number_format((int) $reservation->extra_chairs_amount) }} FCFA</div>
                    @endif
                    <div class="font-semibold border-t border-white/10 pt-2 mt-2">Booking total</div>
                    <div class="font-semibold border-t border-white/10 pt-2 mt-2">{{ number_format((int) ($reservation->booking_total ?: $reservation->final_price)) }} FCFA</div>
                    <div class="text-white/60">Paid</div><div>{{ number_format((int) $reservation->amount_paid) }} FCFA</div>
                    <div class="text-brand-gold font-semibold">Balance</div><div class="text-brand-gold font-semibold">{{ number_format((int) $reservation->balance) }} FCFA</div>
                    <div class="text-white/60">Payment</div><div>{{ $reservation->payment_status }}</div>
                </dl>
                <p class="text-sm text-white/70 mt-4">The deposit is non-refundable. This link is your reservation record.</p>
                @if ($reservation->payment_status === 'needs_reconciliation')
                    <p class="text-sm mt-3">A payment arrived after the hold ended. Staff will reconcile it. This table was not reserved automatically.</p>
                @endif
                @if ($qr)
                    <img src="{{ $qr }}" alt="Invoice QR" class="mt-4 w-36 h-36 bg-white p-2 rounded">
                @endif
                @if ($verifyUrl)
                    <p class="text-sm mt-2"><a class="text-brand-gold underline" href="{{ $verifyUrl }}">Staff invoice check</a></p>
                @endif
            </div>

            <div class="lg:col-span-2">
                @include('beyond.coth._selection', ['reservation' => $reservation])
            </div>
        </div>
    </div>
</div>
@endsection
