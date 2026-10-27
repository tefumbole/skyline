@extends('beyond.layout')
@section('title', 'Choose a table · Chill on the Hill')
@section('body_class', 'coth-body')
@push('head')
<style>
    .coth-body { background: #06091c; }
    .coth-wrap {
        background:
            radial-gradient(900px 480px at 15% -10%, #1b2a5e 0%, rgba(27,42,94,0) 60%),
            radial-gradient(900px 520px at 100% 0%, #142148 0%, rgba(20,33,72,0) 55%),
            linear-gradient(180deg, #0b1332 0%, #080c22 100%);
    }
    .coth-sec {
        border: 1px solid rgba(197,160,89,.30);
        background: linear-gradient(180deg, rgba(27,42,94,.55), rgba(10,16,38,.55));
    }
    .coth-sec.is-active { border-color: #C5A059; background: linear-gradient(180deg, rgba(197,160,89,.18), rgba(10,16,38,.4)); }
    .coth-card {
        border: 1px solid rgba(197,160,89,.38);
        background: linear-gradient(180deg, rgba(23,36,80,.65) 0%, rgba(10,15,36,.75) 100%);
        box-shadow: 0 18px 44px rgba(0,0,0,.45);
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .coth-card .coth-art {
        background:
            radial-gradient(240px 150px at 50% 32%, rgba(40,60,120,.6), rgba(10,15,36,0) 70%),
            linear-gradient(180deg, #101c42 0%, #0a1130 100%);
    }
    a.coth-card:hover { border-color: rgba(197,160,89,.85); box-shadow: 0 22px 54px rgba(0,0,0,.6); transform: translateY(-2px); }
    .coth-card.is-selected, a.coth-card:focus-visible {
        border-color: #E8C878; outline: none;
        box-shadow: 0 0 0 2px rgba(232,200,120,.85), 0 22px 54px rgba(0,0,0,.6);
    }
    .coth-pill {
        border: 1px solid rgba(197,160,89,.9); color: #E8C878;
        background: rgba(10,15,36,.6); letter-spacing: .18em;
    }
    .coth-dot { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; }
    .coth-taken img { filter: grayscale(.7) brightness(.72); }
    .coth-summary { background: linear-gradient(180deg, rgba(23,36,80,.98), rgba(10,15,36,.98)); border-top: 1px solid rgba(197,160,89,.5); }
</style>
@endpush
@section('content')
@php
    $artFor = function ($table) {
        if ($table->isCouch()) {
            $name = 'couch';
        } else {
            $cap = (int) $table->capacity;
            $name = in_array($cap, [2, 3, 4, 6, 8, 10], true) ? $cap : ($cap <= 2 ? 2 : ($cap >= 10 ? 10 : ($cap % 2 === 0 ? $cap : $cap + 1)));
            if (! in_array((int) $name, [2, 3, 4, 6, 8, 10], true)) { $name = 4; }
        }
        return asset('public/images/coth/tables/'.$name.'.png');
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mt-5" id="coth-grid">
                    @foreach ($tables as $row)
                        @php
                            $table = $row['table'];
                            $m = $statusMeta($row['state']);
                            $bookUrl = route('coth.book', ['date' => $date, 'table' => $table->code]);
                        @endphp
                        <{{ $row['available'] ? 'a' : 'div' }}
                            class="coth-card {{ $row['available'] ? '' : 'coth-taken' }} rounded-3xl overflow-hidden flex flex-col"
                            @if ($row['available'])
                                href="{{ $bookUrl }}"
                                role="button"
                                data-coth-select
                                data-code="{{ $table->code }}"
                                data-section="{{ $section->name }}"
                                data-capacity="{{ $table->capacity }}"
                                data-couch="{{ $table->isCouch() ? '1' : '0' }}"
                                data-price="{{ number_format($row['quote']['list_price']) }}"
                                data-discount="{{ $row['quote']['discounted'] ? number_format($row['quote']['discount_amount']) : '' }}"
                                data-final="{{ number_format($row['quote']['final_price']) }}"
                                data-deposit="{{ number_format($row['quote']['deposit_due']) }}"
                                data-balance="{{ number_format(max(0, $row['quote']['final_price'] - $row['quote']['deposit_due'])) }}"
                                data-href="{{ $bookUrl }}"
                            @else
                                aria-disabled="true"
                            @endif
                        >
                            <div class="coth-art relative px-4 pt-5 pb-3 flex items-center justify-center">
                                <img src="{{ $artFor($table) }}" alt="{{ $table->capacity }} seat table" class="w-full max-w-[280px] h-40 object-contain" loading="lazy">
                                <span class="coth-pill absolute bottom-3 left-1/2 -translate-x-1/2 text-[11px] font-semibold uppercase rounded-full px-4 py-1">
                                    {{ $table->isCouch() ? 'Couch' : $table->capacity.' Seats' }}
                                </span>
                            </div>
                            <div class="p-4 flex-1 flex flex-col">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-serif text-lg">{{ $table->code }}</span>
                                    <span class="text-[12px] {{ $m['text'] }} inline-flex items-center gap-1 font-semibold">
                                        <span class="coth-dot" style="background: {{ $m['color'] }}"></span>{{ $m['label'] }}
                                    </span>
                                </div>
                                <p class="text-xs text-white/60 mt-1 mb-3">{{ $section->name }} · {{ $table->capacity }} seats @if($table->isCouch())· Lounge couch @endif</p>

                                <div class="mt-auto space-y-0.5 text-sm">
                                    @if ($row['quote']['discounted'])
                                        <div class="flex justify-between text-white/55"><span>Price</span><span class="line-through">{{ number_format($row['quote']['list_price']) }}</span></div>
                                        <div class="flex justify-between text-emerald-300"><span>Discount (20%)</span><span>−{{ number_format($row['quote']['discount_amount']) }}</span></div>
                                        <div class="flex justify-between font-semibold"><span>Final price</span><span class="text-brand-gold">{{ number_format($row['quote']['final_price']) }} FCFA</span></div>
                                    @else
                                        <div class="flex justify-between font-semibold"><span>Price</span><span class="text-brand-gold">{{ number_format($row['quote']['final_price']) }} FCFA</span></div>
                                    @endif
                                    <div class="flex justify-between text-white/70"><span>Min. deposit</span><span>{{ number_format($row['quote']['deposit_due']) }} FCFA</span></div>
                                </div>

                                @if ($row['available'])
                                    <span class="mt-4 inline-block text-center bg-brand-gold text-[#0A0A0A] font-bold uppercase tracking-[0.12em] text-xs rounded-full px-5 py-2.5">Select table</span>
                                @else
                                    <span class="mt-4 inline-block text-center border border-white/15 text-white/50 uppercase tracking-[0.12em] text-xs rounded-full px-5 py-2.5">{{ $m['label'] }}</span>
                                @endif
                            </div>
                        </{{ $row['available'] ? 'a' : 'div' }}>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>

@if ($section && $viewMode !== 'list')
    <div id="coth-summary" class="coth-summary fixed bottom-0 inset-x-0 z-40 translate-y-full transition-transform duration-200">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[11px] uppercase tracking-[0.2em] text-brand-gold m-0">Your selection</p>
                <p class="m-0 font-semibold truncate" id="sum-title">—</p>
                <p class="m-0 text-xs text-white/70" id="sum-line">—</p>
            </div>
            <a id="sum-continue" href="#" class="shrink-0 bg-brand-gold text-[#0A0A0A] font-bold uppercase tracking-[0.12em] text-xs rounded-full px-7 py-3 text-center">Continue to verification</a>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var grid = document.getElementById('coth-grid');
    var bar = document.getElementById('coth-summary');
    if (!grid || !bar) return;
    var title = document.getElementById('sum-title');
    var line = document.getElementById('sum-line');
    var cont = document.getElementById('sum-continue');
    var dateLabel = @json(\Carbon\Carbon::parse($date)->format('l, j F Y'));

    grid.addEventListener('click', function (e) {
        var card = e.target.closest('[data-coth-select]');
        if (!card) return;
        e.preventDefault();
        grid.querySelectorAll('.is-selected').forEach(function (el) { el.classList.remove('is-selected'); });
        card.classList.add('is-selected');

        var d = card.dataset;
        title.textContent = d.code + ' · ' + d.section + ' · ' + (d.couch === '1' ? 'Lounge couch' : d.capacity + ' seats');
        var parts = [dateLabel];
        if (d.discount) {
            parts.push('Price ' + d.price + ' − ' + d.discount + ' = ' + d.final + ' FCFA');
        } else {
            parts.push('Price ' + d.final + ' FCFA');
        }
        parts.push('Deposit ' + d.deposit + ' FCFA · Balance ' + d.balance + ' FCFA');
        line.textContent = parts.join('  ·  ');
        cont.setAttribute('href', d.href);
        bar.classList.remove('translate-y-full');
        bar.scrollIntoView ? null : null;
    });

    // Keyboard: Enter/Space on a focused card selects it.
    grid.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var card = e.target.closest('[data-coth-select]');
        if (!card) return;
        e.preventDefault();
        card.click();
    });
})();
</script>
@endpush
