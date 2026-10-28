@extends('beyond.layout')

@section('title', 'Services · Skyline Hotel')
@section('meta_description', 'Restaurant, coffee, and bar at Skyline Hotel.')
@section('body_class', 'bg-[#0A0A0A]')

@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0">Skyline Hotel</p>
        <h1 class="font-serif text-3xl md:text-4xl text-white mt-2">Services</h1>
        <p class="text-white/70 text-sm mt-2 max-w-2xl">Browse every counter. Food, coffee, and drinks still go on one bill.</p>

        <div class="mt-8 grid gap-5 md:grid-cols-2">
            @foreach ($catalog as $key => $svc)
                @php
                    $hero = \App\Support\ServiceCatalog::heroUrl($key);
                    $showUrl = ! empty($svc['url']) ? $svc['url'] : route('beyond.services.show', $key);
                    $action = ! empty($svc['reservation']) ? 'Reserve a table' : 'Browse & order';
                @endphp
                <a href="{{ $showUrl }}" class="rounded-2xl border border-brand-gold/30 bg-white/[0.03] overflow-hidden flex flex-col hover:border-brand-gold">
                    @if ($hero)
                        <img src="{{ $hero }}" alt="" class="w-full h-40 object-cover" loading="lazy">
                    @endif
                    <span class="p-6 flex-1 flex flex-col">
                        <span class="flex items-center gap-3">
                            <span class="w-11 h-11 rounded-full border border-brand-gold/60 flex items-center justify-center text-brand-gold shrink-0">
                                <i data-lucide="{{ $svc['icon'] ?? 'tag' }}" class="w-5 h-5"></i>
                            </span>
                            <h2 class="text-xl font-bold text-white m-0">{{ $svc['label'] }}</h2>
                        </span>
                        <span class="text-white/75 text-sm mt-3 mb-0">{{ $svc['blurb'] }}</span>
                        <span class="mt-5 inline-flex self-start text-xs font-bold tracking-[0.14em] uppercase bg-brand-gold text-[#0A0A0A] rounded-full px-4 py-2">{{ $action }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
