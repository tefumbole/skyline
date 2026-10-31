<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm cash payment</title>
    <style>
        body { font-family: Georgia, serif; background: #f6f1e6; color: #1a1208; margin: 0; }
        main { max-width: 420px; margin: 40px auto; padding: 24px; background: #fff; border-radius: 16px; }
        button { background: #1a1208; color: #fff; border: 0; border-radius: 999px; padding: 12px 18px; font-weight: 700; }
        .ok { color: #176b3a; }
        .bad { color: #9b1c1c; }
    </style>
</head>
<body>
<main>
    <h1>Confirm cash payment</h1>
    <p>{{ $sale->reference_no }}</p>
    <p><strong>{{ number_format((float) $amount, 0) }} XAF</strong></p>
    @if(session('message'))<p class="ok">{{ session('message') }}</p>@endif
    @if(session('not_permitted'))<p class="bad">{{ session('not_permitted') }}</p>@endif
    @if(!session('message'))
        <form method="post" action="{{ route('sale.cash.confirm.store', [$sale->id, $token]) }}">
            @csrf
            <button type="submit">I confirm this cash payment</button>
        </form>
    @endif
</main>
</body>
</html>
