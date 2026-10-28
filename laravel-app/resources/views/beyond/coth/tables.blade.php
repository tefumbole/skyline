@extends('beyond.layout')
@section('title', 'Choose a table · Chill on the Hill')
@section('body_class', 'bg-[#24160F]')
@push('head')
<style>
    .coth-wrap { background: #24160F; color: #F6EFE4; }
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
<div class="coth-wrap text-white min-h-[70vh] py-10 px-4 pb-28">
    <div class="max-w-6xl mx-auto">
        <a href="{{ route('coth.index') }}" class="text-xs uppercase tracking-[0.18em] text-brand-gold">&larr; All dates</a>
        <h1 class="font-serif text-3xl mt-2">{{ \Carbon\Carbon::parse($date)->format('l, j F Y') }}</h1>
        <p class="text-white/60 text-sm">One table, this date only. Prices are per table, per day.</p>
        @if (session('not_permitted'))<div class="mt-4 rounded-xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm">{{ session('not_permitted') }}</div>@endif

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6">
            @foreach ($cards as $card)
                <a href="{{ route('coth.tables', ['date' => $date, 'section' => $card['section']->code]) }}"
                   class="coth-sec rounded-2xl px-4 py-4 block {{ $section && $section->id === $card['section']->id ? 'is-active' : '' }}">
                    <span class="block font-semibold">{{ $card['section']->name }}</span>
                    <span class="text-sm text-white/65">{{ $card['open'] }} available</span>
                </a>
            @endforeach
        </div>

        @if ($section)
            <div class="flex items-center justify-between mt-10 gap-3">
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
