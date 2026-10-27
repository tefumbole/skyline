@extends('beyond.layout')
@section('title', 'Choose a table · Chill on the Hill')
@section('body_class', 'bg-[#0A0A0A]')
@push('head')
<style>
    .coth-floor { position: relative; min-height: 280px; }
    .coth-node { width: 148px; }
    .coth-seat {
        position: absolute; width: 14px; height: 14px; border-radius: 4px;
        background: #C5A059; left: 50%; top: 50%; margin: -7px 0 0 -7px;
    }
    .coth-placed { position: absolute; width: 150px; transform: translate(-50%, -50%); }
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
                <div class="coth-floor mt-4 rounded-2xl border border-white/10 p-4 {{ $illustrative ? '' : 'relative h-[640px]' }}">
                    <div class="flex flex-wrap gap-6 {{ $illustrative ? '' : '' }}">
                        @foreach ($tables as $row)
                            @php $table = $row['table']; @endphp
                            <div class="{{ $table->pos_x !== null && $table->pos_y !== null && ! $illustrative ? 'coth-placed' : '' }}"
                                 @if($table->pos_x !== null && $table->pos_y !== null && ! $illustrative) style="left: {{ $table->pos_x }}%; top: {{ $table->pos_y }}%;" @endif>
                                <div class="coth-node text-center">
                                    <div class="relative mx-auto {{ $table->isCouch() ? 'w-28 h-16 rounded-3xl' : 'w-20 h-20 rounded-full' }} border border-brand-gold/70 flex items-center justify-center">
                                        @if (! $table->isCouch())
                                            @for ($seat = 0; $seat < $table->capacity; $seat++)
                                                <span class="coth-seat" style="transform: rotate({{ $seat * (360 / max(1, $table->capacity)) }}deg) translateY(-38px);"></span>
                                            @endfor
                                        @else
                                            <span class="text-[10px] uppercase tracking-wider text-brand-gold">Lounge</span>
                                        @endif
                                    </div>
                                    <p class="font-semibold mt-2 mb-0">{{ $table->code }}</p>
                                    <p class="text-xs text-white/60 m-0">{{ $section->name }} · {{ $table->capacity }} seats · {{ $row['state'] }}</p>
                                    <p class="text-xs m-0">{{ number_format($row['quote']['list_price']) }} FCFA
                                        @if ($row['quote']['discounted']) · −{{ number_format($row['quote']['discount_amount']) }} @endif
                                    </p>
                                    <p class="text-sm text-brand-gold m-0">{{ number_format($row['quote']['final_price']) }} · deposit {{ number_format($row['quote']['deposit_due']) }}</p>
                                    @if ($row['available'])
                                        <a href="{{ route('coth.book', ['date' => $date, 'table' => $table->code]) }}" class="inline-block mt-2 text-xs font-bold uppercase tracking-wider bg-brand-gold text-[#0A0A0A] rounded-full px-3 py-1.5">Reserve</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
