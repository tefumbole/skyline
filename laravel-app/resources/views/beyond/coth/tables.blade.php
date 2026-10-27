@extends('beyond.layout')
@section('title', 'Choose a table · Chill on the Hill')
@section('body_class', 'bg-[#0A0A0A]')
@push('head')
<style>
    .coth-floor { position: relative; min-height: 280px; }
    .coth-grid { display: flex; flex-wrap: wrap; gap: 38px 26px; }
    .coth-node { width: 152px; text-align: center; }
    .coth-placed { position: absolute; transform: translate(-50%, -50%); }

    /* 3D table scene: the plane is tilted back so the tabletop reads as 3D. */
    .coth-scene { perspective: 640px; height: 130px; display: flex; align-items: center; justify-content: center; }
    .coth-plane {
        position: relative; width: 96px; height: 96px;
        transform: rotateX(56deg); transform-style: preserve-3d;
    }
    .coth-plane.couch { width: 122px; height: 74px; }
    .coth-top {
        position: absolute; inset: 0; border-radius: 50%;
        background: radial-gradient(circle at 38% 30%, #f7e6bb 0%, #d7b260 48%, #a67e2f 100%);
        border: 1px solid rgba(255, 255, 255, .28);
        box-shadow: 0 11px 0 #6d541d, 0 13px 20px rgba(0, 0, 0, .55);
    }
    .coth-plane.couch .coth-top {
        border-radius: 46px;
        background: radial-gradient(circle at 40% 32%, #dbe3ee 0%, #7d8aa0 55%, #44506a 100%);
        box-shadow: 0 11px 0 #2b3346, 0 13px 20px rgba(0, 0, 0, .55);
    }
    .coth-seat3d {
        position: absolute; left: 50%; top: 50%; width: 17px; height: 17px; margin: -9px 0 0 -9px;
        border-radius: 5px; background: linear-gradient(#efd189, #b8863a);
        box-shadow: 0 3px 4px rgba(0, 0, 0, .5);
    }
    .coth-plane.taken .coth-top {
        background: radial-gradient(circle at 38% 30%, #5a5a5a 0%, #3a3a3a 55%, #262626 100%);
        box-shadow: 0 11px 0 #161616, 0 13px 20px rgba(0, 0, 0, .55);
    }
    .coth-plane.taken .coth-seat3d { background: linear-gradient(#555, #333); }
    .coth-label {
        display: inline-block; margin-top: 6px; font-weight: 700; letter-spacing: .04em; font-size: 12px;
        color: #fff; background: linear-gradient(#2563eb, #1d4ed8);
        border: 1px solid #60a5fa; border-radius: 9999px; padding: 3px 11px;
        box-shadow: 0 3px 10px rgba(37, 99, 235, .45);
    }
</style>
@endpush
@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <a href="{{ route('coth.index') }}" class="text-xs uppercase tracking-[0.18em] text-brand-gold">All dates</a>
        <h1 class="font-serif text-3xl mt-2">{{ \Carbon\Carbon::parse($date)->format('l, j F Y') }}</h1>
        <p class="text-white/70 text-sm">This reservation covers one day only.</p>
        @if (session('not_permitted'))<div class="mt-4 rounded-xl border border-red-400/40 px-4 py-3 text-sm">{{ session('not_permitted') }}</div>@endif

        <div class="grid sm:grid-cols-2 gap-3 mt-6">
            @foreach ($cards as $card)
                <a href="{{ route('coth.tables', ['date' => $date, 'section' => $card['section']->code]) }}"
                   class="rounded-2xl border px-4 py-4 {{ $section && $section->id === $card['section']->id ? 'border-brand-gold bg-brand-gold/10' : 'border-brand-gold/30' }}">
                    <span class="block font-semibold">{{ $card['section']->name }}</span>
                    <span class="text-sm text-white/70">{{ $card['open'] }} available</span>
                </a>
            @endforeach
        </div>

        @if ($section)
            <div class="flex items-center justify-between mt-8 gap-3">
                <h2 class="text-xl m-0">{{ $section->name }}</h2>
                <a class="text-sm text-brand-gold underline" href="{{ route('coth.tables', ['date' => $date, 'section' => $section->code, 'view' => $viewMode === 'list' ? 'layout' : 'list']) }}">
                    {{ $viewMode === 'list' ? 'Show layout' : 'Show list' }}
                </a>
            </div>
            @if ($illustrative && $viewMode !== 'list')
                <p class="text-sm text-brand-gold mt-2">Illustrative layout. Staff can place tables to match the room.</p>
            @endif

            @if ($viewMode === 'list')
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-white/50">
                            <tr><th class="py-2">Table</th><th>Seats</th><th>Status</th><th>Price</th><th>Discount</th><th>Final</th><th>Deposit</th><th></th></tr>
                        </thead>
                        <tbody>
                        @foreach ($tables as $row)
                            <tr class="border-t border-white/10">
                                <td class="py-3">{{ $row['table']->code }} @if($row['table']->isCouch())<span class="text-brand-gold">Lounge</span>@endif</td>
                                <td>{{ $row['table']->capacity }}</td>
                                <td>{{ $row['state'] }}</td>
                                <td>{{ number_format($row['quote']['list_price']) }}</td>
                                <td>{{ number_format($row['quote']['discount_amount']) }}</td>
                                <td>{{ number_format($row['quote']['final_price']) }}</td>
                                <td>{{ number_format($row['quote']['deposit_due']) }}</td>
                                <td>@if($row['available'])<a class="text-brand-gold" href="{{ route('coth.book', ['date' => $date, 'table' => $row['table']->code]) }}">Reserve</a>@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="coth-floor mt-4 rounded-2xl border border-white/10 bg-gradient-to-b from-white/[0.04] to-transparent p-5 {{ $illustrative ? '' : 'relative h-[640px]' }}">
                    <div class="coth-grid">
                        @foreach ($tables as $row)
                            @php $table = $row['table']; @endphp
                            <div class="coth-node {{ $table->pos_x !== null && $table->pos_y !== null && ! $illustrative ? 'coth-placed' : '' }}"
                                 @if($table->pos_x !== null && $table->pos_y !== null && ! $illustrative) style="left: {{ $table->pos_x }}%; top: {{ $table->pos_y }}%;" @endif>
                                <div class="coth-scene">
                                    <div class="coth-plane {{ $table->isCouch() ? 'couch' : '' }} {{ $row['available'] ? '' : 'taken' }}">
                                        <div class="coth-top"></div>
                                        @if (! $table->isCouch())
                                            @for ($seat = 0; $seat < $table->capacity; $seat++)
                                                <span class="coth-seat3d" style="transform: rotate({{ $seat * (360 / max(1, $table->capacity)) }}deg) translateY(-56px);"></span>
                                            @endfor
                                        @endif
                                    </div>
                                </div>
                                <span class="coth-label">{{ $table->code }}</span>
                                <p class="text-xs text-white/60 mt-2 mb-0">{{ $section->name }} · {{ $table->capacity }} seats @if($table->isCouch())· Lounge @endif· {{ $row['state'] }}</p>
                                <p class="text-xs m-0">{{ number_format($row['quote']['list_price']) }} FCFA
                                    @if ($row['quote']['discounted']) · −{{ number_format($row['quote']['discount_amount']) }} @endif
                                </p>
                                <p class="text-sm text-brand-gold m-0">{{ number_format($row['quote']['final_price']) }} · deposit {{ number_format($row['quote']['deposit_due']) }}</p>
                                @if ($row['available'])
                                    <a href="{{ route('coth.book', ['date' => $date, 'table' => $table->code]) }}" class="inline-block mt-2 text-xs font-bold uppercase tracking-wider bg-brand-gold text-[#0A0A0A] rounded-full px-3 py-1.5">Reserve</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
