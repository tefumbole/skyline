@extends('layout.main')
@section('content')
<style>
    .ready-board { max-width: 860px; margin: 0 auto; padding: 8px 4px 40px; }
    .ready-kicker { margin: 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .ready-board h1 { margin: 4px 0 0; color: #0c1830; font-size: 32px; font-weight: 800; letter-spacing: -.03em; }
    .ready-lead { margin: 8px 0 0; color: #5c6b82; }
    .ready-card { margin-top: 14px; padding: 18px; border: 1px solid #d7deea; border-radius: 18px; background: #fff; box-shadow: 0 10px 28px rgba(12,24,48,.06); }
    .ready-where { margin: 0; color: #0c1830; font-size: 26px; font-weight: 800; letter-spacing: -.03em; }
    .ready-ref { margin: 6px 0 0; color: #5c6b82; font-size: 14px; }
    .ready-who { margin: 6px 0 0; color: #0c1830; font-size: 15px; font-weight: 700; }
    .ready-clock {
        display: inline-block; min-width: 88px; margin-left: 6px; padding: 4px 10px; border-radius: 10px;
        background: #0c1830; color: #e1b368; font-size: 20px; font-weight: 800; letter-spacing: .04em;
        font-variant-numeric: tabular-nums;
    }
    .ready-lines { margin: 14px 0 0; padding: 0; list-style: none; }
    .ready-lines li { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid #e6ebf2; }
    .ready-lines strong { color: #0c1830; font-size: 17px; }
    .ready-lines form { margin: 0; }
    .ready-lines .btn { min-height: 44px; border-radius: 999px; background: #0c1830; border-color: #0c1830; color: #e1b368; font-weight: 700; padding: 8px 16px; }
    .ready-empty { margin-top: 18px; padding: 32px 18px; border: 1px dashed #d7deea; border-radius: 18px; background: #fff; color: #5c6b82; text-align: center; }
    @media (max-width: 640px) {
        .ready-board h1 { font-size: 26px; }
        .ready-where { font-size: 22px; }
        .ready-lines li { align-items: flex-start; flex-direction: column; }
        .ready-lines .btn, .ready-lines form { width: 100%; }
    }
</style>
<section class="container-fluid ready-board" id="ready-board">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <p class="ready-kicker">Floor</p>
    <h1>Ready for Delivery</h1>
    <p class="ready-lead">Each card is one order and the table it should go to.</p>
    @php
        $groups = [];
        foreach ($items as $item) {
            $order = $item->order;
            $key = $order ? (int) $order->id : ('item-'.$item->id);
            if (! isset($groups[$key])) {
                $table = optional(optional($order)->diningTable)->name;
                $service = $order ? (string) $order->service_type : '';
                if ($table) {
                    $where = 'Deliver to '.$table;
                } elseif ($service === 'take_away' || $service === 'takeaway') {
                    $where = 'Take away';
                } elseif ($service === 'dine_in') {
                    $where = 'Dine in';
                } else {
                    $where = 'Take away';
                }
                $sale = $order ? $order->sale : null;
                $groups[$key] = [
                    'where' => $where,
                    'ref' => $sale->reference_no ?? ($order ? $order->id : $item->id),
                    'deliver_by' => optional(optional($order)->placedBy)->name ?: 'Staff',
                    'since' => null,
                    'items' => [],
                ];
            }
            if ($item->ready_at) {
                $marked = $item->ready_at->getTimestamp();
                if ($groups[$key]['since'] === null || $marked < $groups[$key]['since']) {
                    $groups[$key]['since'] = $marked;
                }
            }
            $groups[$key]['items'][] = $item;
        }
    @endphp
    @forelse($groups as $group)
        <article class="ready-card">
            <h2 class="ready-where">{{ $group['where'] }}</h2>
            <p class="ready-ref">{{ $group['ref'] }}</p>
            @php
                $preparedNames = [];
                foreach ($group['items'] as $line) {
                    foreach ([optional($line->acceptedBy)->name, optional($line->readyBy)->name] as $who) {
                        $who = trim((string) $who);
                        if ($who !== '') {
                            $preparedNames[$who] = true;
                        }
                    }
                }
            @endphp
            @if($preparedNames)
                <p class="ready-who">Prepared by: {{ implode(', ', array_keys($preparedNames)) }}</p>
            @endif
            <p class="ready-who">Deliver by: {{ $group['deliver_by'] }}</p>
            @if($group['since'])
                <p class="ready-who">Waiting <span class="ready-clock" data-since="{{ $group['since'] }}">--:--</span></p>
            @endif
            <ul class="ready-lines">
                @foreach($group['items'] as $item)
                    <li>
                        <strong>{{ $item->product_name }} × {{ $item->qty + 0 }}</strong>
                        @if(\App\Services\Restaurant\RestaurantAccess::canDeliver(auth()->user(), $item))
                            <form method="post" action="{{ route('restaurant.deliver', $item->id) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">Delivered</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </article>
    @empty
        <div class="ready-empty">Nothing is waiting to be delivered.</div>
    @endforelse
</section>
<script>
    (function () {
        var url = @json(route('restaurant.alerts'));
        var seen = null;
        function poll() {
            if (document.hidden) return;
            fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin', cache:'no-store'})
                .then(function (res) { return res.ok ? res.json() : {items:[]}; })
                .then(function (data) {
                    var ids = (data.items || []).filter(function (item) { return item.status === 'ready'; }).map(function (item) { return String(item.id); }).sort().join(',');
                    if (seen === null) { seen = ids; return; }
                    if (ids !== seen) window.location.reload();
                }).catch(function () {});
        }
        setInterval(poll, 12000);
        poll();
    })();
    var readyMenu = document.getElementById('ready-delivery-menu');
    if (readyMenu) readyMenu.classList.add('active');
    (function () {
        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function paint() {
            var now = Date.now();
            var clocks = document.querySelectorAll('.ready-clock');
            for (var i = 0; i < clocks.length; i++) {
                var since = parseInt(clocks[i].getAttribute('data-since'), 10) * 1000;
                if (!since) continue;
                var abs = Math.max(0, Math.round((now - since) / 1000));
                var hours = Math.floor(abs / 3600);
                var mins = Math.floor((abs % 3600) / 60);
                var secs = abs % 60;
                clocks[i].textContent = (hours > 0 ? hours + ':' : '') + pad(mins) + ':' + pad(secs);
            }
        }
        paint();
        setInterval(paint, 1000);
    })();
</script>
@endsection
