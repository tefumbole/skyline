@extends('layout.main')
@section('content')
@php
    $ranges = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];
    $mins = function ($value) {
        return $value === null ? '—' : $value.' min';
    };
@endphp
<section class="sky-sales">
    <nav class="sky-sale-ranges" aria-label="Order lifecycle">
        @foreach ($ranges as $key => $label)
            <a class="{{ $range === $key ? 'on' : '' }}" href="{{ route('report.orderLifecycle', ['range' => $key]) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="sky-sale-head">
        <div>
            <h3>{{ $heading }}</h3>
            <p>{{ $period }}</p>
        </div>
        <div>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.orderLifecycle', ['range' => $range, 'print' => 'letter']) }}">Letterhead</a>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.orderLifecycle', ['range' => $range, 'print' => 'thermal']) }}">Thermal</a>
        </div>
    </div>
    <h4>Average delivery time</h4>
    <table class="sky-sale-table">
        <thead>
            <tr>
                <th>Dish</th>
                <th class="num">Delivered</th>
                <th class="num">Avg time to accept</th>
                <th class="num">Avg time to deliver</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product['name'] }}</td>
                    <td class="num">{{ $product['orders'] }}</td>
                    <td class="num">{{ $mins($product['accept']) }}</td>
                    <td class="num">{{ $mins($product['deliver']) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No orders in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
    <h4>Each order</h4>
    <table class="sky-sale-table">
        <thead>
            <tr>
                <th>Placed</th>
                <th>Order</th>
                <th>Item</th>
                <th class="num">Until accepted</th>
                <th class="num">Until delivered</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lines as $line)
                <tr>
                    <td>{{ $line['when'] }}</td>
                    <td>{{ $line['reference'] }}</td>
                    <td>{{ $line['item'] }} × {{ $line['qty'] }}</td>
                    <td class="num">{{ $mins($line['accept']) }}</td>
                    <td class="num">{{ $mins($line['deliver']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No orders in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
<style>
    .sky-sales { background: #f4f6f8; margin: -8px -12px 0; padding: 12px; }
    .sky-sale-ranges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
    .sky-sale-ranges a { display: inline-flex; min-height: 34px; align-items: center; padding: 4px 12px; border-radius: 999px; background: #f7f1e3; border: 1px solid #e1b368; color: #6b5420; text-decoration: none; font-weight: 700; }
    .sky-sale-ranges a.on { background: #0c1830; border-color: #0c1830; color: #fff; }
    .sky-sale-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .sky-sale-head h3, .sky-sales h4 { margin: 0; color: #0c1830; font-weight: 800; }
    .sky-sale-head p { margin: 4px 0 0; color: #5c6570; }
    .sky-sales h4 { margin: 16px 0 8px; }
    .sky-sale-export { display: inline-flex; min-height: 32px; align-items: center; margin-left: 6px; padding: 4px 10px; border-radius: 8px; background: #0c1830; color: #fff; text-decoration: none; font-weight: 700; font-size: 13px; }
    .sky-sale-table { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 8px; }
    .sky-sale-table th, .sky-sale-table td { padding: 8px; text-align: left; }
    .sky-sale-table .num { text-align: right; white-space: nowrap; }
    .sky-sale-table thead th { background: #0c1830; color: #fff; }
    .sky-sale-table tbody tr:nth-child(even) { background: #f3f5f8; }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#order-lifecycle-menu, #order-lifecycle-{{ $range }}").addClass("active");
</script>
@endsection
