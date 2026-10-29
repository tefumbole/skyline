<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System test · Skyline Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @php
        $total = 0;
        foreach ($sections as $section) {
            $total += count($section['items']);
        }
    @endphp
    <style>
        :root {
            --navy: #0c1830;
            --ink: #07111c;
            --gold: #C5A059;
            --gold-soft: #fffaf3;
            --paper: #f4f6fb;
            --card: #ffffff;
            --line: #e4e9f2;
            --muted: #6f7b91;
            --text: #1f2a44;
            --ok: #146c43;
            --ok-bg: #e8f7ee;
            --bad: #b42318;
            --bad-bg: #fdecec;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            background:
                radial-gradient(1200px 420px at 10% -10%, rgba(197, 160, 89, 0.18), transparent 60%),
                var(--paper);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            padding-bottom: 96px;
        }
        .bar {
            position: sticky;
            top: 0;
            z-index: 30;
            display: flex;
            align-items: center;
            gap: 14px;
            background: var(--ink);
            color: #f6f1e6;
            padding: 12px 22px;
        }
        .mark {
            position: relative;
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .mark::before {
            content: "";
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, transparent 0 62%, #C5A059 84%, #fff6df 92%, transparent 100%);
            animation: spin 2.8s linear infinite;
            -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 2px), #000 calc(100% - 1.5px));
            mask: radial-gradient(farthest-side, transparent calc(100% - 2px), #000 calc(100% - 1.5px));
        }
        .mark img { width: 42px; height: 42px; object-fit: contain; position: relative; z-index: 1; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .brand { display: flex; flex-direction: column; min-width: 0; }
        .brand strong {
            font-family: Cinzel, Georgia, serif;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--gold);
            font-size: 13px;
        }
        .brand span { font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: #f4efe4; }
        .meter { margin-left: auto; min-width: 180px; }
        .meter p { margin: 0 0 6px; font-size: 12px; letter-spacing: .04em; color: #f4efe4; text-align: right; }
        .track { height: 6px; border-radius: 999px; background: rgba(255,255,255,.16); overflow: hidden; }
        .track > span { display: block; height: 100%; width: 0; background: linear-gradient(90deg, #8a6230, #e1b368); transition: width .25s ease; }
        .wrap { max-width: 920px; margin: 0 auto; padding: 28px 16px 24px; }
        .hero h1 {
            margin: 8px 0 8px;
            font-family: Cinzel, Georgia, serif;
            font-size: clamp(32px, 5vw, 48px);
            color: var(--navy);
            letter-spacing: .02em;
        }
        .kicker { margin: 0; letter-spacing: .2em; text-transform: uppercase; color: #8a6230; font-size: 12px; font-weight: 700; }
        .lead { margin: 0; max-width: 640px; color: var(--muted); font-size: 16px; line-height: 1.55; }
        .ok {
            margin-top: 16px;
            background: var(--ok-bg);
            color: var(--ok);
            border-radius: 16px;
            padding: 14px 16px;
            font-weight: 600;
        }
        .name {
            margin-top: 22px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 16px 40px rgba(12, 24, 48, .06);
        }
        .name label { display: block; font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); font-weight: 700; margin-bottom: 8px; }
        .name input {
            width: 100%;
            border: 1px solid var(--line);
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            font: inherit;
            font-size: 16px;
            color: var(--navy);
        }
        .name input:focus { outline: 2px solid var(--gold); border-color: transparent; background: #fff; }
        .nav {
            position: sticky;
            top: 66px;
            z-index: 20;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 12px 0;
            margin: 8px 0 4px;
            background: linear-gradient(var(--paper), var(--paper));
        }
        .nav a {
            flex: 0 0 auto;
            text-decoration: none;
            border: 1px solid var(--gold);
            background: var(--gold-soft);
            color: #3f2a12;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
        }
        .nav a:hover { background: #f6ead0; }
        .sec { margin: 28px 0 8px; }
        .sec h2 { margin: 0; font-size: 24px; color: var(--navy); }
        .sec p { margin: 6px 0 0; color: var(--muted); }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 18px 18px 16px;
            margin: 14px 0;
            box-shadow: 0 10px 30px rgba(12, 24, 48, .04);
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .card.is-pass { border-color: #8fd4ae; box-shadow: 0 10px 30px rgba(20, 108, 67, .08); }
        .card.is-fail { border-color: #f0b4b4; box-shadow: 0 10px 30px rgba(180, 35, 24, .08); }
        .card h3 { margin: 0 0 10px; font-size: 18px; color: var(--navy); }
        ol { margin: 0 0 12px; padding-left: 18px; color: var(--text); }
        ol li { margin: 4px 0; }
        .expect {
            margin: 0 0 14px;
            background: var(--gold-soft);
            border-radius: 14px;
            padding: 12px 14px;
            color: #3f2a12;
        }
        .expect strong { letter-spacing: .04em; text-transform: uppercase; font-size: 11px; display: block; margin-bottom: 4px; color: #8a6230; }
        .opts { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .choice { position: relative; margin: 0; }
        .choice input { position: absolute; opacity: 0; inset: 0; }
        .choice span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: #f8fafc;
            font-weight: 700;
            color: var(--navy);
            cursor: pointer;
        }
        .choice input:focus + span { outline: 2px solid var(--gold); }
        .choice input:checked + span.pass { background: var(--ok); color: #fff; border-color: var(--ok); }
        .choice input:checked + span.fail { background: var(--bad); color: #fff; border-color: var(--bad); }
        textarea {
            width: 100%;
            margin-top: 10px;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 12px 14px;
            font: inherit;
            resize: vertical;
            min-height: 64px;
            background: #f8fafc;
            color: var(--navy);
        }
        textarea:focus { outline: 2px solid var(--gold); border-color: transparent; background: #fff; }
        .dock {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 18px calc(12px + env(safe-area-inset-bottom));
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--line);
        }
        .dock p { margin: 0; color: var(--muted); font-weight: 600; }
        button {
            background: var(--navy);
            color: #fff;
            border: 0;
            border-radius: 14px;
            padding: 14px 22px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: .02em;
        }
        button:hover { background: #16284a; }
        @media (max-width: 640px) {
            .bar { padding: 10px 12px; }
            .meter { min-width: 120px; }
            .brand span { display: none; }
            .opts { grid-template-columns: 1fr; }
            .mark, .mark img { width: 34px; height: 34px; }
        }
    </style>
</head>
<body>
<header class="bar">
    <a class="mark" href="{{ url('/') }}" aria-label="Skyline Hotel home">
        <img src="{{ url('public/branding/skyline-logo.png') }}" alt="">
    </a>
    <div class="brand">
        <strong>Skyline</strong>
        <span>System test</span>
    </div>
    <div class="meter">
        <p id="meter-label">0 of {{ $total }} marked</p>
        <div class="track" aria-hidden="true"><span id="meter-bar"></span></div>
    </div>
</header>
<div class="wrap">
    <div class="hero">
        <p class="kicker">Chill on the Hill</p>
        <h1>System test</h1>
        <p class="lead">Go through the whole site. Mark what is working and what has an issue. Add a short note when something is wrong. Do not make a real payment.</p>
    </div>
    @if($sent)
        <p class="ok">{{ $sent }}</p>
    @endif
    <form method="POST" action="{{ route('system.test.store') }}" id="test-form">
        @csrf
        <div class="name">
            <label for="tester_name">Your name</label>
            <input id="tester_name" type="text" name="tester_name" required maxlength="120" placeholder="Secretary name">
        </div>
        <nav class="nav" aria-label="Test sections">
            @foreach($sections as $section)
                <a href="#sec-{{ $section['id'] }}">{{ $section['title'] }}</a>
            @endforeach
        </nav>
        @foreach($sections as $section)
            <section class="sec" id="sec-{{ $section['id'] }}">
                <h2>{{ $section['title'] }}</h2>
                <p>{{ $section['intro'] }}</p>
            </section>
            @foreach($section['items'] as $item)
                <article class="card" id="card-{{ $item['key'] }}">
                    <h3>{{ $item['title'] }}</h3>
                    <ol>
                        @foreach($item['steps'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                    <p class="expect"><strong>Working when</strong> {{ $item['expect'] }}</p>
                    <div class="opts">
                        <label class="choice">
                            <input type="radio" name="items[{{ $item['key'] }}]" value="pass">
                            <span class="pass">Working</span>
                        </label>
                        <label class="choice">
                            <input type="radio" name="items[{{ $item['key'] }}]" value="fail">
                            <span class="fail">Has an issue</span>
                        </label>
                    </div>
                    <textarea name="notes[{{ $item['key'] }}]" rows="2" placeholder="What should be fixed?"></textarea>
                </article>
            @endforeach
        @endforeach
        <div class="dock">
            <p id="dock-label">0 of {{ $total }} marked</p>
            <button type="submit">Send the report</button>
        </div>
    </form>
</div>
<script>
(function () {
    var total = {{ (int) $total }};
    var form = document.getElementById('test-form');
    var bar = document.getElementById('meter-bar');
    var top = document.getElementById('meter-label');
    var dock = document.getElementById('dock-label');
    function paint() {
        var marked = form.querySelectorAll('input[type="radio"]:checked').length;
        var width = total ? Math.round((marked / total) * 100) : 0;
        bar.style.width = width + '%';
        var text = marked + ' of ' + total + ' marked';
        top.textContent = text;
        dock.textContent = text;
        var cards = form.querySelectorAll('.card');
        for (var i = 0; i < cards.length; i++) {
            var on = cards[i].querySelector('input[type="radio"]:checked');
            cards[i].classList.remove('is-pass', 'is-fail');
            if (on && on.value === 'pass') cards[i].classList.add('is-pass');
            if (on && on.value === 'fail') cards[i].classList.add('is-fail');
        }
    }
    form.addEventListener('change', paint);
})();
</script>
</body>
</html>
