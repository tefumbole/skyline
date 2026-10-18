<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} | Chill On The Hill</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            color: #0c1830;
            background: #07111c url('/public/branding/chill-on-the-hill.jpg?v=final') center center / cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 16px;
        }
        .card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 56px rgba(0, 0, 0, 0.32);
            padding: 36px 32px 28px;
            text-align: center;
        }
        .code {
            margin: 0;
            font-size: 56px;
            line-height: 1;
            letter-spacing: -1px;
            color: #e1b368;
        }
        h1 {
            margin: 12px 0 8px;
            font-size: 22px;
        }
        p {
            margin: 0 0 22px;
            color: #5c6b7a;
            line-height: 1.5;
        }
        a {
            display: inline-block;
            background: #0c1830;
            color: #e1b368;
            text-decoration: none;
            font-weight: 700;
            border-radius: 999px;
            padding: 12px 22px;
        }
        @media (max-width: 640px) {
            body { background-position: left center; background-attachment: scroll; }
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="code">{{ $code }}</p>
        <h1>{{ $heading }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ url('/') }}">Back to Chill On The Hill</a>
    </div>
</body>
</html>
