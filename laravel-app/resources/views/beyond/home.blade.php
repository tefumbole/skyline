<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Chill on the Hill | Skyline Hotel Bamenda</title>
    <meta name="description" content="Skyline Hotel Bamenda presents Chill on the Hill, 24 December 2026 to 2 January 2027.">
    <link rel="icon" href="{{ url('public/branding/skyline-logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #07111c; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            color: #f6f1e6;
            overflow-x: hidden;
        }
        .bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: #0c1830;
            padding: 10px max(22px, env(safe-area-inset-right)) 10px max(22px, env(safe-area-inset-left));
        }
        .brand, .actions, .contact {
            display: flex;
            align-items: center;
        }
        .brand { gap: 14px; min-width: 0; flex: 1 1 auto; }
        .actions { gap: 16px; flex: 0 0 auto; }
        .logo-mark {
            position: relative;
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
        }
        .logo-mark::before {
            content: "";
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, transparent 0 62%, #e1b368 84%, #fff6df 92%, transparent 100%);
            animation: logo-spin 2.8s linear infinite;
            -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 2.5px), #000 calc(100% - 2px));
            mask: radial-gradient(farthest-side, transparent calc(100% - 2.5px), #000 calc(100% - 2px));
        }
        .logo {
            width: 46px;
            height: 46px;
            object-fit: contain;
            display: block;
            position: relative;
            z-index: 1;
            animation: logo-glow 2.6s ease-in-out infinite;
        }
        @keyframes logo-spin {
            to { transform: rotate(360deg); }
        }
        @keyframes logo-glow {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(225, 179, 104, 0)); }
            50% { transform: scale(1.08); filter: drop-shadow(0 0 8px rgba(225, 179, 104, 0.9)); }
        }
        @media (prefers-reduced-motion: reduce) {
            .logo, .logo-mark::before { animation: none; }
        }
        .rule {
            width: 1px;
            height: 28px;
            background: rgba(230, 211, 161, 0.55);
            flex-shrink: 0;
        }
        .event {
            margin: 0;
            font-family: Cinzel, Georgia, serif;
            font-weight: 600;
            font-size: clamp(14px, 1.7vw, 20px);
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #e6d3a1;
            white-space: nowrap;
        }
        .hotel {
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #f4efe4;
            white-space: nowrap;
        }
        .login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 8px 14px;
            border: 1px solid rgba(255, 255, 255, 0.82);
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
        }
        .login:hover { background: rgba(255, 255, 255, 0.08); }
        .hero { line-height: 0; background: #07111c; }
        .frame { position: relative; }
        .poster {
            width: 100%;
            height: auto;
            display: block;
        }
        .when {
            position: absolute;
            left: 6.5%;
            top: 43%;
            max-width: 88%;
            margin: 0;
            color: #fff;
            font-size: clamp(12px, 1.6vw, 26px);
            font-weight: 600;
            letter-spacing: 0.02em;
            line-height: 1.25;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.65);
        }
        .count {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px 22px;
            flex-wrap: wrap;
            line-height: 1.2;
            background: #0c1830;
            border-top: 1px solid rgba(225, 179, 104, 0.35);
            padding: 12px max(16px, env(safe-area-inset-right)) 14px max(16px, env(safe-area-inset-left));
        }
        .count-label {
            margin: 0;
            font-family: Cinzel, Georgia, serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #e1b368;
        }
        .count-grid {
            display: flex;
            gap: 8px;
        }
        .count-cell {
            min-width: 62px;
            text-align: center;
            border: 1px solid rgba(225, 179, 104, 0.5);
            border-radius: 8px;
            padding: 6px 8px 5px;
            background: rgba(7, 17, 28, 0.45);
        }
        .count-cell strong {
            display: block;
            font-variant-numeric: tabular-nums;
            font-size: clamp(18px, 2.4vw, 26px);
            font-weight: 600;
            color: #fff6df;
        }
        .count-cell span {
            display: block;
            margin-top: 2px;
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #e1b368;
        }
        .count.is-done .count-grid { display: none; }
        .count-done {
            display: none;
            margin: 0;
            color: #fff6df;
            font-weight: 600;
        }
        .count.is-done .count-done { display: block; }
        .foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-top: auto;
            background: #0c1830;
            padding: 14px max(22px, env(safe-area-inset-right)) max(14px, env(safe-area-inset-bottom)) max(22px, env(safe-area-inset-left));
        }
        .credit {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: #e1b368;
            font-size: 14px;
            white-space: nowrap;
        }
        .credit a {
            color: inherit;
            text-decoration: none;
            white-space: nowrap;
        }
        .credit a:hover { color: #f0cb86; }
        .foot .brand { margin-left: auto; flex: 0 1 auto; }
        .foot-links {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 28px;
        }
        .contact {
            gap: 8px;
            color: #e1b368;
            font-size: 14px;
            text-decoration: none;
            white-space: nowrap;
        }
        .contact svg { flex-shrink: 0; color: #e1b368; }
        .contact:hover { color: #f0cb86; }
        @media (max-width: 1180px) {
            .foot { flex-wrap: wrap; row-gap: 8px; }
            .foot .brand { flex: 1 1 100%; min-width: 0; margin-left: 0; justify-content: flex-end; }
            .credit { white-space: normal; flex-wrap: wrap; line-height: 1.45; row-gap: 2px; }
            .foot-links {
                flex: 1 1 100%;
                margin-left: 0;
                justify-content: flex-start;
                flex-wrap: wrap;
                gap: 4px 22px;
            }
            .contact { min-height: 44px; }
        }
        @media (max-width: 860px) {
            .bar {
                gap: 10px;
                padding: 10px max(14px, env(safe-area-inset-right)) 10px max(14px, env(safe-area-inset-left));
            }
            .hotel, .actions .rule { display: none; }
            .bar .brand { gap: 10px; overflow: hidden; }
            .event {
                font-size: clamp(10px, 2.7vw, 15px);
                letter-spacing: 0.04em;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }
        @media (max-width: 640px) {
            .foot {
                align-items: stretch;
                padding: 12px max(16px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left));
            }
            .foot .brand { align-items: flex-start; }
            .foot .rule { height: 22px; margin-top: 4px; }
            .credit { display: block; font-size: 14px; text-align: right; }
            .foot-links {
                flex-direction: column;
                align-items: stretch;
                gap: 0;
            }
            .contact {
                min-height: 48px;
                font-size: 15px;
                padding: 6px 2px;
                border-top: 1px solid rgba(225, 179, 104, 0.28);
            }
            .when { left: 5%; top: 42%; font-size: clamp(12px, 3.4vw, 16px); }
            .count { gap: 8px 12px; padding-top: 10px; padding-bottom: 12px; }
            .count-label { letter-spacing: 0.08em; font-size: 11px; text-align: center; }
            .count-cell { min-width: 58px; padding: 5px 4px 4px; }
        }
        @media (max-width: 420px) {
            .event { font-size: 11px; letter-spacing: 0.06em; }
            .logo-mark, .logo { width: 36px; height: 36px; }
            .logo-mark::before { inset: -3px; }
            .login { min-height: 36px; padding: 6px 10px; font-size: 12px; }
            .credit { font-size: 13px; gap: 6px; }
        }
        @media (max-width: 340px) {
            .event { font-size: 10px; letter-spacing: 0.03em; }
            .bar { gap: 8px; }
            .brand { gap: 8px; }
            .logo-mark, .logo { width: 32px; height: 32px; }
        }
    </style>
</head>
<body>
    <header class="bar">
        <div class="brand">
            <span class="logo-mark">
                <img class="logo" src="{{ url('public/branding/skyline-logo.png') }}" alt="Skyline Hotel">
            </span>
            <span class="rule" aria-hidden="true"></span>
            <p class="event">Chill on the Hill</p>
        </div>
        <div class="actions">
            <span class="hotel">Skyline Hotel Bamenda</span>
            <span class="rule" aria-hidden="true"></span>
            <a class="login" href="{{ url('/login') }}">Admin login</a>
        </div>
    </header>

    <main class="hero">
        <div class="frame">
            <img class="poster" src="{{ url('public/branding/chill-on-the-hill.png') }}?v=dj" width="2048" height="1153" alt="Chill on the Hill at Skyline Hotel Bamenda">
            <p class="when">24th Dec, 2026 - 02 Jan, 2027</p>
        </div>
        <section class="count" id="opening-countdown" aria-label="Countdown to 1 November 2026" data-target="2026-11-01T00:00:00+01:00">
            <p class="count-label">1 November 2026</p>
            <div class="count-grid">
                <div class="count-cell"><strong data-unit="days">00</strong><span>Days</span></div>
                <div class="count-cell"><strong data-unit="hours">00</strong><span>Hours</span></div>
                <div class="count-cell"><strong data-unit="minutes">00</strong><span>Minutes</span></div>
                <div class="count-cell"><strong data-unit="seconds">00</strong><span>Seconds</span></div>
            </div>
            <p class="count-done">1 November 2026 is here</p>
        </section>
    </main>

    <footer class="foot">
        <div class="foot-links">
            <a class="contact" href="mailto:info@skylinehotel.org">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 7 9-7"/></svg>
                info@skylinehotel.org
            </a>
            <a class="contact" href="https://www.skylinehotel.org">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
                www.skylinehotel.org
            </a>
            <a class="contact" href="https://wa.me/237694677541" target="_blank" rel="noopener">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6.5 3.5h3l1.5 4-2 1.2a12 12 0 0 0 6.3 6.3l1.2-2 4 1.5v3A2 2 0 0 1 18.5 19 15 15 0 0 1 5 5.5a2 2 0 0 1 1.5-2z"/></svg>
                +237 694 677 541
            </a>
        </div>
        <div class="brand">
            <span class="logo-mark">
                <img class="logo" src="{{ url('public/branding/skyline-logo.png') }}" alt="">
            </span>
            <span class="rule" aria-hidden="true"></span>
            <p class="credit">Developed By <span aria-hidden="true">|</span> Sr. Engr. Tefu R. Mbole <span aria-hidden="true">|</span> <a href="https://wa.me/237675321739" target="_blank" rel="noopener">237 675 321 739</a></p>
        </div>
    </footer>
    <script>
        (function () {
            var root = document.getElementById('opening-countdown');
            if (!root) return;
            var target = new Date(root.getAttribute('data-target')).getTime();
            var units = {
                days: root.querySelector('[data-unit="days"]'),
                hours: root.querySelector('[data-unit="hours"]'),
                minutes: root.querySelector('[data-unit="minutes"]'),
                seconds: root.querySelector('[data-unit="seconds"]')
            };
            function pad(n) { return (n < 10 ? '0' : '') + n; }
            function tick() {
                var left = target - Date.now();
                if (left <= 0) {
                    root.classList.add('is-done');
                    return;
                }
                var seconds = Math.floor(left / 1000);
                units.days.textContent = pad(Math.floor(seconds / 86400));
                units.hours.textContent = pad(Math.floor((seconds % 86400) / 3600));
                units.minutes.textContent = pad(Math.floor((seconds % 3600) / 60));
                units.seconds.textContent = pad(seconds % 60);
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
</body>
</html>
