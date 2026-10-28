@extends('beyond.layout')
@section('title', 'Choose a table · Chill on the Hill')
@section('body_class', 'bg-[#140E0A]')
@push('head')
<style>
    .coth-wrap { background: #140E0A; color: #F6EFE4; }
    .coth-exp-hero { position: relative; height: 280px; overflow: hidden; }
    .coth-exp-hero img { width: 100%; height: 100%; object-fit: cover; object-position: center 62%; display: block; }
    .coth-exp-hero::after {
        content: ""; position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(20,14,10,.05) 30%, #140E0A 100%);
    }
    .coth-exp-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; }
    .coth-exp-kicker { color: #E8C878; letter-spacing: .18em; font-size: 12px; font-weight: 700; }
    .coth-exp-title { font-family: Georgia, "Times New Roman", serif; font-size: clamp(32px, 4vw, 48px); font-weight: 500; line-height: 1.05; margin: 8px 0 6px; }
    .coth-flow { display: flex; align-items: flex-start; gap: 0; min-width: 340px; }
    .coth-flow-step { display: flex; flex-direction: column; align-items: center; width: 72px; text-align: center; }
    .coth-flow-dot {
        width: 28px; height: 28px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid rgba(246,239,228,.28); color: rgba(246,239,228,.55); font-size: 13px; font-weight: 700; background: #1C1410;
    }
    .coth-flow-step.is-done .coth-flow-dot { background: #C6A15B; color: #1A1008; border-color: #C6A15B; }
    .coth-flow-step.is-on .coth-flow-dot { background: #C6A15B; color: #1A1008; border-color: #C6A15B; }
    .coth-flow-step small { display: block; margin-top: 6px; font-size: 11px; color: rgba(246,239,228,.45); line-height: 1.2; }
    .coth-flow-step.is-done small, .coth-flow-step.is-on small { color: #E8C878; }
    .coth-flow-step b { display: block; font-weight: 600; color: inherit; }
    .coth-flow-line { flex: 1; height: 2px; margin-top: 13px; background: rgba(246,239,228,.18); min-width: 18px; }
    .coth-flow-step.is-done + .coth-flow-line { background: #C6A15B; }
    .coth-exp-h { font-family: Georgia, "Times New Roman", serif; font-size: clamp(34px, 4vw, 52px); font-weight: 500; margin: 28px 0 16px; }
    .coth-exp-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .coth-exp-card {
        display: flex; flex-direction: column; border: 1px solid rgba(198,161,91,.55); border-radius: 18px;
        overflow: hidden; background: #1C1410; color: inherit; text-decoration: none; min-height: 100%;
    }
    .coth-exp-card:hover { border-color: #E8C878; }
    .coth-exp-photo { height: 210px; background: #24160F; display: flex; align-items: center; justify-content: center; padding: 8px; }
    .coth-exp-photo img { width: 100%; height: 100%; object-fit: contain; }
    .coth-exp-body { padding: 16px 16px 18px; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; }
    .coth-exp-body h3 { font-family: Georgia, "Times New Roman", serif; font-size: 22px; font-weight: 500; margin: 0; }
    .coth-exp-count {
        margin-top: 10px; border-radius: 999px; background: rgba(255,255,255,.08); color: rgba(246,239,228,.8);
        font-size: 13px; padding: 4px 12px;
    }
    .coth-exp-pin { margin: 12px 0; color: #E8C878; }
    .coth-exp-go {
        margin-top: auto; width: 100%; border: 1px solid rgba(232,200,120,.7); color: #E8C878; border-radius: 12px;
        padding: 10px 12px; font-size: 15px;
    }
    @media (max-width: 980px) {
        .coth-exp-hero { height: 180px; }
        .coth-exp-head, .coth-flow { display: block; min-width: 0; }
        .coth-flow { display: flex; margin-top: 16px; }
        .coth-exp-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .coth-exp-grid { grid-template-columns: 1fr; }
    }
    .coth-sec {
        border: 1px solid rgba(197,160,89,.30);
        background: rgba(255,255,255,.03);
    }
    .coth-sec.is-active { border-color: #C5A059; background: rgba(197,160,89,.12); }
    .coth-card {
        border: 1px solid rgba(197,160,89,.35);
        background: #3C2A1E;
        box-shadow: 0 18px 40px rgba(0,0,0,.35);
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .coth-card .coth-art {
        position: relative;
        background:
            radial-gradient(240px 150px at 50% 38%, rgba(198,161,91,.18), rgba(36,22,15,0) 70%),
            #24160F;
    }
    /* Feather the artwork's dark-blue backdrop into the black card so there is no hard rectangle. */
    .coth-card .coth-art::after {
        content: ''; position: absolute; inset: 0; pointer-events: none; z-index: 1;
        box-shadow: inset 0 0 34px 20px #24160F;
    }
    .coth-card .coth-art img { position: relative; z-index: 0; }
    .coth-card .coth-pill { z-index: 2; }
    details.coth-card > summary { list-style: none; cursor: pointer; }
    details.coth-card > summary::-webkit-details-marker { display: none; }
    details.coth-card[open] { border-color: #E8C878; }
    details.coth-card > summary:hover { filter: brightness(1.04); }
    .coth-unit { border-top: 1px solid rgba(198,161,91,.22); }
    .coth-card.is-selected, a.coth-card:focus-visible {
        border-color: #E8C878; outline: none;
        box-shadow: 0 0 0 2px rgba(232,200,120,.85), 0 22px 54px rgba(0,0,0,.6);
    }
    .coth-pill {
        border: 1px solid rgba(197,160,89,.9); color: #E8C878;
        background: rgba(10,10,10,.6); letter-spacing: .18em;
    }
    .coth-dot { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; }
    .coth-taken img { filter: grayscale(.7) brightness(.72); }
    .coth-summary { background: linear-gradient(180deg, rgba(20,16,8,.98), rgba(10,10,10,.98)); border-top: 1px solid rgba(197,160,89,.5); }
</style>
@endpush
@section('content')
@php
    $visuals = app(\App\Services\Coth\CothVisualLibrary::class);
    $artFor = function ($table) use ($visuals) {
        return $visuals->assetFor($table);
    };
    $chipFor = function ($table) use ($visuals) {
        return $visuals->chipFor($table);
    };
    $statusMeta = function ($state) {
        switch ($state) {
            case 'Available': return ['label' => 'Available', 'color' => '#34d399', 'text' => 'text-emerald-300'];
            case 'Held': return ['label' => 'Temporarily held', 'color' => '#fbbf24', 'text' => 'text-amber-300'];
            case 'Booked': return ['label' => 'Reserved', 'color' => '#f87171', 'text' => 'text-red-300'];
            default: return ['label' => 'Closed', 'color' => '#9ca3af', 'text' => 'text-white/50'];
        }
    };
@endphp
@php
    $heroFile = public_path('images/coth/hero-lounge.jpg');
    $hero = asset('public/images/coth/hero-lounge.jpg').(is_file($heroFile) ? '?v='.filemtime($heroFile) : '');
    $flow = [
        1 => ['Date', 'Completed'],
        2 => ['Section', $section ? 'Completed' : 'Active'],
        3 => ['Table', $section ? 'Active' : ''],
        4 => ['Reserve', ''],
    ];
@endphp
<div class="coth-wrap text-white min-h-[70vh] pb-28">
    <div class="coth-exp-hero">
        <img src="{{ $hero }}" alt="Lounge seating at Chill on the Hill, looking over Bamenda">
    </div>
    <div class="max-w-6xl mx-auto px-4">
        <div class="coth-exp-head">
            <div>
                <a href="{{ route('coth.index') }}" class="coth-exp-kicker">&larr; ALL DATES</a>
                <h1 class="coth-exp-title">{{ \Carbon\Carbon::parse($date)->format('l, j F Y') }}</h1>
                @if (!empty($nightTitle))
                    <p class="text-brand-gold text-sm m-0 mb-1">{{ $nightTitle }}</p>
                @endif
                <p class="text-white/60 text-sm m-0">One table, this date only. Prices are per table, per day.</p>
            </div>
            <ol class="coth-flow" aria-label="Reservation progress">
                @foreach ($flow as $num => $meta)
                    @php
                        $state = $num < ($section ? 3 : 2) ? 'is-done' : ($num === ($section ? 3 : 2) ? 'is-on' : '');
                    @endphp
                    <li class="coth-flow-step {{ $state }}">
                        <span class="coth-flow-dot">
                            @if ($state === 'is-done')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @else
                                {{ $num }}
                            @endif
                        </span>
                        <small><b>{{ $num }}. {{ $meta[0] }}</b>{{ $meta[1] }}</small>
                    </li>
                    @if ($num < 4)
                        <li class="coth-flow-line" aria-hidden="true"></li>
                    @endif
                @endforeach
            </ol>
        </div>
        @if (session('not_permitted'))<div class="mt-4 rounded-xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm">{{ session('not_permitted') }}</div>@endif

        @if (! $section)
            <h2 class="coth-exp-h">Choose your experience</h2>
            <div class="coth-exp-grid">
                @foreach ($cards as $card)
                    <a href="{{ route('coth.tables', ['date' => $date, 'section' => $card['section']->code]) }}" class="coth-exp-card">
                        <span class="coth-exp-photo">
                            @if (! empty($card['image']))
                                <img src="{{ $card['image'] }}" alt="{{ $card['section']->name }}">
                            @endif
                        </span>
                        <span class="coth-exp-body">
                            <h3>{{ $card['section']->name }}</h3>
                            <span class="coth-exp-count">{{ $card['open'] }} available</span>
                            <span class="coth-exp-pin" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.2" fill="currentColor"/></svg>
                            </span>
                            <span class="coth-exp-go">View tables &nbsp;&rarr;</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($section)
            <a href="{{ route('coth.tables', ['date' => $date]) }}" class="coth-exp-kicker inline-block mt-8">&larr; ALL SECTIONS</a>
            <div class="flex items-center justify-between mt-3 gap-3">
                <h2 class="text-xl m-0">{{ $section->name }}</h2>
                <a class="text-sm text-brand-gold underline" href="{{ route('coth.tables', ['date' => $date, 'section' => $section->code, 'view' => $viewMode === 'list' ? 'layout' : 'list']) }}">
                    {{ $viewMode === 'list' ? 'Show 3D layout' : 'Show list' }}
                </a>
            </div>
            @if ($illustrative && $viewMode !== 'list')
                <p class="text-sm text-brand-gold/90 mt-2">Illustrative layout. Staff can place tables to match the room.</p>
            @endif

            @if ($viewMode === 'list')
                <div class="mt-4 overflow-x-auto rounded-2xl border border-white/10">
                    <table class="w-full text-sm">
                        <thead class="text-left text-white/50">
                            <tr><th class="py-3 px-3">Table</th><th>Seats</th><th>Status</th><th>Price</th><th>Discount</th><th>Final</th><th>Deposit</th><th></th></tr>
                        </thead>
                        <tbody>
                        @foreach ($tables as $row)
                            @php $m = $statusMeta($row['state']); @endphp
                            <tr class="border-t border-white/10">
                                <td class="py-3 px-3">{{ $row['table']->code }} @if($row['table']->isCouch())<span class="text-brand-gold">· Lounge</span>@endif</td>
                                <td>{{ $row['table']->capacity }}</td>
                                <td class="{{ $m['text'] }}"><span class="coth-dot align-middle mr-1" style="background: {{ $m['color'] }}"></span>{{ $m['label'] }}</td>
                                <td>{{ number_format($row['quote']['list_price']) }}</td>
                                <td>{{ $row['quote']['discounted'] ? '−'.number_format($row['quote']['discount_amount']) : '—' }}</td>
                                <td>{{ number_format($row['quote']['final_price']) }}</td>
                                <td>{{ number_format($row['quote']['deposit_due']) }}</td>
                                <td class="pr-3">@if($row['available'])<a class="text-brand-gold font-semibold" href="{{ route('coth.book', ['date' => $date, 'table' => $row['table']->code]) }}">Reserve</a>@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                @php
                    $groups = [];
                    foreach ($tables as $row) {
                        $code = (string) $row['table']->code;
                        $key = preg_match('/^(.*-T\d+)/i', $code, $match) ? strtoupper($match[1]) : strtoupper($section->code).'-T'.$row['table']->capacity;
                        $groups[$key][] = $row;
                    }
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mt-5" id="coth-grid">
                    @foreach ($groups as $groupKey => $rows)
                        @php
                            $sample = $rows[0]['table'];
                            $chip = $chipFor($sample);
                            $remaining = 0;
                            $taken = 0;
                            foreach ($rows as $row) {
                                if ($row['available']) {
                                    $remaining++;
                                } elseif (in_array($row['state'], ['Booked', 'Held'], true)) {
                                    $taken++;
                                }
                            }
                            $parts = explode('-', $groupKey, 2);
                            $groupLabel = ucfirst(strtolower($parts[0])).'-'.($parts[1] ?? '');
                        @endphp
                        <details class="coth-card rounded-3xl overflow-hidden">
                            <summary class="block">
                                <div class="coth-art relative px-4 pt-5 pb-3 flex items-center justify-center">
                                    <img src="{{ $artFor($sample) }}" alt="{{ $groupLabel }} {{ $sample->capacity }} separate seats" class="w-full max-w-[300px] h-44 object-contain" loading="lazy">
                                    <span class="coth-pill absolute bottom-3 left-1/2 -translate-x-1/2 text-[11px] font-semibold uppercase rounded-full px-4 py-1">
                                        {{ $chip === 'City View · High Table' ? 'City View · High Table' : $sample->capacity.' Seats' }}
                                    </span>
                                </div>
                                <div class="px-4 pt-4 pb-5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-serif text-2xl">{{ $groupLabel }}</span>
                                        <span class="text-xs text-white/55">{{ count($rows) }} {{ count($rows) === 1 ? 'table' : 'tables' }}</span>
                                    </div>
                                    <p class="text-xs text-white/60 mt-1 mb-3">
                                        {{ $section->name }} · {{ $sample->capacity }} seats
                                        @if ($chip)· <span class="text-brand-gold">{{ $chip }}</span>@endif
                                    </p>
                                    <div class="flex items-center justify-between text-sm font-semibold">
                                        <span class="text-red-300">{{ $taken }} taken</span>
                                        <span class="text-emerald-300">{{ $remaining }} remaining</span>
                                    </div>
                                    <span class="mt-4 block text-center border border-brand-gold/50 text-brand-gold uppercase tracking-[0.12em] text-xs rounded-full px-5 py-2.5">See each table</span>
                                </div>
                            </summary>
                            <div class="px-4 pb-4">
                                <p class="text-sm text-white/80 mb-2">{{ $remaining }} available · {{ $taken }} taken</p>
                                @foreach ($rows as $row)
                                    @php
                                        $table = $row['table'];
                                        $m = $statusMeta($row['state']);
                                    @endphp
                                    <div class="coth-unit py-3 flex items-center gap-3">
                                        <img src="{{ $artFor($table) }}" alt="" class="w-16 h-12 object-contain rounded-md bg-[#24160F]">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold">{{ $table->code }}</div>
                                            <div class="text-xs {{ $m['text'] }}"><span class="coth-dot align-middle mr-1" style="background: {{ $m['color'] }}"></span>{{ $m['label'] }}</div>
                                        </div>
                                        @if ($row['available'])
                                            <a class="shrink-0 bg-brand-gold text-[#24160F] font-bold uppercase tracking-[0.1em] text-[11px] rounded-full px-3 py-2" href="{{ route('coth.book', ['date' => $date, 'table' => $table->code]) }}">Reserve</a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>

@endsection
