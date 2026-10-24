<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @php
        $siteLogoUrl = \App\Support\SiteBrand::logoUrl($general_setting ?? null);
        $siteTitle = \App\Support\SiteBrand::siteTitle($general_setting ?? null);
        $webUser = Auth::guard('web')->user();
        $beyondUser = Auth::guard('beyond')->user();
        $headerUser = $webUser ?: $beyondUser;
        $isAdminSession = (bool) $webUser;
        $headerName = $headerUser ? $headerUser->name : '';
        $headerRole = $isAdminSession
            ? 'ADMINISTRATOR'
            : strtoupper(str_replace('_', ' ', optional($beyondUser)->role ?: 'USER'));
        $headerInitial = $headerName !== '' ? mb_strtoupper(mb_substr($headerName, 0, 1)) : 'U';
        $shortName = \Illuminate\Support\Str::limit($headerName, 18, '…');
    @endphp
    <title>@yield('title', $siteTitle) | {{ $siteTitle }}</title>
    <meta name="description" content="@yield('meta_description', 'Beyond Enterprise — IT consultancy, networks, CCTV security, and professional sound/screen/lighting solutions.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ $siteLogoUrl }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { blue: '#0A0A0A', dark: '#070707', light: '#1a1a1a', gold: '#C5A059', navy: '#0A0A0A' },
                    },
                },
            },
        };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        img, video, svg { max-width: 100%; }
        main { min-width: 0; }
        @keyframes floaty { 0%,100% { transform: translateY(0); opacity:.4 } 50% { transform: translateY(-20px); opacity:.9 } }
        .floaty { animation: floaty 4s ease-in-out infinite; }
        [x-cloak] { display:none !important; }

        @keyframes navLogoSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        /* Gold ↔ Silver metallic shift (no white plate / circle) */
        @keyframes navLogoMetal {
            0%, 100% {
                filter: sepia(1) saturate(4.2) hue-rotate(2deg) brightness(1.12) contrast(1.05)
                    drop-shadow(0 0 8px rgba(212,175,55,.7));
            }
            50% {
                filter: grayscale(1) brightness(1.45) contrast(1.15) saturate(0.2)
                    drop-shadow(0 0 8px rgba(220,220,230,.65));
            }
        }
        .nav-logo-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: .75rem;
            background: transparent;
            border: 0;
            padding: 0;
            box-shadow: none;
        }
        .nav-logo-spin {
            width: 2.75rem;
            height: 2.75rem;
            object-fit: contain;
            background: transparent;
            border-radius: 0;
            animation: navLogoSpin 7s linear infinite, navLogoMetal 5s ease-in-out infinite;
        }
        @media (min-width: 768px) {
            .nav-logo-spin { width: 3.25rem; height: 3.25rem; }
        }
        @media (min-width: 1024px) {
            .nav-logo-spin { width: 3.5rem; height: 3.5rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .nav-logo-spin { animation: none; }
        }

        /* Homepage: one screen — nav + hero + copyright footer, no page scroll */
        body.home-lock {
            height: 100dvh;
            max-height: 100dvh;
            overflow: hidden;
        }
        body.home-lock main {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        body.home-lock .home-hero {
            flex: 1 1 auto;
            min-height: 0;
        }

        /* Transparent footer — dark text on light pages; light text on home hero */
        @keyframes beyCopyIn {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes beyCopyFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        @keyframes beySepPulse {
            0%, 100% { opacity: .35; }
            50% { opacity: .9; }
        }
        .bey-foot {
            position: relative;
            z-index: 80;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            flex-shrink: 0;
        }
        .bey-foot-copy {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 0.35rem 0;
            padding: 0.7rem 1rem 0.95rem;
            font-size: 0.96rem; /* +20% from 0.8rem */
            line-height: 1.45;
            text-align: center;
            background: transparent !important;
            color: #0f2744;
            text-shadow: none;
            animation: beyCopyIn .9s ease-out both, beyCopyFloat 3.6s ease-in-out .9s infinite;
        }
        .bey-foot-copy span {
            padding: 0 0.7rem;
            font-weight: 600;
            letter-spacing: .01em;
        }
        .bey-foot-copy .bey-foot-rights {
            color: #1e3a5f;
        }
        .bey-foot-copy .bey-foot-dev {
            color: #0b4f8a;
        }
        .bey-foot-copy span + span {
            border-left: 1px solid rgba(15, 39, 68, 0.28);
            animation: beySepPulse 2.8s ease-in-out infinite;
        }
        .bey-foot-copy a {
            color: #0a66c2;
            font-weight: 700;
            text-decoration: none;
            pointer-events: auto;
            text-shadow: none;
        }
        .bey-foot-copy a:hover { color: #084a8f; text-decoration: underline; }
        .bey-foot-copy .bey-foot-ver {
            color: #9a6b00;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }
        /* Home hero: keep light-on-dark footer */
        body.home-lock .bey-foot-copy {
            color: #f8fafc;
            text-shadow: 0 1px 2px rgba(0,0,0,.85), 0 0 12px rgba(0,20,50,.55);
        }
        body.home-lock .bey-foot-copy .bey-foot-rights,
        body.home-lock .bey-foot-copy .bey-foot-dev {
            color: #f8fafc;
        }
        body.home-lock .bey-foot-copy span + span {
            border-left-color: rgba(248, 250, 252, 0.45);
        }
        body.home-lock .bey-foot-copy a {
            color: #F5D76E;
            text-shadow: 0 1px 2px rgba(0,0,0,.9);
        }
        body.home-lock .bey-foot-copy a:hover { color: #fff; }
        body.home-lock .bey-foot-copy .bey-foot-ver {
            color: #D4AF37;
        }
        @media (max-width: 640px) {
            .bey-foot-copy { flex-direction: column; gap: 0.2rem; padding: 0.55rem 0.75rem 0.8rem; font-size: 0.9rem; /* +20% from 0.75rem */ }
            .bey-foot-copy span { padding: 0; }
            .bey-foot-copy span + span { border-left: 0; animation: none; }
        }
        body.home-lock { position: relative; }
        body.home-lock main { flex: 1 1 auto; min-height: 0; }
        body.home-lock .bey-foot {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            z-index: 80;
            margin-top: 0;
            pointer-events: none;
            background: transparent !important;
        }
        @media (max-width: 768px), (max-height: 740px) {
            body.home-lock {
                height: auto;
                max-height: none;
                overflow-x: hidden;
                overflow-y: auto;
            }
            body.home-lock main { display: block; }
            body.home-lock .bey-foot {
                position: relative;
                left: auto;
                right: auto;
                bottom: auto;
                pointer-events: auto;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .bey-foot-copy,
            .bey-foot-copy span + span { animation: none; }
        }
    </style>
    @include('beyond.partials.site-chrome-style')
    @stack('head')
</head>
<body class="bg-[#f7f4ee] text-[#07111c] flex flex-col min-h-screen @yield('body_class')">

@php
    $navDefs = [
        'home'     => ['label' => 'Home', 'url' => url('/')],
        'menu'     => ['label' => 'Menu', 'url' => url('/menu')],
        'events'   => ['label' => 'Events', 'url' => url('/events')],
        'about'    => ['label' => 'About', 'url' => url('/about')],
        'register' => ['label' => 'Become a Member', 'url' => url('/register-now')],
        'services' => ['label' => 'Services', 'url' => url('/services')],
        'gallery'  => ['label' => 'Gallery', 'url' => url('/gallery')],
        'reviews'  => ['label' => 'Reviews', 'url' => url('/reviews')],
    ];
    $navLinks = [];
    foreach (\App\Support\SiteMenu::landingOrder() as $navKey) {
        if (isset($navDefs[$navKey])) {
            $navLinks[] = $navDefs[$navKey];
        }
    }
    $currentUrl = url()->current();
@endphp

@include('beyond.partials.site-header')

<main class="flex-1">
    @yield('content')
</main>

@include('beyond.partials.site-footer')

@include('beyond.partials.mbole_ai_widget')

<script src="https://unpkg.com/lucide@latest"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
    document.addEventListener('alpine:initialized', () => { if (window.lucide) lucide.createIcons(); });
</script>
@stack('scripts')
@include('components.whatsapp_phone_script')
<script>
(function () {
    if (window.__eventCountdownInit) return;
    window.__eventCountdownInit = true;
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function bindCountdown(el) {
        if (el.__countdownBound) return;
        el.__countdownBound = true;
        var targetIso = el.getAttribute('data-target');
        if (!targetIso) return;
        var hideAfter = el.getAttribute('data-hide-after') === '1';
        var doneMsg = el.querySelector('[data-done]');
        var units = el.querySelector('[data-units]');
        var target = new Date(targetIso).getTime();
        if (isNaN(target)) return;
        function tick() {
            var diff = target - Date.now();
            if (diff <= 0) {
                if (units) units.classList.add('hidden');
                if (doneMsg) doneMsg.classList.remove('hidden');
                if (hideAfter) setTimeout(function () { el.style.display = 'none'; }, 8000);
                return false;
            }
            var secs = Math.floor(diff / 1000);
            var days = Math.floor(secs / 86400); secs %= 86400;
            var hours = Math.floor(secs / 3600); secs %= 3600;
            var mins = Math.floor(secs / 60); secs %= 60;
            var d = el.querySelector('.cd-days');
            var h = el.querySelector('.cd-hours');
            var m = el.querySelector('.cd-mins');
            var s = el.querySelector('.cd-secs');
            if (d) d.textContent = days;
            if (h) h.textContent = pad(hours);
            if (m) m.textContent = pad(mins);
            if (s) s.textContent = pad(secs);
            return true;
        }
        if (tick()) setInterval(tick, 1000);
    }
    document.querySelectorAll('[data-countdown]').forEach(bindCountdown);
})();
</script>
@include('components.image_paste_script')
<script src="{{ asset('public/js/money-format.js') }}?v=1"></script>
</body>
</html>
