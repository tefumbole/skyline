@extends('beyond.layout')
@section('title', 'Chill on the Hill · Skyline Hotel')
@section('body_class', 'bg-[#140E0A] coth-fit')
@push('head')
<style>
    body.coth-fit { height: 100dvh; max-height: 100dvh; overflow: hidden; }
    body.coth-fit main { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
    .coth-land { background: #140E0A; color: #F6EFE4; flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
    .coth-land a { color: inherit; text-decoration: none; }
    .coth-hero { display: grid; grid-template-columns: 1.05fr .95fr; gap: 20px; align-items: stretch; padding: 12px 32px 0; max-width: 1180px; margin: 0 auto; width: 100%; flex: 0 0 auto; }
    .coth-hero h1 { font-family: Georgia, "Times New Roman", serif; font-size: clamp(36px, 4vw, 52px); line-height: 1; font-weight: 500; margin: 0 0 12px; }
    .coth-photo { position: relative; min-height: 0; height: 100%; border-radius: 18px; overflow: hidden; }
    .coth-photo img { width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; }
    .coth-photo::after {
        content: ""; position: absolute; inset: 0;
        background: linear-gradient(90deg, #140E0A 0%, rgba(20,14,10,.15) 22%, rgba(20,14,10,0) 46%);
    }
    .coth-panel { max-width: 1180px; margin: 8px auto 0; padding: 0 32px 8px; width: 100%; flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
    .coth-count {
        display: grid; grid-template-columns: minmax(0, 1.2fr) repeat(4, minmax(0, .8fr)); gap: 8px;
        border: 1px solid rgba(198,161,91,.28); border-radius: 16px; padding: 10px;
        background: rgba(42,28,20,.72);
    }
    .coth-count-lead { display: flex; gap: 8px; align-items: center; padding: 2px 4px; min-width: 0; }
    .coth-count-lead strong { display: block; font-size: 13px; line-height: 1.25; }
    .coth-count-lead span { color: rgba(246,239,228,.6); font-size: 11px; }
    .coth-unit {
        border: 1px solid rgba(198,161,91,.22); border-radius: 12px; text-align: center; padding: 6px 4px; background: #1C1410;
    }
    .coth-unit b { display: block; font-size: 22px; font-variant-numeric: tabular-nums; letter-spacing: .04em; }
    .coth-unit small { letter-spacing: .12em; font-size: 9px; color: rgba(246,239,228,.55); }
    .coth-choose { margin-top: 8px; border: 1px solid rgba(198,161,91,.28); border-radius: 18px; padding: 12px 14px; background: rgba(28,20,16,.55); flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
    .coth-choose-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 8px; }
    .coth-choose h2 { font-family: Georgia, "Times New Roman", serif; font-size: 26px; font-weight: 500; margin: 0; }
    .coth-steps { display: flex; align-items: center; gap: 8px; font-size: 12px; color: rgba(246,239,228,.55); }
    .coth-step-dot {
        width: 22px; height: 22px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid rgba(246,239,228,.25); font-size: 11px;
    }
    .coth-steps .is-on { color: #E8C878; }
    .coth-steps .is-on .coth-step-dot { background: #C6A15B; color: #24160F; border-color: #C6A15B; font-weight: 700; }
    .coth-step-line { width: 28px; height: 1px; background: rgba(246,239,228,.2); }
    .coth-dates { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
    .coth-date {
        text-align: left; background: #1C1410; color: #F6EFE4; border: 1px solid rgba(198,161,91,.22);
        border-radius: 14px; padding: 8px 12px; cursor: pointer; position: relative; min-height: 0;
    }
    .coth-date small { display: block; letter-spacing: .14em; font-size: 11px; color: rgba(246,239,228,.55); }
    .coth-date strong { display: block; margin-top: 2px; font-size: 15px; font-weight: 600; }
    .coth-night { display: block; margin-top: 4px; font-size: 12px; line-height: 1.25; color: #E8C878; font-weight: 600; }
    .coth-date.is-on { border-color: #E8C878; box-shadow: inset 0 0 0 1px #E8C878; }
    .coth-date .tick {
        position: absolute; top: 10px; right: 10px; width: 18px; height: 18px; border-radius: 999px;
        background: #C6A15B; color: #24160F; font-size: 11px; display: none; align-items: center; justify-content: center;
    }
    .coth-date.is-on .tick { display: inline-flex; }
    .coth-off {
        display: inline-block; margin-top: 4px; font-size: 10px; letter-spacing: .08em; color: #E8C878;
        border: 1px solid rgba(232,200,120,.45); border-radius: 999px; padding: 1px 7px;
    }
    .coth-selected {
        margin-top: 8px; border: 1px solid rgba(198,161,91,.28); border-radius: 16px;
        display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 8px 12px;
        background: #1C1410;
    }
    .coth-selected p { margin: 0; color: rgba(246,239,228,.65); font-size: 13px; }
    .coth-selected strong { display: block; color: #F6EFE4; font-size: 18px; font-family: Georgia, "Times New Roman", serif; font-weight: 500; }
    .coth-land a.coth-go {
        background: #F0D078; color: #1A1008; font-weight: 800; font-size: 16px; border-radius: 12px;
        padding: 12px 22px; letter-spacing: .01em; white-space: nowrap;
        box-shadow: 0 0 0 2px #F0D078, 0 10px 28px rgba(240, 208, 120, .45);
    }
    .coth-land a.coth-go:hover { background: #FFE7A3; color: #1A1008; }
    @media (max-width: 1100px) {
        body.coth-fit { height: auto; max-height: none; overflow: auto; }
        .coth-land { min-height: auto; display: block; }
        .coth-hero, .coth-dates { grid-template-columns: 1fr 1fr; }
        .coth-hero { padding: 16px 16px 0; }
        .coth-photo img { height: 220px; }
        .coth-panel { display: block; padding: 0 16px 28px; }
        .coth-count { grid-template-columns: 1fr 1fr; }
        .coth-choose { display: block; }
        .coth-selected { flex-direction: column; align-items: stretch; }
        .coth-go { text-align: center; }
    }
</style>
@endpush
@section('content')
@php
    $first = $dates[0] ?? null;
    $heroFile = public_path('images/coth/hero-lounge.jpg');
    $hero = asset('public/images/coth/hero-lounge.jpg').(is_file($heroFile) ? '?v='.filemtime($heroFile) : '');
@endphp
<div class="coth-land">
    <section class="coth-hero">
        <div>
            <h1>Chill on the Hill</h1>
            <div class="coth-count" id="coth-count" @if(!empty($phase['countdown'])) data-target="{{ $phase['countdown'] }}" @endif>
                <div class="coth-count-lead">
                    <span aria-hidden="true">🕒</span>
                    <div>
                        <strong>{{ $phase['label'] }}</strong>
                        <span>Cameroon time</span>
                    </div>
                </div>
                <div class="coth-unit"><b data-unit="days">00</b><small>DAYS</small></div>
                <div class="coth-unit"><b data-unit="hours">00</b><small>HOURS</small></div>
                <div class="coth-unit"><b data-unit="mins">00</b><small>MINUTES</small></div>
                <div class="coth-unit"><b data-unit="secs">00</b><small>SECONDS</small></div>
            </div>
        </div>
        <div class="coth-photo">
            <img src="{{ $hero }}" alt="Lounge seating at Chill on the Hill, looking over Bamenda">
        </div>
    </section>

    <div class="coth-panel">
        <section class="coth-choose">
            <div class="coth-choose-head">
                <h2>Choose your date</h2>
                <div class="coth-steps" aria-label="Reservation progress">
                    <span class="is-on"><span class="coth-step-dot">1</span> Date</span>
                    <span class="coth-step-line"></span>
                    <span><span class="coth-step-dot">2</span> Table</span>
                    <span class="coth-step-line"></span>
                    <span><span class="coth-step-dot">3</span> Reserve</span>
                </div>
            </div>
            <div class="coth-dates" id="coth-dates">
                @foreach ($dates as $date)
                    @php
                        $carbon = \Carbon\Carbon::parse($date);
                        $night = $nightTitles[$date] ?? '';
                        $fullLabel = $carbon->format('l, j F Y').($night !== '' ? ' · '.$night : '');
                    @endphp
                    <button type="button" class="coth-date {{ $date === $first ? 'is-on' : '' }}" data-date="{{ $date }}" data-label="{{ $fullLabel }}">
                        <span class="tick">✓</span>
                        <small>{{ strtoupper($carbon->format('D')) }}</small>
                        <strong>{{ $carbon->format('j M Y') }}</strong>
                        @if ($night !== '')
                            <span class="coth-night">{{ $night }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
            <div class="coth-selected">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span aria-hidden="true">📅</span>
                    <p>Selected: <strong id="coth-selected-label">{{ $first ? \Carbon\Carbon::parse($first)->format('l, j F Y').((($nightTitles[$first] ?? '') !== '') ? ' · '.$nightTitles[$first] : '') : '—' }}</strong></p>
                </div>
                <a id="coth-explore" class="coth-go" href="{{ $first ? route('coth.tables', ['date' => $first]) : route('coth.index') }}">Explore available tables &nbsp;&gt;</a>
            </div>
        </section>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function () {
    var box = document.getElementById('coth-count');
    if (box && box.getAttribute('data-target')) {
        var target = new Date(box.getAttribute('data-target')).getTime();
        var units = {
            days: box.querySelector('[data-unit="days"]'),
            hours: box.querySelector('[data-unit="hours"]'),
            mins: box.querySelector('[data-unit="mins"]'),
            secs: box.querySelector('[data-unit="secs"]')
        };
        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function tick() {
            var diff = target - Date.now();
            if (diff < 0) diff = 0;
            var days = Math.floor(diff / 86400000);
            var hours = Math.floor((diff % 86400000) / 3600000);
            var mins = Math.floor((diff % 3600000) / 60000);
            var secs = Math.floor((diff % 60000) / 1000);
            units.days.textContent = pad(days);
            units.hours.textContent = pad(hours);
            units.mins.textContent = pad(mins);
            units.secs.textContent = pad(secs);
        }
        tick();
        setInterval(tick, 1000);
    }
    var dates = document.getElementById('coth-dates');
    var label = document.getElementById('coth-selected-label');
    var go = document.getElementById('coth-explore');
    var base = @json(route('coth.tables'));
    if (!dates) return;
    dates.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-date]');
        if (!btn) return;
        dates.querySelectorAll('.coth-date').forEach(function (el) { el.classList.remove('is-on'); });
        btn.classList.add('is-on');
        label.textContent = btn.getAttribute('data-label');
        go.setAttribute('href', base + '?date=' + encodeURIComponent(btn.getAttribute('data-date')));
    });
})();
</script>
@endpush
