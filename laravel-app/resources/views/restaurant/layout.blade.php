<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Skyline Hotel')</title>
    <style>
        :root { --navy:#0c1830; --ink:#07111c; --gold:#e1b368; --paper:#f7f4ee; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Georgia, "Times New Roman", serif; background:var(--paper); color:var(--ink); }
        header { background:var(--navy); color:#fff6df; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; gap:12px; }
        header a { color:var(--gold); text-decoration:none; }
        main { max-width:980px; margin:0 auto; padding:20px 16px 48px; }
        h1,h2 { font-weight:500; }
        .card { background:#fff; border:1px solid rgba(12,24,48,.12); border-radius:14px; padding:16px; margin:0 0 14px; }
        label { display:block; font-size:14px; margin:10px 0 4px; }
        input, select, textarea { width:100%; padding:10px 12px; border:1px solid #d9d0c2; border-radius:8px; font:inherit; }
        button, .btn { background:var(--gold); color:var(--ink); border:0; border-radius:999px; padding:10px 16px; font:inherit; cursor:pointer; text-decoration:none; display:inline-block; }
        .btn.ghost { background:transparent; color:var(--navy); border:1px solid var(--gold); }
        .row { display:flex; gap:12px; flex-wrap:wrap; }
        .row > * { flex:1 1 180px; }
        .muted { color:#5c5346; font-size:14px; }
        .alert { padding:10px 12px; border-radius:8px; margin-bottom:12px; }
        .ok { background:#e7f6ea; }
        .bad { background:#fde8e8; }
        .tabs a { margin-right:8px; }
        .tabs a.on { background:var(--navy); color:var(--gold); }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid #eee; vertical-align:top; }
        .steam, .glass { height:28px; width:28px; display:inline-block; vertical-align:middle; }
        .steam { border-radius:50%; background:radial-gradient(circle at 50% 60%, #fff, #e1b368 70%); animation:rise 1.4s ease-in-out infinite; }
        .glass { border:2px solid #0c1830; border-radius:0 0 10px 10px; background:linear-gradient(#e1b368, #0c1830); animation:fill 1.6s ease-in-out infinite; }
        @keyframes rise { 50% { transform:translateY(-4px); opacity:.7; } }
        @keyframes fill { 50% { filter:brightness(1.2); } }
        @media (prefers-reduced-motion: reduce) { .steam, .glass { animation:none; } }
    </style>
</head>
<body>
<header>
    <strong>Skyline Hotel</strong>
    <a href="{{ url('/') }}">Skyline Hotel</a>
</header>
<main>
    @if(session('message'))<div class="alert ok">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert bad">{{ session('not_permitted') }}</div>@endif
    @yield('content')
</main>
</body>
</html>
