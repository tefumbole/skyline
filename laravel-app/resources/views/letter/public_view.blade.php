<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $data->subject }} · Letter verification</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f6fb; color: #1f2a44; font-family: Nunito, "Segoe UI", sans-serif; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 20px 16px 40px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 28px rgba(12, 24, 48, .08); overflow: hidden; }
        .banner { padding: 22px 22px 18px; color: #fff; background: #0c1830; }
        .banner.valid { background: #0c1830; }
        .banner.invalid { background: #7a1f1f; }
        .badge { display: inline-block; background: #e1b368; color: #0c1830; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; border-radius: 999px; padding: 6px 12px; font-size: 13px; }
        .banner.invalid .badge { background: #fff; color: #7a1f1f; }
        .banner p { margin: 10px 0 0; color: #f3f6fb; }
        .site { margin: 0 0 8px; color: #e1b368; font-size: 13px; font-weight: 700; }
        .rows { padding: 8px 22px 18px; }
        .row { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #e8eef6; }
        .row:last-child { border-bottom: 0; }
        .label { width: 128px; flex: 0 0 128px; color: #6b778c; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding-top: 2px; }
        .value { flex: 1; font-weight: 700; color: #0c1830; }
        .letter { margin-top: 16px; background: #fff; border-radius: 16px; padding: 22px; box-shadow: 0 10px 28px rgba(12, 24, 48, .08); }
        .letter h2 { margin: 0 0 12px; font-size: 18px; color: #0c1830; }
        .letter .body { line-height: 1.55; }
        .letter .body img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <div class="wrap">
        <article class="card">
            <header class="banner {{ $verify['valid'] ? 'valid' : 'invalid' }}">
                <p class="site">{{ $general_setting->site_title ?? 'Chill On The Hill' }}</p>
                <span class="badge">{{ $verify['status'] }}</span>
                <p>{{ $verify['detail'] }}</p>
            </header>
            <div class="rows">
                <div class="row"><div class="label">Reference</div><div class="value">{{ $data->reference }}</div></div>
                <div class="row"><div class="label">Date</div><div class="value">{{ $verify['date'] !== '' ? $verify['date'] : '—' }}</div></div>
                <div class="row"><div class="label">Subject</div><div class="value">{{ $data->subject }}</div></div>
                <div class="row"><div class="label">Sent to</div><div class="value">{{ count($verify['to']) ? implode(', ', $verify['to']) : '—' }}@if($verify['personalized']) <span style="font-weight:600;color:#6b778c;"> · personalized to each member</span>@endif</div></div>
                @if(count($verify['cc']))
                    <div class="row"><div class="label">Copied to</div><div class="value">{{ implode(', ', $verify['cc']) }}</div></div>
                @endif
                <div class="row"><div class="label">Signed by</div><div class="value">{{ $verify['signed_by'] !== '' ? $verify['signed_by'] : 'Not signed' }}@if($verify['signed_at'] !== '') <span style="font-weight:600;color:#6b778c;"> · {{ $verify['signed_at'] }}</span>@endif</div></div>
                @if($verify['approved_by'] !== '')
                    <div class="row"><div class="label">Approved by</div><div class="value">{{ $verify['approved_by'] }}</div></div>
                @endif
                <div class="row"><div class="label">Author</div><div class="value">{{ $verify['author'] !== '' ? $verify['author'] : '—' }}</div></div>
                @if($data->category)
                    <div class="row"><div class="label">Category</div><div class="value">{{ $data->category->name }}</div></div>
                @endif
                <div class="row"><div class="label">Delivery</div><div class="value">{{ $verify['sent'] ? 'Sent' : 'Not sent yet' }}</div></div>
            </div>
        </article>
        <article class="letter">
            <h2>Subject: {{ $data->subject }}</h2>
            <div class="body">{!! $data->body !!}</div>
            @if(trim(strip_tags((string) $data->footer)) !== '')
                <div class="body" style="margin-top:16px;">{!! $data->footer !!}</div>
            @endif
        </article>
    </div>
</body>
</html>
