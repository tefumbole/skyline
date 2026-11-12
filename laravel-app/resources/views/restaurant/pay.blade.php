<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay {{ $sale->reference_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e8edf5; font-family: Arial, Helvetica, sans-serif; color: #0c1830; }
        main { max-width: 460px; margin: 24px auto; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(12,24,48,.2); }
        .pay-head { padding: 16px; background: #0c1830; color: #fff; }
        .pay-kicker { color: #e1b368; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 4px 0 0; font-size: 22px; }
        .pay-ref { margin-top: 2px; color: rgba(255,255,255,.72); font-size: 13px; }
        .pay-body { padding: 16px; background: #f3f6fb; }
        .pay-due { margin-bottom: 12px; padding: 12px 14px; border-radius: 14px; background: #fff; }
        .pay-due strong { display: block; margin-top: 2px; font-size: 28px; }
        label { display: block; margin: 0 0 6px; color: #5c6b82; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        input { width: 100%; min-height: 48px; border: 1px solid #d7deea; border-radius: 12px; padding: 0 12px; font-size: 16px; }
        .pay-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 12px 0; }
        .pay-choice button { min-height: 52px; border: 1px solid #d7deea; border-radius: 14px; background: #fff; color: #0c1830; font-weight: 700; }
        .pay-choice button small { display: block; color: #5c6b82; font-weight: 600; }
        .pay-choice button.is-on { border-color: #0c1830; background: #0c1830; color: #fff; }
        .pay-choice button.is-on small { color: #e1b368; }
        .pay-send { width: 100%; min-height: 48px; border: 0; border-radius: 14px; background: #e1b368; color: #0c1830; font-weight: 700; font-size: 16px; }
        .pay-hint, .pay-left { margin-top: 8px; color: #5c6b82; font-size: 13px; }
        .bad { color: #9b1c1c; font-weight: 700; }
        .ok { color: #176b3a; font-weight: 700; }
        @media (max-width: 520px) { main { margin: 0; border-radius: 0; min-height: 100vh; } }
    </style>
</head>
<body>
<main>
    <div class="pay-head">
        <div class="pay-kicker">Add payment</div>
        <h1>{{ optional($sale->customer)->name ?: 'Your bill' }}</h1>
        <div class="pay-ref">{{ $sale->reference_no }}</div>
    </div>
    <div class="pay-body">
        <div class="pay-due">
            @if(!empty($paid))
                <div class="pay-kicker" style="color:#176b3a">Paid</div>
                <strong>Invoice closed</strong>
            @else
                <div class="pay-kicker" style="color:#8a6230">Still due</div>
                <strong id="due-label">{{ number_format((float) $due, 0) }} XAF</strong>
            @endif
        </div>
        @if(session('message'))<p class="ok">{{ session('message') }}</p>@endif
        @if(session('not_permitted'))<p class="bad">{{ session('not_permitted') }}</p>@endif
        @if(!empty($empty))
            <p>There is nothing left to pay on this order.</p>
        @elseif(!empty($paid))
            <p class="ok">This invoice is paid. It is no longer valid for a new payment.</p>
        @elseif(!empty($waiting))
            <p id="momo-hint">A MoMo prompt was sent for {{ number_format((float) $amount, 0) }} XAF. Keep this page open until the phone approves it.</p>
        @else
            <form method="post" action="{{ route('restaurant.pay.start', [$sale->id, \App\Support\SalePayQr::token($sale)]) }}" id="pay-form">
                @csrf
                <label for="momo_phone">Mobile Money number</label>
                <input id="momo_phone" name="momo_phone" inputmode="tel" autocomplete="tel" value="{{ old('momo_phone', optional($sale->customer)->phone_number) }}" required>
                <p class="pay-hint" id="phone-note"></p>
                <div class="pay-choice">
                    <button type="button" class="is-on" id="mode-full">Full amount<small>Pay everything due</small></button>
                    <button type="button" id="mode-part">Partial<small>Pay part now</small></button>
                </div>
                <label for="amount">Amount (XAF)</label>
                <input id="amount" name="amount" type="number" min="100" step="1" value="{{ (int) round((float) $due) }}" readonly>
                <p class="pay-left" id="left-after">Left after this payment: 0 XAF</p>
                <p><button class="pay-send" id="pay-send" type="submit">Send MoMo prompt</button></p>
            </form>
        @endif
    </div>
</main>
@if(!empty($waiting))
<script>
(function () {
    var hint = document.getElementById('momo-hint');
    var started = Date.now();
    var stopped = false;
    function tick() {
        if (stopped) return;
        fetch(@json($statusUrl), {headers: {Accept: 'application/json'}})
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'paid') {
                    stopped = true;
                    hint.className = 'ok';
                    hint.textContent = 'Payment received. Thank you.';
                    return;
                }
                if (data.status === 'failed') {
                    stopped = true;
                    hint.className = 'bad';
                    hint.textContent = data.message || 'The payment was not approved. Nothing was marked paid.';
                    return;
                }
                if (Date.now() - started > 180000) {
                    hint.textContent = 'Still waiting for the phone. If the prompt expired, go back and send it again.';
                }
                window.setTimeout(tick, 1500);
            })
            .catch(function () { window.setTimeout(tick, 1500); });
    }
    tick();
})();
</script>
@else
<script>
(function () {
    var due = {{ (int) round((float) $due) }};
    var amount = document.getElementById('amount');
    var full = document.getElementById('mode-full');
    var part = document.getElementById('mode-part');
    var note = document.getElementById('phone-note');
    var phone = document.getElementById('momo_phone');
    var left = document.getElementById('left-after');
    var form = document.getElementById('pay-form');
    if (!amount || !full || !form) return;
    function money(value) {
        return String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
    function paintLeft() {
        var pay = parseInt(amount.value || '0', 10);
        if (isNaN(pay) || pay < 0) pay = 0;
        if (pay > due) pay = due;
        left.textContent = 'Left after this payment: ' + money(Math.max(0, due - pay)) + ' XAF';
    }
    function setMode(mode) {
        var isFull = mode === 'full';
        full.classList.toggle('is-on', isFull);
        part.classList.toggle('is-on', !isFull);
        amount.readOnly = isFull;
        if (isFull) amount.value = due;
        paintLeft();
    }
    full.addEventListener('click', function () { setMode('full'); });
    part.addEventListener('click', function () { setMode('part'); amount.focus(); });
    amount.addEventListener('input', paintLeft);
    function localNote() {
        var digits = (phone.value || '').replace(/\D/g, '');
        if (digits.indexOf('237') === 0) digits = digits.slice(3);
        if (digits.length < 9) { note.textContent = ''; return; }
        var head = digits.slice(0, 2);
        var ok = head === '67' || head === '65' || head === '68' || head === '69';
        note.textContent = ok ? '' : 'Not an MTN or Orange Cameroon number.';
    }
    phone.addEventListener('input', localNote);
    paintLeft();
    localNote();
})();
</script>
@endif
</body>
</html>
