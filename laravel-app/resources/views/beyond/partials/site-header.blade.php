@php
    $skyLabels = \App\Support\SiteMenu::landingItems();
    $skyUrls = [
        'home' => url('/'),
        'events' => url('/events'),
        'about' => url('/about'),
        'register' => url('/register-now'),
        'services' => url('/services'),
        'gallery' => url('/gallery'),
        'reviews' => url('/reviews'),
        'payments' => url('/payments'),
    ];
    $skyMenus = [];
    foreach (\App\Support\SiteMenu::landingOrder() as $skyKey) {
        if (isset($skyLabels[$skyKey], $skyUrls[$skyKey])) {
            $skyMenus[] = ['label' => $skyLabels[$skyKey], 'url' => $skyUrls[$skyKey]];
        }
    }
    $skyHere = rtrim(url()->current(), '/');
@endphp
<header class="sky-bar">
    <div class="sky-brand">
        <a href="{{ url('/') }}" class="sky-logo-mark" aria-label="Skyline Hotel home">
            <img class="sky-logo" src="{{ url('public/branding/skyline-logo.png') }}" alt="Skyline Hotel">
        </a>
        <span class="sky-rule" aria-hidden="true"></span>
        <div class="sky-event-wrap">
            <p class="sky-event">Chill on the Hill</p>
            @if (request()->routeIs('beyond.home'))
                <div class="sky-count" data-sky-count="2026-12-24T00:00:00+01:00" aria-live="polite" aria-label="Countdown to 24 December 2026">
                    <span><b data-unit="d">00</b><small>Days</small></span>
                    <span><b data-unit="h">00</b><small>Hrs</small></span>
                    <span><b data-unit="m">00</b><small>Min</small></span>
                    <span><b data-unit="s">00</b><small>Sec</small></span>
                </div>
            @endif
        </div>
    </div>
    <nav class="sky-menus" aria-label="Site">
        @foreach($skyMenus as $item)
            <a class="{{ rtrim($item['url'], '/') === $skyHere ? 'on' : '' }}" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>
    <div class="sky-actions">
        @auth
            <a class="sky-login" href="{{ url('/admin') }}">Admin Panel</a>
        @else
            <a class="sky-login" href="{{ url('/login') }}">Admin login</a>
        @endauth
    </div>
</header>
@if (request()->routeIs('beyond.home'))
<script>
(function () {
    var box = document.querySelector('[data-sky-count]');
    if (!box) return;
    var target = new Date(box.getAttribute('data-sky-count')).getTime();
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
        var diff = target - Date.now();
        if (diff <= 0) {
            box.innerHTML = '<span class="sky-count-done">24 December</span>';
            return;
        }
        var secs = Math.floor(diff / 1000);
        var days = Math.floor(secs / 86400); secs %= 86400;
        var hours = Math.floor(secs / 3600); secs %= 3600;
        var mins = Math.floor(secs / 60); secs %= 60;
        box.querySelector('[data-unit="d"]').textContent = String(days);
        box.querySelector('[data-unit="h"]').textContent = pad(hours);
        box.querySelector('[data-unit="m"]').textContent = pad(mins);
        box.querySelector('[data-unit="s"]').textContent = pad(secs);
        setTimeout(tick, 1000);
    }
    tick();
})();
</script>
@endif
