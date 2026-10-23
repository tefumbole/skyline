@php
    $skyMenus = [
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'Menu', 'url' => url('/menu')],
        ['label' => 'Events', 'url' => url('/events')],
        ['label' => 'About', 'url' => url('/about')],
        ['label' => 'Become a Member', 'url' => url('/register-now')],
        ['label' => 'Services', 'url' => url('/services')],
        ['label' => 'Gallery', 'url' => url('/gallery')],
        ['label' => 'Reviews', 'url' => url('/reviews')],
    ];
    $skyHere = rtrim(url()->current(), '/');
@endphp
<header class="sky-bar">
    <div class="sky-brand">
        <a href="{{ url('/') }}" class="sky-logo-mark" aria-label="Skyline Hotel home">
            <img class="sky-logo" src="{{ url('public/branding/skyline-logo.png') }}" alt="Skyline Hotel">
        </a>
        <span class="sky-rule" aria-hidden="true"></span>
        <p class="sky-event">Chill on the Hill</p>
    </div>
    <nav class="sky-menus" aria-label="Site">
        @foreach($skyMenus as $item)
            <a class="{{ rtrim($item['url'], '/') === $skyHere ? 'on' : '' }}" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>
    <div class="sky-actions">
        <span class="sky-hotel">Skyline Hotel Bamenda</span>
        <span class="sky-rule" aria-hidden="true"></span>
        <a class="sky-login" href="{{ url('/login') }}">Admin login</a>
    </div>
</header>
