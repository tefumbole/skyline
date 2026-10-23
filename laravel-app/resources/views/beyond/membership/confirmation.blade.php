@extends('beyond.layout')
@section('title', 'Membership received · Skyline Hotel')

@section('content')
<div class="bg-[#0c1830] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-xl mx-auto rounded-2xl border border-brand-gold/40 bg-white/5 p-6">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0">Skyline Hotel</p>
        <h1 class="font-serif text-3xl mt-2">Application received</h1>
        <p class="text-white/75 mt-3">Reference <strong class="text-white">{{ $application->reference }}</strong>. Staff will review your ID and signature.</p>
        @if ((float) $application->fee > 0)
            <p class="text-white/75">The fee is {{ number_format((float) $application->fee, 0) }} XAF{{ $plan ? ' for '.$plan->name : '' }}. Pay it at the hotel. Membership starts when staff approve the application.</p>
        @else
            <p class="text-white/75">This application uses the free membership. It starts when staff approve it and lasts 90 days.</p>
        @endif
        <a href="{{ url('/') }}" class="inline-block mt-5 text-brand-gold underline">Back to Skyline Hotel</a>
    </div>
</div>
@endsection
