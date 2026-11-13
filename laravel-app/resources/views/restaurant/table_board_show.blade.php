@extends('layout.main')
@section('content')
<style>
    .bill-board { max-width: 1100px; margin: 0 auto; padding: 8px 4px 28px; }
    .bill-top { display: flex; align-items: baseline; gap: 12px; }
    .bill-back { color: #0c1830; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .bill-board h1 { margin: 0; color: #0c1830; font-size: 26px; font-weight: 700; letter-spacing: -.03em; }
    .bill-sheet { margin-top: 12px; border-radius: 18px; background: #fff; box-shadow: 0 12px 32px rgba(12,24,48,.06); overflow: hidden; }
    .bill-scroll { overflow-x: auto; overflow-y: hidden; }
    .bill-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
    .bill-table th { padding: 11px 12px; background: #0c1830; color: #e1b368; border: 0; box-shadow: inset 0 -3px 0 #e1b368; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap; }
    .bill-table th:first-child { border-top-left-radius: 18px; }
    .bill-table th:last-child { border-top-right-radius: 18px; }
    .bill-table td { padding: 8px 12px; border-top: 1px solid #eef1f6; color: #1c2434; vertical-align: middle; background: #fff; }
    .bill-table tbody tr:nth-child(odd) td { background: #f3f6fb; }
    .bill-client { font-weight: 700; color: #0c1830; white-space: nowrap; }
    .bill-order { max-width: 420px; }
    .bill-amount { margin-top: 2px; color: #0c1830; font-weight: 800; white-space: nowrap; }
    .bill-pill { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .bill-pill.pending, .bill-pill.waiting, .bill-pill.accepted, .bill-pill.hold { background: #fff4e5; color: #8a5a1f; }
    .bill-pill.due, .bill-pill.ready { background: #fde8e8; color: #8a1f1f; }
    .bill-pill.paid, .bill-pill.delivered { background: #d9f5e5; color: #178a45; }
    .bill-pay { margin: 0; padding: 5px 10px; border: 0; border-radius: 999px; background: #e1b368; color: #0c1830; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .bill-empty { margin-top: 16px; color: #5c6b82; }
    @media (max-width: 640px) {
        .bill-board h1 { font-size: 22px; }
    }
</style>
<section class="container-fluid bill-board">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <div class="bill-top">
        <a class="bill-back" href="{{ route('tables.index') }}">&larr; Tables</a>
        <h1>{{ $table['name'] }}</h1>
    </div>
    @if(count($table['bills']))
        <div class="bill-sheet">
            <div class="bill-scroll">
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Order and amount</th>
                            <th>Payment status</th>
                            <th>Order status</th>
                            <th>Add payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($table['bills'] as $bill)
                            @php
                                $dishes = [];
                                foreach ($bill['menu'] as $line) {
                                    $dishes[] = $line['name'].' × '.$line['qty'];
                                }
                                $orderClass = 'waiting';
                                if ($bill['order_status'] === 'Ready for delivery') {
                                    $orderClass = 'ready';
                                } elseif ($bill['order_status'] === 'Accepted') {
                                    $orderClass = 'accepted';
                                } elseif ($bill['order_status'] === 'Delivered') {
                                    $orderClass = 'delivered';
                                } elseif ($bill['order_status'] === 'On hold') {
                                    $orderClass = 'hold';
                                } elseif ($bill['order_status'] === 'Rejected') {
                                    $orderClass = 'rejected';
                                }
                            @endphp
                            <tr>
                                <td class="bill-client">{{ $bill['customer'] !== '' ? $bill['customer'] : 'Walk-in' }}</td>
                                <td class="bill-order">
                                    {{ count($dishes) ? implode(', ', $dishes) : $bill['reference'] }}
                                    <div class="bill-amount">{{ \App\Support\TableBoard::money($bill['total']) }} XAF</div>
                                </td>
                                <td><span class="bill-pill {{ strtolower($bill['status']) }}">{{ $bill['status'] }}</span></td>
                                <td><span class="bill-pill {{ $orderClass }}">{{ $bill['order_status'] }}</span></td>
                                <td>
                                    @if($canPay && $bill['due'] > 0.009)
                                        <button type="button" class="bill-pay add-payment" data-id="{{ $bill['sale_id'] }}" data-name="{{ e($bill['customer'] ?: $table['name']) }}" data-phone="{{ e($bill['phone']) }}" data-ref="{{ e($bill['reference']) }}" data-due="{{ $bill['due'] }}" data-toggle="modal" data-target="#add-payment">Add payment</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <p class="bill-empty">No orders on this table.</p>
    @endif
</section>
@if($canPay)
    @include('restaurant.partials.table_payment')
@endif
<script>
    var tablesMenu = document.getElementById('tables-menu');
    if (tablesMenu) tablesMenu.classList.add('active');
</script>
@endsection
