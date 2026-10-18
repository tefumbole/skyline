@extends('restaurant.layout')
@section('title', 'Track '.$sale->reference_no)
@section('content')
    <h1>{{ $sale->reference_no }}</h1>
    <p>{{ $order->serviceLabel() }} · {{ (int)$sale->payment_status === 4 ? 'Paid' : ((int)$sale->payment_status === 3 ? 'Partly paid' : 'Unpaid') }}</p>
    @php
        $food = $order->items->where('department', 'food');
        $drinks = $order->items->where('department', 'drinks');
        $open = $order->items->filter(function ($item) {
            return in_array($item->status, ['awaiting', 'preparing'], true) && $item->expected_ready_at;
        });
        $latest = null;
        foreach ($open as $item) {
            if (! $latest || $item->expected_ready_at->gt($latest)) {
                $latest = $item->expected_ready_at;
            }
        }
    @endphp
    <div class="card" id="countdown" data-ready="{{ $latest ? $latest->toIso8601String() : '' }}">
        @if($food->where('status', 'preparing')->count())<span class="steam" title="Cooking"></span> Kitchen @endif
        @if($drinks->whereIn('status', ['awaiting', 'preparing'])->count())<span class="glass" title="Preparing"></span> Bar @endif
        <p id="count-text" class="muted">@if($latest) Expected {{ $latest->format('H:i') }} @else Waiting for the kitchen or bar to accept the order. @endif</p>
    </div>
    @foreach(['Food' => $food, 'Drinks' => $drinks] as $label => $rows)
        <div class="card">
            <h2>{{ $label }}</h2>
            @forelse($rows as $item)
                <p><strong>{{ $item->product_name }}</strong> × {{ $item->qty }} — {{ ucfirst($item->status) }}
                    @if($item->status === 'preparing' && $item->expected_ready_at)
                        · ready about {{ $item->expected_ready_at->format('H:i') }}
                    @endif
                </p>
                @if($item->customer_comment)<p class="muted">{{ $item->customer_comment }}</p>@endif
            @empty
                <p class="muted">No {{ strtolower($label) }} on this order.</p>
            @endforelse
        </div>
    @endforeach
    @if($order->customer_comment)<p class="muted">Your note: {{ $order->customer_comment }}</p>@endif
    <p>
        @if((int)$sale->payment_status === 4)
            <strong>Paid.</strong>
        @else
            <a class="btn" href="{{ $payUrl }}">Pay</a>
        @endif
        @if($order->isFulfilled())
            <a class="btn ghost" href="{{ $order->reviewUrl() }}">Review this order</a>
        @endif
    </p>
    <script>
        (function () {
            var box = document.getElementById('countdown');
            var label = document.getElementById('count-text');
            var ready = box.getAttribute('data-ready');
            if (!ready) return;
            var end = new Date(ready).getTime();
            function tick() {
                var left = end - Date.now();
                if (left <= 0) {
                    label.textContent = 'Taking longer than expected';
                    return;
                }
                var mins = Math.floor(left / 60000);
                var secs = Math.floor((left % 60000) / 1000);
                label.textContent = 'About ' + mins + 'm ' + secs + 's';
            }
            tick();
            setInterval(tick, 1000);
        })();
        setTimeout(function () { window.location.reload(); }, 15000);
    </script>
@endsection
