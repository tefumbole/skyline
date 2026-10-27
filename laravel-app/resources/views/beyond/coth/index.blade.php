@extends('beyond.layout')
@section('title', 'Chill on the Hill · Skyline Hotel')
@section('body_class', 'bg-[#0A0A0A]')
@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-3xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0">Skyline Hotel · Bamenda</p>
        <h1 class="font-serif text-3xl md:text-4xl mt-2">Chill on the Hill</h1>
        <p class="text-white/70 text-sm mt-2">Reserve one table for one night, from 24 December 2026 to 2 January 2027. Prices are per table, per day.</p>
        <div class="mt-6 rounded-2xl border border-brand-gold/40 bg-white/[0.03] p-5">
            <p class="text-brand-gold font-semibold m-0" id="coth-phase">{{ $phase['label'] }}</p>
            @if ($phase['countdown'])
                <p class="text-2xl font-bold mt-2 mb-0 tabular-nums" id="coth-count" data-target="{{ $phase['countdown'] }}">—</p>
            @endif
        </div>
        <h2 class="text-lg mt-8 mb-3">Select a date</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach ($dates as $date)
                @php $carbon = \Carbon\Carbon::parse($date); @endphp
                <a href="{{ route('coth.tables', ['date' => $date]) }}" class="rounded-2xl border border-brand-gold/30 hover:border-brand-gold px-4 py-3">
                    <span class="block text-xs uppercase tracking-wider text-brand-gold">{{ $carbon->format('D') }}</span>
                    <span class="block text-lg font-semibold">{{ $carbon->format('j M Y') }}</span>
                    @if (in_array($date, ['2026-12-26','2026-12-27','2026-12-28','2026-12-29','2026-12-30'], true))
                        <span class="text-xs text-white/60">20% off table prices</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function () {
    var el = document.getElementById('coth-count');
    if (!el) return;
    var target = new Date(el.getAttribute('data-target')).getTime();
    function tick() {
        var diff = target - Date.now();
        if (diff <= 0) { el.textContent = 'Event in progress.'; return; }
        var days = Math.floor(diff / 86400000);
        var hours = Math.floor((diff % 86400000) / 3600000);
        var mins = Math.floor((diff % 3600000) / 60000);
        var secs = Math.floor((diff % 60000) / 1000);
        el.textContent = days + 'd ' + hours + 'h ' + mins + 'm ' + secs + 's';
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
@endpush
