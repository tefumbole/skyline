<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Chill on the Hill | Skyline Hotel Bamenda</title>
    <meta name="description" content="Skyline Hotel Bamenda presents Chill on the Hill, 24 December 2026 to 2 January 2027.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ url('public/branding/skyline-logo.png') }}">
    @include('beyond.partials.site-chrome-style')
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #07111c; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            color: #f6f1e6;
            overflow: hidden;
        }
        .bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            gap: 16px;
            background: #0c1830;
            padding: 10px max(22px, env(safe-area-inset-right)) 10px max(22px, env(safe-area-inset-left));
        }
        .brand, .actions, .contact {
            display: flex;
            align-items: center;
        }
        .brand { gap: 14px; min-width: 0; flex: 0 0 auto; }
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
            background: conic-gradient(from 0deg, transparent 0 62%, #C5A059 84%, #fff6df 92%, transparent 100%);
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
        .menus {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 6px 12px;
            flex: 1 1 auto;
            min-width: 0;
            overflow-x: auto;
            background: transparent;
            padding: 0;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }
        .menus::-webkit-scrollbar { display: none; }
        .menus a {
            color: #C5A059;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-decoration: none;
            white-space: nowrap;
        }
        .menus a:hover, .menus a.on { color: #fff6df; }
        .menus a.special { color: #C5A059; }
        .hero {
            flex: 1 1 0;
            min-height: 0;
            position: relative;
            line-height: 0;
            background: #07111c;
        }
        .frame {
            position: absolute;
            inset: 0;
        }
        .poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block;
        }
        .when-block {
            position: absolute;
            left: 6.5%;
            top: 48%;
            max-width: 90%;
            line-height: 1.2;
        }
        .when {
            margin: 0 0 0.45em;
            color: #fff;
            font-size: clamp(12px, 1.6vw, 26px);
            font-weight: 600;
            letter-spacing: 0.02em;
            line-height: 1.25;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.65);
        }
        .foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-shrink: 0;
            background: #0c1830;
            padding: 14px max(22px, env(safe-area-inset-right)) max(14px, env(safe-area-inset-bottom)) max(22px, env(safe-area-inset-left));
        }
        .credit {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: #C5A059;
            font-size: 14px;
            white-space: nowrap;
        }
        .credit a {
            color: inherit;
            text-decoration: none;
            white-space: nowrap;
        }
        .credit a:hover { color: #f0cb86; }
        .brand-text {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
            min-width: 0;
        }
        .version {
            margin: 0;
            color: #C5A059;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.08em;
            white-space: nowrap;
        }
        .foot .brand { margin-left: auto; flex: 0 1 auto; }
        .foot-links {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 28px;
        }
        .contact {
            gap: 8px;
            color: #C5A059;
            font-size: 14px;
            text-decoration: none;
            white-space: nowrap;
        }
        .contact svg { flex-shrink: 0; color: #C5A059; }
        .contact:hover { color: #f0cb86; }
        @media (max-width: 1360px) {
            .hotel, .actions .rule { display: none; }
        }
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
            .menus { justify-content: flex-start; gap: 6px 12px; }
            .menus a { font-size: 11px; letter-spacing: 0.05em; }
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
            .when-block { left: 5%; top: 46%; }
            .poster { object-position: left center; }
            .when { font-size: clamp(12px, 3.4vw, 16px); }
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
    @include('beyond.partials.site-header')

    <main class="hero">
        <div class="frame">
            <img class="poster" src="{{ url('public/branding/chill-on-the-hill.jpg') }}?v=final" width="1024" height="576" alt="Chill on the Hill at Skyline Hotel Bamenda">
            <div class="when-block">
                <p class="when">24th Dec, 2026 - 02 Jan, 2027</p>
            </div>
        </div>
    </main>

    @include('beyond.partials.site-footer')
    @include('beyond.partials.mbole_ai_widget')
</body>
</html>
