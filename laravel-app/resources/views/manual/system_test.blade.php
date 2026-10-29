<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System test · Skyline Hotel</title>
    <style>
        body { margin: 0; background: #f3f6fb; color: #1f2a44; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .wrap { max-width: 880px; margin: 0 auto; padding: 28px 16px 64px; }
        h1 { color: #0c1830; font-size: 32px; margin: 0; }
        h2 { color: #0c1830; font-size: 22px; margin: 28px 0 8px; }
        .kicker { letter-spacing: .16em; text-transform: uppercase; color: #8a7424; font-size: 12px; font-weight: 700; }
        .lead { color: #6f7b91; }
        .card { background: #fff; border: 1px solid #e6ebf3; border-radius: 16px; padding: 16px; margin: 12px 0; }
        .expect { background: #fffaf3; border-radius: 10px; padding: 10px 12px; }
        label { font-weight: 650; }
        input[type="text"], textarea { width: 100%; box-sizing: border-box; border: 1px solid #d5dce8; border-radius: 8px; padding: 8px 10px; font: inherit; }
        .opts { display: flex; flex-wrap: wrap; gap: 16px; margin: 10px 0; }
        button { background: #0c1830; color: #fff; border: 0; border-radius: 8px; padding: 12px 18px; font-weight: 700; cursor: pointer; }
        .ok { background: #e8f7ee; color: #146c43; border-radius: 12px; padding: 12px 14px; }
    </style>
</head>
<body>
<div class="wrap">
    <p class="kicker">Skyline Hotel</p>
    <h1>System test</h1>
    <p class="lead">Go through the whole site. Mark what is working and what has an issue. Add a short note when something is wrong. Do not make a real payment.</p>
    @if($sent)
        <p class="ok">{{ $sent }}</p>
    @endif
    <form method="POST" action="{{ route('system.test.store') }}">
        @csrf
        <div class="card">
            <label>Your name</label>
            <input type="text" name="tester_name" required maxlength="120" placeholder="Name">
        </div>
        @foreach($sections as $section)
            <h2>{{ $section['title'] }}</h2>
            <p class="lead">{{ $section['intro'] }}</p>
            @foreach($section['items'] as $item)
                <article class="card">
                    <h3 style="margin:0 0 8px;">{{ $item['title'] }}</h3>
                    <ol>
                        @foreach($item['steps'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                    <p class="expect"><strong>Working when:</strong> {{ $item['expect'] }}</p>
                    <div class="opts">
                        <label><input type="radio" name="items[{{ $item['key'] }}]" value="pass"> Working</label>
                        <label><input type="radio" name="items[{{ $item['key'] }}]" value="fail"> Has an issue</label>
                    </div>
                    <textarea name="notes[{{ $item['key'] }}]" rows="2" placeholder="What should be fixed?"></textarea>
                </article>
            @endforeach
        @endforeach
        <button type="submit">Send the report</button>
    </form>
</div>
</body>
</html>
