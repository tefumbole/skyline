@extends('layout.main')
@section('content')
<style>
    .log-page { max-width: 1440px; margin: 0 auto; padding: 8px 4px 40px; }
    .log-kicker { margin: 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .log-page h1 { margin: 4px 0 0; color: #0c1830; font-size: 32px; font-weight: 700; letter-spacing: -.03em; }
    .log-lead { margin: 8px 0 0; color: #5c6b82; }
    .log-card { margin-top: 16px; border: 0; border-radius: 18px; background: #fff; box-shadow: 0 12px 32px rgba(12,24,48,.06); overflow: hidden; }
    .log-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 14px; }
    .log-filters .form-control { height: 42px; border: 1px solid #d7deea; border-radius: 12px; color: #0c1830; box-shadow: none; }
    .log-filters .form-control:focus { border-color: #e1b368; }
    .log-filters .grow { flex: 1 1 150px; min-width: 140px; }
    .log-go { height: 42px; padding: 0 18px; border: 0; border-radius: 999px; background: #0c1830; color: #e1b368; font-weight: 700; }
    .log-scroll { overflow-x: auto; }
    .log-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
    .log-table th { padding: 12px 14px; background: #f7f4ee; color: #8a6a32; border: 0; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap; }
    .log-table td { padding: 14px; border-top: 1px solid #eef1f6; color: #1c2434; vertical-align: top; }
    .log-table tbody tr:hover td { background: #f8fafc; }
    .log-ref { color: #0c1830; font-weight: 800; letter-spacing: -.02em; }
    .log-muted { margin-top: 3px; color: #5c6b82; font-size: 12px; }
    .log-name { color: #0c1830; font-weight: 750; }
    .log-time { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .log-time strong { display: block; color: #0c1830; }
    .log-pill { display: inline-block; margin-top: 6px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
    .log-pill.food { background: #f6f0e4; color: #6a5420; }
    .log-pill.drinks { background: #e7eef8; color: #1d3d73; }
    .log-pill.awaiting, .log-pill.hold { background: #fff4e5; color: #8a5a1f; }
    .log-pill.preparing { background: #0c1830; color: #e1b368; }
    .log-pill.ready { background: #fde8e8; color: #8a1f1f; }
    .log-pill.delivered { background: #d9f5e5; color: #178a45; }
    .log-pill.cancelled, .log-pill.rejected { background: #eef1f6; color: #5c6b82; }
    .log-pay { display: inline-block; margin-top: 6px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
    .log-pay.paid { background: #d9f5e5; color: #178a45; }
    .log-pay.partial { background: #fff4e5; color: #8a5a1f; }
    .log-pay.unpaid { background: #fde8e8; color: #8a1f1f; }
    .log-invoice { color: #0c1830; font-weight: 700; text-decoration: none; }
    .log-invoice:hover { color: #8a6230; }
    .log-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px 0; }
    .log-head h2 { margin: 0; color: #0c1830; font-size: 18px; font-weight: 700; }
    .log-add { display: flex; flex-wrap: wrap; gap: 8px; padding: 0 18px 18px; }
    .log-add .form-control { height: 42px; width: 240px; border: 1px solid #d7deea; border-radius: 12px; box-shadow: none; }
    .log-add .btn { height: 42px; border-radius: 999px; border-color: #0c1830; color: #0c1830; font-weight: 700; }
    @media (max-width: 640px) {
        .log-page h1 { font-size: 26px; }
        .log-filters .grow { flex-basis: 100%; }
        .log-go { width: 100%; }
    }
</style>
<section class="container-fluid log-page">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    <p class="log-kicker">Restaurant</p>
    <h1>Order Logs @if(request('lane')) · {{ \App\Services\Restaurant\RestaurantAccess::lanes()[request('lane')] ?? request('lane') }} @endif</h1>
    <p class="log-lead">Time from the order being placed to delivered.</p>
    <form method="get" class="log-card log-filters">
        @if(request('lane'))<input type="hidden" name="lane" value="{{ request('lane') }}">@endif
        <input class="form-control grow" type="date" name="date" value="{{ request('date') }}">
        <select class="form-control grow" name="department">
            <option value="">Department</option>
            <option value="food" @if(request('department')==='food') selected @endif>Food</option>
            <option value="drinks" @if(request('department')==='drinks') selected @endif>Drinks</option>
        </select>
        <select class="form-control grow" name="status">
            <option value="">Status</option>
            @foreach(['awaiting' => 'Waiting', 'preparing' => 'Accepted', 'ready' => 'Ready', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $status => $statusLabel)
                <option value="{{ $status }}" @if(request('status')===$status) selected @endif>{{ $statusLabel }}</option>
            @endforeach
        </select>
        <select class="form-control grow" name="table">
            <option value="">Table</option>
            @foreach($tables as $table)
                <option value="{{ $table->id }}" @if((string)request('table')===(string)$table->id) selected @endif>{{ $table->name }}</option>
            @endforeach
        </select>
        <select class="form-control grow" name="staff">
            <option value="">Staff</option>
            @foreach($staffList as $person)
                <option value="{{ $person->id }}" @if((string)request('staff')===(string)$person->id) selected @endif>{{ $person->name }}</option>
            @endforeach
        </select>
        <button class="log-go" type="submit">Filter</button>
    </form>
    <div class="log-card">
        <div class="log-scroll">
            <table class="log-table">
                <thead>
                <tr>
                    <th>Order</th><th>Source</th><th>Guest / table</th><th>Item</th>
                    <th>Placed</th><th>Accepted</th><th>Estimate</th><th>Ready</th><th>Delivered</th>
                    <th>Acceptance delay</th><th>Preparation</th><th>Waiting</th><th>Total</th>
                    <th>Invoice</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    @php
                        $order = $item->order;
                        $sale = $order ? $order->sale : null;
                        $placed = $order ? $order->created_at : null;
                        $mins = function ($from, $to) {
                            if (! $from || ! $to) return '—';
                            return $from->diffInMinutes($to).' min';
                        };
                        $stateLabels = ['awaiting' => 'Waiting', 'preparing' => 'Accepted', 'ready' => 'Ready', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'hold' => 'On hold', 'rejected' => 'Rejected'];
                        $state = (string) $item->status;
                        $pay = $sale ? (int) $sale->payment_status : 0;
                        $payClass = $pay === 4 ? 'paid' : ($pay === 3 ? 'partial' : 'unpaid');
                        $payLabel = $pay === 4 ? 'Paid' : ($pay === 3 ? 'Partial' : 'Unpaid');
                    @endphp
                    <tr>
                        <td><div class="log-ref">{{ $sale->reference_no ?? '' }}</div></td>
                        <td>
                            <div class="log-name">{{ $order->source ?? '' }}</div>
                            <div class="log-muted">{{ optional($order->placedBy)->name }}</div>
                            <div class="log-muted">Waitress: {{ optional($order->waitress)->name ?: '—' }}</div>
                        </td>
                        <td>
                            <div class="log-name">{{ optional(optional($sale)->customer)->name }}</div>
                            <div class="log-muted">{{ $order ? $order->serviceLabel() : '' }}</div>
                        </td>
                        <td>
                            <div class="log-name">{{ $item->product_name }} × {{ $item->qty + 0 }}</div>
                            <span class="log-pill {{ $item->department }}">{{ ucfirst($item->department) }}</span>
                            <span class="log-pill {{ $state }}">{{ isset($stateLabels[$state]) ? $stateLabels[$state] : ucfirst($state) }}</span>
                        </td>
                        <td class="log-time">{{ $placed ? $placed->format('d M') : '' }}<strong>{{ $placed ? $placed->format('H:i') : '' }}</strong></td>
                        <td class="log-time">{{ optional($item->acceptedBy)->name }}<strong>{{ optional($item->accepted_at)->format('H:i') }}</strong></td>
                        <td>
                            {{ $item->original_estimate_minutes ? $item->original_estimate_minutes.' min' : '—' }}
                            @if($item->estimate_minutes && $item->estimate_minutes != $item->original_estimate_minutes)
                                <div class="log-muted">now {{ $item->estimate_minutes }} min</div>
                            @endif
                        </td>
                        <td class="log-time">{{ optional($item->readyBy)->name }}<strong>{{ optional($item->ready_at)->format('H:i') }}</strong></td>
                        <td class="log-time">{{ optional($item->deliveredBy)->name }}<strong>{{ optional($item->delivered_at)->format('H:i') }}</strong></td>
                        <td>{{ $mins($placed, $item->accepted_at) }}</td>
                        <td>{{ $mins($item->accepted_at, $item->ready_at) }}</td>
                        <td>{{ $mins($item->ready_at, $item->delivered_at) }}</td>
                        <td><strong>{{ $mins($placed, $item->delivered_at) }}</strong></td>
                        <td>
                            @if($sale)
                                <a class="log-invoice" href="{{ route('sale.invoice', $sale->id) }}">{{ $sale->reference_no }}</a>
                                <div><span class="log-pay {{ $payClass }}">{{ $payLabel }}</span></div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14" class="log-muted">No orders for this filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="log-card">
        <div class="log-head"><h2>Rejections</h2></div>
        <div class="log-scroll">
            <table class="log-table">
                <thead><tr><th>When</th><th>Item</th><th>Qty</th><th>Reason</th><th>Resolution</th><th>Staff</th></tr></thead>
                <tbody>
                @forelse($rejections as $rejection)
                    <tr>
                        <td class="log-time">{{ $rejection->created_at }}</td>
                        <td class="log-name">{{ optional($rejection->item)->product_name }}</td>
                        <td>{{ $rejection->qty + 0 }}</td>
                        <td>{{ $rejection->reason }}</td>
                        <td>{{ $rejection->resolution }} @if($rejection->remake_item_id) · remake #{{ $rejection->remake_item_id }} @endif</td>
                        <td>{{ optional($rejection->user)->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="log-muted">No rejections.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="log-card">
        <div class="log-head"><h2>Add a table</h2></div>
        <form method="post" action="{{ route('restaurant.tables.store') }}" class="log-add">
            @csrf
            <input class="form-control" name="name" placeholder="Table name" required>
            <button class="btn btn-default" type="submit">Add</button>
        </form>
    </div>
</section>
@endsection
