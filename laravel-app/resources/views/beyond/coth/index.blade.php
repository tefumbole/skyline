@extends('beyond.layout')
@section('title', 'Chill on the Hill · Skyline Hotel')
@section('coth_landing', '1')
@section('body_class', 'bg-[#140E0A]')
@push('head')
<style>
    .coth-land { background: #140E0A; color: #F6EFE4; min-height: 100vh; }
    .coth-land a { color: inherit; text-decoration: none; }
    .coth-nav {
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
        padding: 18px 28px; border-bottom: 1px solid rgba(198,161,91,.18);
    }
    .coth-brand { display: flex; align-items: center; gap: 10px; letter-spacing: .22em; }
    .coth-brand strong { display: block; font-size: 13px; font-weight: 600; }
    .coth-brand span { display: block; font-size: 9px; letter-spacing: .32em; color: #C6A15B; }
    .coth-menus { display: flex; gap: 22px; font-size: 12px; letter-spacing: .16em; color: rgba(246,239,228,.78); }
    .coth-menus a.on { color: #E8C878; box-shadow: inset 0 -1px 0 #E8C878; padding-bottom: 4px; }
    .coth-bookstay {
        border: 1px solid rgba(232,200,120,.7); color: #E8C878; border-radius: 999px;
        padding: 8px 16px; font-size: 11px; letter-spacing: .16em; font-weight: 700;
    }
    .coth-hero { display: grid; grid-template-columns: 1.05fr .95fr; gap: 28px; align-items: center; padding: 36px 40px 10px; max-width: 1180px; margin: 0 auto; }
    .coth-kicker { letter-spacing: .28em; font-size: 11px; color: rgba(246,239,228,.62); margin: 0; }
    .coth-hero h1 { font-family: Georgia, "Times New Roman", serif; font-size: 64px; line-height: 1; font-weight: 500; margin: 10px 0 12px; }
    .coth-hero p.lead { margin: 0; max-width: 460px; color: rgba(246,239,228,.78); font-size: 15px; }
    .coth-meta { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 18px; color: #E8C878; font-size: 14px; }
    .coth-photo { position: relative; min-height: 280px; border-radius: 18px; overflow: hidden; }
    .coth-photo img { width: 100%; height: 340px; object-fit: cover; display: block; }
    .coth-photo::after {
        content: ""; position: absolute; inset: 0;
        background: linear-gradient(90deg, #140E0A 0%, rgba(20,14,10,.15) 28%, rgba(20,14,10,0) 55%);
    }
    .coth-panel { max-width: 1180px; margin: 8px auto 0; padding: 0 40px 48px; }
    .coth-count {
        display: grid; grid-template-columns: 1.3fr repeat(4, 1fr); gap: 10px;
        border: 1px solid rgba(198,161,91,.28); border-radius: 16px; padding: 14px;
        background: rgba(42,28,20,.72);
    }
    .coth-count-lead { display: flex; gap: 12px; align-items: center; padding: 6px 8px; }
    .coth-count-lead strong { display: block; font-size: 15px; }
    .coth-count-lead span { color: rgba(246,239,228,.6); font-size: 12px; }
    .coth-unit {
        border: 1px solid rgba(198,161,91,.22); border-radius: 12px; text-align: center; padding: 10px 6px; background: #1C1410;
    }
    .coth-unit b { display: block; font-size: 28px; font-variant-numeric: tabular-nums; letter-spacing: .04em; }
    .coth-unit small { letter-spacing: .16em; font-size: 10px; color: rgba(246,239,228,.55); }
    .coth-choose { margin-top: 22px; border: 1px solid rgba(198,161,91,.28); border-radius: 18px; padding: 22px; background: rgba(28,20,16,.55); }
    .coth-choose-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
    .coth-choose h2 { font-family: Georgia, "Times New Roman", serif; font-size: 32px; font-weight: 500; margin: 0; }
    .coth-steps { display: flex; align-items: center; gap: 8px; font-size: 12px; color: rgba(246,239,228,.55); }
    .coth-step-dot {
        width: 22px; height: 22px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid rgba(246,239,228,.25); font-size: 11px;
    }
    .coth-steps .is-on { color: #E8C878; }
    .coth-steps .is-on .coth-step-dot { background: #C6A15B; color: #24160F; border-color: #C6A15B; font-weight: 700; }
    .coth-step-line { width: 28px; height: 1px; background: rgba(246,239,228,.2); }
    .coth-dates { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
    .coth-date {
        text-align: left; background: #1C1410; color: #F6EFE4; border: 1px solid rgba(198,161,91,.22);
        border-radius: 14px; padding: 14px 14px 12px; cursor: pointer; position: relative; min-height: 92px;
    }
    .coth-date small { display: block; letter-spacing: .14em; font-size: 11px; color: rgba(246,239,228,.55); }
    .coth-date strong { display: block; margin-top: 6px; font-size: 16px; font-weight: 600; }
    .coth-date.is-on { border-color: #E8C878; box-shadow: inset 0 0 0 1px #E8C878; }
    .coth-date .tick {
        position: absolute; top: 10px; right: 10px; width: 18px; height: 18px; border-radius: 999px;
        background: #C6A15B; color: #24160F; font-size: 11px; display: none; align-items: center; justify-content: center;
    }
    .coth-date.is-on .tick { display: inline-flex; }
    .coth-off {
        display: inline-block; margin-top: 8px; font-size: 10px; letter-spacing: .08em; color: #E8C878;
        border: 1px solid rgba(232,200,120,.45); border-radius: 999px; padding: 2px 8px;
    }
    .coth-selected {
        margin-top: 14px; border: 1px solid rgba(198,161,91,.28); border-radius: 16px;
        display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 16px;
        background: #1C1410;
    }
    .coth-selected p { margin: 0; color: rgba(246,239,228,.65); font-size: 13px; }
    .coth-selected strong { display: block; color: #F6EFE4; font-size: 22px; font-family: Georgia, "Times New Roman", serif; font-weight: 500; }
    .coth-go {
        background: #E4C98A; color: #2A1C12; font-weight: 700; border-radius: 12px; padding: 14px 22px;
        letter-spacing: .02em; white-space: nowrap;
    }
    @media (max-width: 900px) {
        .coth-menus { display: none; }
        .coth-hero, .coth-count, .coth-dates { grid-template-columns: 1fr 1fr; }
        .coth-hero { padding: 24px 16px 8px; }
        .coth-hero h1 { font-size: 42px; }
        .coth-panel { padding: 0 16px 32px; }
        .coth-count { grid-template-columns: 1fr 1fr; }
        .coth-choose h2 { font-size: 26px; }
        .coth-selected { flex-direction: column; align-items: stretch; }
        .coth-go { text-align: center; }
    }
</style>
@endpush
@section('content')
@php
    $first = $dates[0] ?? null;
    $hero = asset('public/images/coth/hero-lounge.jpg');
@endphp
<div class="coth-land">
    <header class="coth-nav">
        <a class="coth-brand" href="{{ url('/') }}">
            <svg width="28" height="18" viewBox="0 0 28 18" fill="none" aria-hidden="true"><path d="M1 16 L8 6 L12 11 L16 3 L20 9 L27 16" stroke="#C6A15B" stroke-width="1.4"/></svg>
            <div>
                <strong>SKYLINE HOTEL</strong>
                <span>BAMENDA</span>
            </div>
        </a>
        <nav class="coth-menus" aria-label="Site">
            <a href="{{ url('/') }}">HOME</a>
            <a href="{{ url('/') }}">STAY</a>
            <a href="{{ url('/menu') }}">DINE</a>
            <a href="{{ url('/events') }}">EVENTS</a>
            <a class="on" href="{{ route('coth.index') }}">COTH</a>
            <a href="{{ url('/about') }}">ABOUT</a>
        </nav>
        <a class="coth-bookstay" href="{{ url('/') }}">BOOK A STAY</a>
    </header>

    <section class="coth-hero">
        <div>
            <p class="coth-kicker">SKYLINE HOTEL · BAMENDA</p>
            <h1>Chill on the Hill</h1>
            <p class="lead">Reserve one table for one night, from 24 December 2026 to 2 January 2027. Prices are per table, per day.</p>
            <div class="coth-meta">
                <span>📅 24 December 2026 – 2 January 2027</span>
                <span>📍 Skyline Hotel · Bamenda</span>
            </div>
        </div>
        <div class="coth-photo">
            <img src="{{ $hero }}" alt="Lounge seating at Chill on the Hill">
        </div>
    </section>

    <div class="coth-panel">
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
                    @php $carbon = \Carbon\Carbon::parse($date); @endphp
                    <button type="button" class="coth-date {{ $date === $first ? 'is-on' : '' }}" data-date="{{ $date }}" data-label="{{ $carbon->format('l, j F Y') }}">
                        <span class="tick">✓</span>
                        <small>{{ strtoupper($carbon->format('D')) }}</small>
                        <strong>{{ $carbon->format('j M Y') }}</strong>
                        @if (in_array($date, $discountDates ?? [], true))
                            <span class="coth-off">{{ (int) ($discountPercent ?? 20) }}% OFF</span>
                        @endif
                    </button>
                @endforeach
            </div>
            <div class="coth-selected">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span aria-hidden="true">📅</span>
                    <p>Selected: <strong id="coth-selected-label">{{ $first ? \Carbon\Carbon::parse($first)->format('l, j F Y') : '—' }}</strong></p>
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
