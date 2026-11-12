@extends('layout.main')
@section('content')
<style>
    .momo-wait { max-width: 460px; margin: 24px auto; }
    .momo-wait .card { border: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(12, 24, 48, .18); }
    .momo-wait .pay-head { padding: 16px; background: #0c1830; color: #fff; }
    .momo-wait .pay-kicker { color: #e1b368; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
    .momo-wait h1 { margin: 4px 0 0; font-size: 22px; }
    .momo-wait .pay-body { padding: 16px; background: #f3f6fb; }
    .momo-wait .pay-due { padding: 12px 14px; border-radius: 14px; background: #fff; }
    .momo-wait .pay-due strong { display: block; margin-top: 2px; color: #0c1830; font-size: 28px; }
    .momo-wait #momo-back { display: none; margin-top: 12px; }
</style>
<section class="momo-wait">
    <div class="card">
        <div class="pay-head">
            <div class="pay-kicker">Mobile Money</div>
            <h1>{{ $customerName }}</h1>
        </div>
        <div class="pay-body">
            <div class="pay-due">
                <div class="pay-kicker" style="color:#8a6230">Waiting for</div>
                <strong>{{ number_format($amount, 0, '.', ' ') }} XAF</strong>
            </div>
            <p id="momo-hint" style="margin-top:14px">Keep this page open. As soon as the phone approves, this page closes and the invoice prints.</p>
            <a id="momo-back" class="btn btn-default" href="{{ url('pos') }}">Back to POS</a>
        </div>
    </div>
</section>
@endsection
@section('scripts')
<script>
(function () {
    var hint = document.getElementById('momo-hint');
    var back = document.getElementById('momo-back');
    var started = Date.now();
    var stopped = false;
    function tick() {
        if (stopped) return;
        fetch(@json($statusUrl), {headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'paid' && (data.print || data.url)) {
                    stopped = true;
                    hint.textContent = 'Payment received. Printing the invoice.';
                    window.location = data.print || data.url;
                    return;
                }
                if (data.status === 'failed') {
                    stopped = true;
                    hint.textContent = data.message || 'The payment was not approved. The order was not placed.';
                    back.style.display = '';
                    return;
                }
                if (Date.now() - started > 180000) {
                    hint.textContent = 'Still waiting. If the prompt expired, go back and send it again.';
                    back.style.display = '';
                }
                window.setTimeout(tick, 1000);
            })
            .catch(function () { window.setTimeout(tick, 1500); });
    }
    tick();
})();
</script>
@endsection
