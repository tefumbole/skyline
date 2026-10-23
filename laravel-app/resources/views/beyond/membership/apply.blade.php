@extends('beyond.layout')
@section('title', 'Become a Member · Skyline Hotel')

@push('head')
<style>
    .plan-grid { display: grid; gap: 12px; grid-template-columns: 1fr; }
    @media (min-width: 640px) { .plan-grid { grid-template-columns: 1fr 1fr; } }
    .plan-card {
        display: flex; flex-direction: column; gap: 4px; text-decoration: none;
        background: #fff; color: #111; border-radius: 18px; padding: 16px 16px 14px;
        border: 1px solid rgba(0,0,0,.08); min-height: 148px;
    }
    .plan-card:hover { border-color: #C5A059; }
    .plan-badge {
        align-self: flex-start; font-size: 10px; letter-spacing: .14em; text-transform: uppercase;
        font-weight: 700; border-radius: 999px; padding: 3px 8px; background: #111; color: #C5A059;
    }
    .plan-name { font-weight: 700; }
    .plan-price { font-size: 1.35rem; font-weight: 800; }
    .plan-meta, .plan-hint { font-size: .85rem; color: #6b7280; }
</style>
@endpush

@section('content')
<div class="bg-[#F7F1E8] text-[#0A0A0A] min-h-[70vh] py-10 px-4">
    <div class="max-w-4xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-[#C5A059] m-0">Skyline Hotel</p>
        <h1 class="font-serif text-3xl text-[#0A0A0A] mt-2">Become a Member</h1>
        @if (empty($ready))
            <p class="mt-6 text-black/70">Membership is being prepared. Please try again shortly.</p>
        @else
            <p class="text-black/70 text-sm mt-3">Members receive a 10% discount. The free offer, when it is open, lasts 90 days and has no registration fee. Paid plans are collected at the hotel when staff approve the application.</p>

            <div class="plan-grid mt-6">
                @if ($promo)
                    <a class="plan-card" href="{{ url('/register-now/details?plan=promo') }}">
                        <span class="plan-badge">Promotion</span>
                        <span class="plan-name">{{ $promo->name }}</span>
                        <span class="plan-price">0 <span class="text-sm font-semibold">XAF</span></span>
                        <span class="plan-meta">{{ (int) ($promo->free_days ?: 90) }} days</span>
                        <span class="plan-hint">Tap to continue</span>
                    </a>
                @endif
                @foreach ($plans as $plan)
                    @php $months = (int) $plan->duration_months; $span = $months === 1 ? '1 Month' : $months.' Months'; @endphp
                    <a class="plan-card" href="{{ url('/register-now/details?plan='.$plan->id) }}">
                        <span class="plan-badge">{{ $span }}</span>
                        <span class="plan-name">{{ $plan->name }}</span>
                        <span class="plan-price">{{ number_format((float) $plan->fee, 0) }} <span class="text-sm font-semibold">XAF</span></span>
                        <span class="plan-meta">{{ $span }}</span>
                        <span class="plan-hint">Tap to continue</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
