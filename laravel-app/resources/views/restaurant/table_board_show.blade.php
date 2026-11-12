@extends('layout.main')
@section('content')
<style>
    .bill-board { max-width: 760px; margin: 0 auto; padding: 8px 4px 40px; }
    .bill-back { display: inline-flex; align-items: center; min-height: 44px; color: #0c1830; font-weight: 700; text-decoration: none; }
    .bill-board h1 { margin: 8px 0 0; color: #0c1830; font-size: 32px; font-weight: 700; letter-spacing: -.03em; }
    .bill-kicker { margin: 12px 0 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .bill-summary { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 14px; padding: 16px; border-radius: 18px; background: #0c1830; color: #fff; }
    .bill-summary strong { display: block; margin-top: 4px; font-size: 28px; letter-spacing: -.03em; }
    .bill-summary span { color: #e1b368; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .bill-pill { padding: 6px 12px; border-radius: 999px; font-weight: 700; }
    .bill-pill.pending { background: #fff4e5; color: #8a5a1f; }
    .bill-pill.due { background: #fde8e8; color: #8a1f1f; }
    .bill-pill.paid { background: #d9f5e5; color: #178a45; }
    .food-clock { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; background: #0c1830; color: #e1b368; font-variant-numeric: tabular-nums; }
    .food-clock.is-late { background: #8a1f1f; color: #fff; }
    .bill-card { margin-top: 12px; padding: 16px; border: 1px solid #d7deea; border-radius: 18px; background: #fff; box-shadow: 0 10px 28px rgba(12,24,48,.05); }
    .bill-card h2 { margin: 0; color: #0c1830; font-size: 18px; font-weight: 700; }
    .bill-meta { margin: 4px 0 0; color: #5c6b82; font-size: 13px; }
    .bill-lines { margin: 12px 0 0; padding: 0; list-style: none; }
    .bill-lines li { display: block; padding: 10px 0; border-top: 1px solid #e6ebf2; color: #243044; }
    .bill-kind { margin: 0; color: #8a6230; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .bill-dish { margin: 2px 0 0; font-weight: 700; }
    .bill-note, .bill-status { margin: 4px 0 0; color: #5c6b82; font-size: 13px; }
    .bill-status { color: #0c1830; font-weight: 700; }
    .bill-status .state-awaiting { color: #8a5a1f; }
    .bill-status .state-ready, .bill-status .state-hold { color: #8a1f1f; }
    .bill-status .state-delivered { color: #178a45; }
    .bill-money { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 12px; }
    .bill-money div { padding: 10px; border-radius: 12px; background: #f3f6fb; }
    .bill-money span { display: block; color: #5c6b82; font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .bill-money strong { color: #0c1830; font-size: 16px; }
    .bill-pay { width: 100%; min-height: 48px; margin-top: 12px; border: 0; border-radius: 14px; background: #e1b368; color: #0c1830; font-weight: 700; }
    .bill-edit { display: block; width: 100%; min-height: 48px; margin-top: 8px; border: 1px solid #0c1830; border-radius: 14px; background: #fff; color: #0c1830; font-weight: 700; line-height: 46px; text-align: center; text-decoration: none; }
    @media (max-width: 640px) {
        .bill-board h1 { font-size: 26px; }
        .bill-summary strong { font-size: 24px; }
        .bill-money { grid-template-columns: 1fr; }
    }
</style>
<section class="container-fluid bill-board">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <a class="bill-back" href="{{ route('tables.index') }}">&larr; Tables</a>
    <p class="bill-kicker">Table</p>
    <h1>{{ $table['name'] }}</h1>
    @if($table['description'] !== '')<p class="bill-meta">{{ $table['description'] }}</p>@endif
    <div class="bill-summary">
        <div>
            <span>Amount due</span>
            <strong>{{ \App\Support\TableBoard::money($table['due']) }} XAF</strong>
        </div>
        <div class="bill-pill {{ strtolower($table['status']) }}">{{ $table['status'] }}</div>
    </div>
    @foreach($table['bills'] as $bill)
        <article class="bill-card">
            <h2>{{ $bill['reference'] }}</h2>
            <p class="bill-meta">{{ $bill['when_label'] }}@if($bill['customer'] !== '') · {{ $bill['customer'] }}@endif @if(!empty($bill['placed'])) · Placed by {{ $bill['placed'] }}@endif</p>
            @if(!empty($bill['comment']))
                <p class="bill-note">Comment: {{ $bill['comment'] }}</p>
            @endif
            @if(!empty($bill['menu']))
                <ul class="bill-lines">
                    @foreach($bill['menu'] as $line)
                        <li>
                            <p class="bill-kind">{{ $line['kind'] }}</p>
                            <p class="bill-dish">{{ $line['name'] }} × {{ $line['qty'] }}</p>
                            @if($line['note'] !== '')<p class="bill-note">Comment: {{ $line['note'] }}</p>@endif
                            <p class="bill-status"><span class="state-{{ $line['state'] }}">{{ $line['label'] }}</span>@if($line['clock'] === 'down') <span class="food-clock" data-due="{{ (int) $line['due_at'] }}">--:--</span>@elseif($line['clock'] === 'up') <span class="food-clock is-late" data-since="{{ (int) $line['ready_at'] }}">--:--</span>@endif</p>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="bill-money">
                <div><span>Total</span><strong>{{ \App\Support\TableBoard::money($bill['total']) }}</strong></div>
                <div><span>Paid</span><strong>{{ \App\Support\TableBoard::money($bill['paid']) }}</strong></div>
                <div><span>Due</span><strong>{{ \App\Support\TableBoard::money($bill['due']) }}</strong></div>
            </div>
            <p class="bill-meta" style="margin-top:10px;"><span class="bill-pill {{ strtolower($bill['status']) }}">{{ $bill['status'] }}</span></p>
            <a class="bill-edit" href="{{ route('tables.order.edit', [$table['id'], $bill['sale_id']]) }}">Modify order</a>
            @if($canPay && $bill['due'] > 0.009)
                <button type="button" class="bill-pay add-payment" data-id="{{ $bill['sale_id'] }}" data-name="{{ e($bill['customer'] ?: $table['name']) }}" data-phone="{{ e($bill['phone']) }}" data-ref="{{ e($bill['reference']) }}" data-due="{{ $bill['due'] }}" data-toggle="modal" data-target="#add-payment">Add payment</button>
            @endif
        </article>
    @endforeach
</section>
@if($canPay)
    @include('restaurant.partials.table_payment')
@endif
<script>
    var tablesMenu = document.getElementById('tables-menu');
    if (tablesMenu) tablesMenu.classList.add('active');
    (function () {
        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function text(abs) {
            var hours = Math.floor(abs / 3600);
            var mins = Math.floor((abs % 3600) / 60);
            var secs = abs % 60;
            return (hours > 0 ? hours + ':' : '') + pad(mins) + ':' + pad(secs);
        }
        function paint() {
            var now = Date.now();
            var clocks = document.querySelectorAll('.food-clock');
            for (var i = 0; i < clocks.length; i++) {
                var since = parseInt(clocks[i].getAttribute('data-since'), 10);
                if (since) {
                    clocks[i].textContent = text(Math.max(0, Math.round(now / 1000 - since)));
                    clocks[i].className = 'food-clock is-late';
                    continue;
                }
                var due = parseInt(clocks[i].getAttribute('data-due'), 10);
                if (!due) continue;
                var diff = Math.round(due - now / 1000);
                clocks[i].textContent = (diff < 0 ? '-' : '') + text(Math.abs(diff));
                clocks[i].className = diff < 0 ? 'food-clock is-late' : 'food-clock';
            }
        }
        paint();
        setInterval(paint, 1000);
    })();
</script>
@endsection
