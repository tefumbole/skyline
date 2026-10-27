@extends('beyond.layout')
@section('title', 'Reserve '.$table->code)
@section('body_class', 'bg-[#0A0A0A]')
@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-xl mx-auto">
        <a href="{{ route('coth.tables', ['date' => $date, 'section' => $table->section->code]) }}" class="text-xs uppercase tracking-[0.18em] text-brand-gold">Back to tables</a>
        <h1 class="font-serif text-3xl mt-2">{{ $table->code }}</h1>
        <p class="text-white/70">{{ $table->section->name }} · {{ $table->capacity }} seats{{ $table->isCouch() ? ' · lounge' : '' }}</p>
        <p class="text-lg font-semibold">{{ \Carbon\Carbon::parse($date)->format('l, j F Y') }} · one day</p>
        @if (session('not_permitted'))<div class="mt-4 rounded-xl border border-red-400/40 px-4 py-3 text-sm">{{ session('not_permitted') }}</div>@endif
        <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
            <div>Listed price</div><div>{{ number_format($quote['list_price']) }} FCFA</div>
            <div>Discount</div><div>{{ number_format($quote['discount_amount']) }} FCFA</div>
            <div>Final price</div><div>{{ number_format($quote['final_price']) }} FCFA</div>
            <div>Minimum deposit</div><div>{{ number_format($quote['deposit_due']) }} FCFA</div>
        </dl>
        <p class="text-sm text-white/60 mt-3">The deposit is non-refundable and is 20% of the final price for this date. Paying the deposit leaves the rest as a balance.</p>
        <form method="POST" action="{{ route('coth.hold') }}" class="mt-6 space-y-3">
            @csrf
            <input type="hidden" name="table_id" value="{{ $table->id }}">
            <input type="hidden" name="event_date" value="{{ $date }}">
            <input name="name" value="{{ old('name') }}" required placeholder="Your name" class="w-full rounded-md bg-black border border-white/15 px-3 py-2">
            <input name="phone" value="{{ old('phone') }}" required placeholder="WhatsApp number" class="w-full rounded-md bg-black border border-white/15 px-3 py-2">
            <input name="email" value="{{ old('email') }}" type="email" placeholder="Email, optional" class="w-full rounded-md bg-black border border-white/15 px-3 py-2">
            <label class="flex items-center gap-2"><input type="radio" name="pay_choice" value="deposit" checked> Pay the deposit ({{ number_format($quote['deposit_due']) }} FCFA)</label>
            <label class="flex items-center gap-2"><input type="radio" name="pay_choice" value="full" @if(old('pay_choice')==='full') checked @endif> Pay the full price ({{ number_format($quote['final_price']) }} FCFA)</label>
            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="terms" value="1" required> I accept that the deposit is non-refundable and that this booking is for the date shown only.</label>
            <button class="bg-brand-gold text-[#0A0A0A] font-bold rounded-full px-5 py-3" type="submit">Continue to verification</button>
        </form>
    </div>
</div>
@endsection
