@extends('beyond.layout')
@section('title', $reservation->reference)
@section('body_class', 'bg-[#0A0A0A]')
@section('content')
@php $table = $reservation->table; @endphp
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold">Chill on the Hill</p>
        <h1 class="font-serif text-3xl mt-2">{{ $reservation->status === 'confirmed' ? 'Reservation confirmed' : 'Reservation '.$reservation->status }}</h1>
        <p class="text-lg">{{ $reservation->event_date->format('l, j F Y') }} · one day</p>
        <dl class="mt-4 grid grid-cols-2 gap-y-2 text-sm">
            <div>Reference</div><div>{{ $reservation->reference }}</div>
            <div>Name</div><div>{{ $reservation->customer_name }}</div>
            <div>Section</div><div>{{ optional(optional($table)->section)->name }}</div>
            <div>Table</div><div>{{ $table ? $table->code : '' }} · {{ $table ? $table->capacity : '' }} seats</div>
            <div>Listed price</div><div>{{ number_format($reservation->list_price) }} FCFA</div>
            <div>Discount</div><div>{{ number_format($reservation->discount_amount) }} FCFA</div>
            <div>Final price</div><div>{{ number_format($reservation->final_price) }} FCFA</div>
            <div>Paid</div><div>{{ number_format($reservation->amount_paid) }} FCFA</div>
            <div>Balance</div><div>{{ number_format($reservation->balance) }} FCFA</div>
            <div>Payment</div><div>{{ $reservation->payment_status }}</div>
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
</div>
@endsection
