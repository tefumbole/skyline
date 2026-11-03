@extends('layout.main')
@section('content')
<style>
    .floor-board { max-width: 860px; margin: 0 auto; padding: 8px 4px 32px; }
    .floor-board h1 { margin: 4px 0 0; color: #0c1830; font-size: 32px; font-weight: 700; letter-spacing: -.03em; }
    .floor-kicker { margin: 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .floor-lead { margin: 8px 0 0; color: #5c6b82; }
    .floor-card { margin-top: 12px; padding: 16px; border: 1px solid #d7deea; border-radius: 18px; background: #fff; box-shadow: 0 10px 28px rgba(12,24,48,.05); }
    .floor-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .floor-ref { margin: 0; color: #0c1830; font-size: 18px; font-weight: 700; }
    .floor-where { margin: 6px 0 0; color: #0c1830; font-size: 20px; font-weight: 800; letter-spacing: -.02em; }
    .floor-when { margin: 4px 0 0; color: #5c6b82; font-size: 13px; }
    .floor-pill { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .floor-pill.waiting { background: #e7f2fb; color: #1a2744; }
    .floor-pill.preparing { background: #fff4e5; color: #8a5a1f; }
    .floor-pill.ready { background: #d9f5e5; color: #178a45; }
    .floor-pill.delivered { background: #eef1f6; color: #5c6b82; }
    .floor-lines { margin: 12px 0 0; padding: 0; list-style: none; }
    .floor-lines li { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 0; border-top: 1px solid #e6ebf2; }
    .floor-lines strong { color: #0c1830; }
    .floor-empty { margin-top: 18px; padding: 28px 18px; border: 1px dashed #d7deea; border-radius: 18px; background: #fff; color: #5c6b82; text-align: center; }
    @media (max-width: 640px) {
        .floor-board h1 { font-size: 26px; }
        .floor-where { font-size: 18px; }
        .floor-top { flex-direction: column; }
    }
</style>
<section class="container-fluid floor-board">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <p class="floor-kicker">Orders</p>
    <h1>Orders</h1>
    <p class="floor-lead">Where each order should be delivered, and how far along it is.</p>
    @forelse($orders as $order)
        @php
            $tone = 'waiting';
            if ($order['status'] === 'Being prepared') $tone = 'preparing';
            if ($order['status'] === 'Ready for delivery') $tone = 'ready';
            if ($order['status'] === 'Delivered') $tone = 'delivered';
        @endphp
        <article class="floor-card">
            <div class="floor-top">
                <div>
                    <p class="floor-ref">{{ $order['reference'] }}</p>
                    <p class="floor-where">{{ $order['destination'] }}</p>
                    <p class="floor-when">{{ $order['when'] }}</p>
                </div>
                <span class="floor-pill {{ $tone }}">{{ $order['status'] }}</span>
            </div>
            <ul class="floor-lines">
                @foreach($order['lines'] as $line)
                    <li>
                        <strong>{{ $line['name'] }} × {{ $line['qty'] }}</strong>
                        <span>{{ $line['status'] }}</span>
                    </li>
                @endforeach
            </ul>
        </article>
    @empty
        <div class="floor-empty">No orders to deliver right now.</div>
    @endforelse
</section>
<script>
    var floorMenu = document.getElementById('order-floor-menu');
    if (floorMenu) floorMenu.classList.add('active');
</script>
@endsection
