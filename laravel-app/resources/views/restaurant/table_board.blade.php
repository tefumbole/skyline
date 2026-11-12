@extends('layout.main')
@section('content')
<style>
    .table-board { max-width: 1100px; margin: 0 auto; padding: 8px 4px 28px; }
    .table-board h1 { margin: 4px 0 0; color: #0c1830; font-size: 32px; font-weight: 700; letter-spacing: -.03em; }
    .table-kicker { margin: 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .table-lead { margin: 8px 0 0; color: #5c6b82; font-size: 15px; }
    .table-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-top: 18px; }
    .table-card {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
        min-height: 92px; padding: 16px; border-radius: 18px; background: #fff;
        border: 1px solid #d7deea; box-shadow: 0 10px 28px rgba(12,24,48,.05);
        text-decoration: none; color: inherit;
    }
    .table-card:active { transform: scale(.99); }
    .table-name { margin: 0; color: #0c1830; font-size: 22px; font-weight: 700; }
    .table-sub { margin: 4px 0 0; color: #5c6b82; font-size: 13px; }
    .table-due { margin: 0; color: #0c1830; font-size: 20px; font-weight: 800; letter-spacing: -.03em; text-align: right; }
    .table-pill { display: inline-block; margin-top: 6px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .table-food { margin: 10px 0 0; padding: 0; list-style: none; }
    .table-food li { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 6px; color: #243044; font-size: 13px; font-weight: 700; }
    .table-food .dish { font-weight: 600; color: #5c6b82; }
    .state-awaiting { color: #8a5a1f; }
    .state-preparing { color: #0c1830; }
    .state-ready, .state-hold { color: #8a1f1f; }
    .state-delivered { color: #178a45; }
    .food-clock { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #0c1830; color: #e1b368; font-variant-numeric: tabular-nums; }
    .food-clock.is-late { background: #8a1f1f; color: #fff; }
    .table-pill.pending, .table-pill.open, .table-pill.waiting, .table-pill.accepted { background: #fff4e5; color: #8a5a1f; }
    .table-pill.due, .table-pill.ready { background: #fde8e8; color: #8a1f1f; }
    .table-pill.paid, .table-pill.delivered { background: #d9f5e5; color: #178a45; }
    .table-empty { margin-top: 18px; padding: 28px 18px; border: 1px dashed #d7deea; border-radius: 18px; background: #fff; color: #5c6b82; text-align: center; }
    @media (min-width: 700px) {
        .table-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (min-width: 1024px) {
        .table-grid { grid-template-columns: 1fr 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .table-board h1 { font-size: 26px; }
        .table-card { min-height: 84px; padding: 14px; }
    }
</style>
<section class="container-fluid table-board">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <p class="table-kicker">Sales</p>
    <h1>Tables</h1>
    <p class="table-lead">Each table lists today's orders. Open a table to see every dish and whether it is Waiting, Accepted, Ready for delivery, or Delivered.</p>
    @if(count($tables))
        <div class="table-grid">
            @foreach($tables as $row)
                <a class="table-card" href="{{ route('tables.show', $row['id']) }}">
                    <div>
                        <p class="table-name">{{ $row['name'] }}</p>
                        <p class="table-sub">{{ $row['bills'].' order'.($row['bills'] === 1 ? '' : 's') }}</p>
                        @if(!empty($row['food']))
                            <ul class="table-food">
                                @foreach($row['food'] as $line)
                                    <li>
                                        <span class="dish">{{ $line['name'] }} × {{ $line['qty'] }}</span>
                                        <span class="state-{{ $line['state'] }}">{{ $line['label'] }}</span>
                                        @if($line['clock'] === 'down')
                                            <span class="food-clock" data-due="{{ (int) $line['due_at'] }}">--:--</span>
                                        @elseif($line['clock'] === 'up')
                                            <span class="food-clock is-late" data-since="{{ (int) $line['ready_at'] }}">--:--</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div>
                        <p class="table-due">{{ \App\Support\TableBoard::money(isset($row['total']) ? $row['total'] : $row['due']) }} XAF</p>
                        <div style="text-align:right"><span class="table-pill {{ $row['status_class'] }}">{{ $row['status'] }}</span></div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="table-empty">No table has an open order.</div>
    @endif
</section>
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
