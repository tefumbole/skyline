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
    .momo-wait p { color: #24324a; }
    .momo-wait .pay-parts { margin: 14px 0 0; padding: 0; list-style: none; }
    .momo-wait .pay-parts li { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 8px; padding: 10px 12px; border-radius: 12px; background: #fff; }
    .momo-wait .pay-parts em { font-style: normal; font-weight: 700; color: #8a6230; }
    .momo-wait .pay-back { display: none; margin-top: 12px; }
</style>
<section class="momo-wait">
    <div class="card">
        <div class="pay-head">
            <div class="pay-kicker">Mobile Money</div>
            <h1>{{ optional($sale->customer)->name ?: 'Client' }}</h1>
            <div>{{ isset($referenceLabel) ? $referenceLabel : $sale->reference_no }}</div>
        </div>
        <div class="pay-body">
            <div class="pay-due">
                <div class="pay-kicker" style="color:#8a6230">Waiting for</div>
                <strong>{{ number_format((float) $amount, 0) }} XAF</strong>
            </div>
            @if(!empty($parts))
            <ul class="pay-parts" id="momo-parts">
                @foreach($parts as $part)
                <li>
                    <span>{{ $part['phone'] }} · {{ number_format((float) $part['amount'], 0) }} XAF</span>
                    <em>{{ !empty($part['state']) && $part['state'] === 'failed' ? 'Not sent' : 'Waiting' }}</em>
                </li>
                @endforeach
            </ul>
            @endif
            <p id="momo-hint" style="margin-top:14px">{{ !empty($parts) ? 'Each person approves their own share. A share is recorded only after that phone approves it.' : 'A prompt was sent. This bill is confirmed only after the client approves it on their phone.' }}</p>
            <a id="momo-back" class="btn btn-default pay-back" href="{{ $backUrl }}">Back</a>
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
        fetch(@json($statusUrl), {headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.parts) {
                    var rows = document.querySelectorAll('#momo-parts li em');
                    data.parts.forEach(function (part, index) {
                        if (!rows[index]) return;
                        rows[index].textContent = part.state === 'paid' ? 'Approved' : (part.state === 'failed' ? 'Not approved' : 'Waiting');
                    });
                }
                if (data.message && data.status !== 'paid') {
                    hint.textContent = data.message;
                }
                if (data.status === 'paid' && data.url) {
                    stopped = true;
                    hint.textContent = 'Payment received. The bill is confirmed.';
                    window.location = data.url;
                    return;
                }
                if (data.status === 'failed') {
                    stopped = true;
                    hint.textContent = data.message || 'The payment was not approved. The bill was not marked paid.';
                    back.style.display = 'inline-block';
                    return;
                }
                if (Date.now() - started > {{ !empty($parts) ? 480000 : 180000 }}) {
                    hint.textContent = 'Still waiting. If the prompt expired, go back and send it again. The bill is not paid yet.';
                    back.style.display = 'inline-block';
                }
                window.setTimeout(tick, 1500);
            })
            .catch(function () { window.setTimeout(tick, 1500); });
    }
    tick();
})();
</script>
@endsection
