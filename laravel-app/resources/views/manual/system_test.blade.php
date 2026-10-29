<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System test · Skyline Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0c1830; --ink: #07111c; --gold: #C5A059; --gold-soft: #fffaf3;
            --paper: #f4f6fb; --line: #e4e9f2; --muted: #6f7b91; --text: #1f2a44;
            --ok: #146c43; --bad: #b42318;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: radial-gradient(1200px 420px at 10% -10%, rgba(197, 160, 89, 0.18), transparent 60%), var(--paper);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            padding-bottom: 40px;
        }
        .bar { display: flex; align-items: center; gap: 14px; background: var(--ink); color: #f6f1e6; padding: 12px 22px; }
        .mark { width: 42px; height: 42px; display: grid; place-items: center; position: relative; }
        .mark img { width: 42px; height: 42px; object-fit: contain; }
        .brand strong { font-family: Cinzel, Georgia, serif; letter-spacing: .18em; text-transform: uppercase; color: var(--gold); font-size: 13px; display: block; }
        .brand span { font-size: 12px; letter-spacing: .12em; text-transform: uppercase; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 28px 16px 40px; }
        h1 { margin: 8px 0; font-family: Cinzel, Georgia, serif; font-size: clamp(32px, 5vw, 46px); color: var(--navy); }
        .kicker { margin: 0; letter-spacing: .2em; text-transform: uppercase; color: #8a6230; font-size: 12px; font-weight: 700; }
        .lead { color: var(--muted); line-height: 1.55; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 22px; padding: 18px; margin: 14px 0; box-shadow: 0 10px 30px rgba(12, 24, 48, .04); }
        label { display: block; font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); font-weight: 700; margin-bottom: 8px; }
        input[type="text"], input[type="tel"], textarea {
            width: 100%; border: 1px solid var(--line); background: #f8fafc; border-radius: 12px;
            padding: 14px 16px; font: inherit; font-size: 16px; color: var(--navy);
        }
        input:focus, textarea:focus { outline: 2px solid var(--gold); border-color: transparent; background: #fff; }
        button, .btn {
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--navy); color: #fff; border: 0; border-radius: 14px;
            padding: 14px 18px; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none;
        }
        .btn.light { background: #fff; color: var(--navy); border: 1px solid var(--line); }
        .row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .error { background: #fdecec; color: var(--bad); border-radius: 14px; padding: 12px 14px; }
        .ok { background: #e8f7ee; color: var(--ok); border-radius: 14px; padding: 12px 14px; }
        .name { font-size: 22px; color: var(--navy); font-weight: 700; margin: 0; }
        .opts { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .choice { position: relative; margin: 0; }
        .choice input { position: absolute; opacity: 0; }
        .choice span { display: flex; align-items: center; justify-content: center; min-height: 48px; border-radius: 14px; border: 1px solid var(--line); background: #f8fafc; font-weight: 700; cursor: pointer; }
        .choice input:checked + span.pass { background: var(--ok); color: #fff; border-color: var(--ok); }
        .choice input:checked + span.fail { background: var(--bad); color: #fff; border-color: var(--bad); }
        .expect { background: var(--gold-soft); border-radius: 14px; padding: 12px 14px; color: #3f2a12; }
        .expect strong { display: block; letter-spacing: .04em; text-transform: uppercase; font-size: 11px; color: #8a6230; margin-bottom: 4px; }
        .score { font-size: 64px; line-height: 1; font-weight: 700; color: var(--navy); margin: 8px 0; }
        .track { height: 8px; background: #e6ebf3; border-radius: 999px; overflow: hidden; margin: 10px 0; }
        .track > span { display: block; height: 100%; background: linear-gradient(90deg, #8a6230, #e1b368); }
        .who { color: var(--muted); font-size: 14px; }
        a.quiet { color: #8a6230; }
        @media (max-width: 640px) { .opts { grid-template-columns: 1fr; } }
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
</header>
<div class="wrap">
    <p class="kicker">Chill on the Hill</p>
    <h1>System test</h1>
    @if(!empty($error))
        <p class="error">{{ $error }}</p>
    @endif
    @if(!empty($notice))
        <p class="ok">{{ $notice }}</p>
    @endif

    @if($step === 'unavailable')
        <p class="lead">The test form is not ready yet. Try again in a minute.</p>
    @endif

    @if($step === 'phone')
        <p class="lead">Enter the WhatsApp number for the person doing this test. If this number already started a test, the saved stages come back after the code. A code is sent on WhatsApp to confirm it is you.</p>
        <form class="card" method="POST" action="{{ route('system.test.phone') }}">
            @csrf
            <label for="phone">WhatsApp number</label>
            <input id="phone" type="tel" name="phone" required placeholder="675 321 739" autocomplete="tel">
            <p class="who">A Cameroon number does not need +237. Use the same number to continue a test you already started.</p>
            <button type="submit">Continue</button>
        </form>
    @endif

    @if($step === 'confirm')
        <p class="lead">{{ $phone }}</p>
        <form class="card" method="POST" action="{{ route('system.test.code') }}">
            @csrf
            @if($source === 'system')
                <p class="who">This number is already in Skyline.</p>
                <p class="name">{{ $name }}</p>
            @elseif($source === 'campay')
                <p class="who">Campay found this name.</p>
                <p class="name">{{ $name }}</p>
            @else
                <label for="tester_name">Your name</label>
                <input id="tester_name" type="text" name="tester_name" required maxlength="120" value="{{ $name }}" placeholder="Your name">
            @endif
            <div class="row" style="margin-top:16px;">
                <button type="submit">Send code</button>
                <a class="btn light" href="{{ route('system.test.reset') }}">Use a different number</a>
            </div>
        </form>
    @endif

    @if($step === 'otp')
        <p class="lead">{{ $name }}, enter the code sent to {{ $phone }}.</p>
        <form class="card" method="POST" action="{{ route('system.test.verify') }}">
            @csrf
            <label for="code">WhatsApp code</label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required maxlength="6" placeholder="6 digits">
            <div class="row" style="margin-top:16px;">
                <button type="submit">Start the test</button>
            </div>
        </form>
        <form method="POST" action="{{ route('system.test.code') }}">
            @csrf
            <button class="btn light" type="submit">Send the code again</button>
        </form>
        <p><a class="quiet" href="{{ route('system.test.reset') }}">Use a different number</a></p>
    @endif

    @if($step === 'stage')
        <p class="who">Testing as {{ $report->tester_name }} · {{ $phone }}</p>
        <p class="lead">Stage {{ $stage }} of {{ $stageCount }} · {{ $summary['answered'] }} of {{ $summary['total'] }} checks marked</p>
        <div class="track" aria-hidden="true"><span style="width: {{ $summary['total'] ? round(($summary['answered'] / $summary['total']) * 100) : 0 }}%"></span></div>
        @if(! $summary['complete'])
            <p class="who">The site score is final only when every check is marked. {{ count($summary['open']) }} are still open.</p>
        @endif
        <h2 style="color:var(--navy);margin-bottom:4px;">{{ $section['title'] }}</h2>
        <p class="lead">{{ $section['intro'] }}</p>
        <form method="POST" action="{{ route('system.test.stage') }}">
            @csrf
            <input type="hidden" name="stage" value="{{ $stage }}">
            @foreach($section['items'] as $item)
                @php
                    $row = isset($saved[$item['key']]) && is_array($saved[$item['key']]) ? $saved[$item['key']] : [];
                    $marked = isset($row['result']) ? $row['result'] : '';
                    $note = isset($row['note']) ? $row['note'] : '';
                @endphp
                <article class="card">
                    <h3 style="margin:0 0 8px;color:var(--navy);">{{ $item['title'] }}</h3>
                    <ol>
                        @foreach($item['steps'] as $one)
                            <li>{{ $one }}</li>
                        @endforeach
                    </ol>
                    <p class="expect"><strong>Working when</strong> {{ $item['expect'] }}</p>
                    <div class="opts">
                        <label class="choice">
                            <input type="radio" name="items[{{ $item['key'] }}]" value="pass" @if($marked === 'pass') checked @endif>
                            <span class="pass">Working</span>
                        </label>
                        <label class="choice">
                            <input type="radio" name="items[{{ $item['key'] }}]" value="fail" @if($marked === 'fail') checked @endif>
                            <span class="fail">Has an issue</span>
                        </label>
                    </div>
                    <textarea name="notes[{{ $item['key'] }}]" rows="2" placeholder="What should be fixed?">{{ $note }}</textarea>
                </article>
            @endforeach
            <div class="row">
                @if($stage < $stageCount)
                    <button type="submit" name="action" value="next">Save and continue</button>
                @else
                    <button type="submit" name="action" value="finish">Finish and send</button>
                @endif
                @if($stage > 1)
                    <button class="btn light" type="submit" name="action" value="back">Back</button>
                @endif
            </div>
        </form>
        <p><a class="quiet" href="{{ route('system.test.reset') }}">Sign out of this test</a></p>
    @endif

    @if($step === 'done')
        <p class="who">{{ $report->tester_name }} · {{ $phone }}</p>
        <p class="score">{{ $summary['score'] }}%</p>
        <p class="lead">Site score. {{ $summary['passed'] }} of {{ $summary['total'] }} checks are working. Every check was tested.</p>
        <h2 style="color:var(--navy);">What is wrong</h2>
        @if(count($summary['issues']) === 0)
            <p class="lead">Nothing was marked as an issue.</p>
        @endif
        @foreach($summary['issues'] as $issue)
            <article class="card">
                <strong>{{ $issue['label'] }}</strong>
                @if($issue['note'] !== '')
                    <p>{{ $issue['note'] }}</p>
                @endif
            </article>
        @endforeach
        <h2 style="color:var(--navy);">All comments</h2>
        @if(count($summary['comments']) === 0)
            <p class="lead">No comments.</p>
        @endif
        @foreach($summary['comments'] as $comment)
            <article class="card">
                <strong>{{ $comment['label'] }}</strong>
                <p>{{ $comment['note'] }}</p>
            </article>
        @endforeach
        <p><a class="btn" href="{{ route('system.test', ['stage' => 1]) }}">Review the stages</a></p>
    @endif
</div>
</body>
</html>
