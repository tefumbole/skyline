@extends('layout.main')
@section('content')
    <section class="container-fluid">
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        <h3>Order Logs</h3>
        <form method="get" class="row" style="margin-bottom:16px;">
            <div class="col-md-2"><input class="form-control" type="date" name="date" value="{{ request('date') }}"></div>
            <div class="col-md-2">
                <select class="form-control" name="department">
                    <option value="">Department</option>
                    <option value="food" @if(request('department')==='food') selected @endif>Food</option>
                    <option value="drinks" @if(request('department')==='drinks') selected @endif>Drinks</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-control" name="status">
                    <option value="">Status</option>
                    @foreach(['awaiting','preparing','ready','delivered','cancelled'] as $status)
                        <option value="{{ $status }}" @if(request('status')===$status) selected @endif>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-control" name="table">
                    <option value="">Table</option>
                    @foreach($tables as $table)
                        <option value="{{ $table->id }}" @if((string)request('table')===(string)$table->id) selected @endif>{{ $table->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-control" name="staff">
                    <option value="">Staff</option>
                    @foreach($staffList as $person)
                        <option value="{{ $person->id }}" @if((string)request('staff')===(string)$person->id) selected @endif>{{ $person->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary" type="submit">Filter</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Order</th><th>Source</th><th>Guest / table</th><th>Item</th>
                    <th>Placed</th><th>Accepted</th><th>Estimate</th><th>Ready</th><th>Delivered</th>
                    <th>Acceptance delay</th><th>Preparation</th><th>Waiting</th><th>Total</th>
                    <th>Invoice</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    @php
                        $order = $item->order;
                        $sale = $order ? $order->sale : null;
                        $placed = $order ? $order->created_at : null;
                        $mins = function ($from, $to) {
                            if (! $from || ! $to) return '—';
                            return $from->diffInMinutes($to).' min';
                        };
                    @endphp
                    <tr>
                        <td>{{ $sale->reference_no ?? '' }}</td>
                        <td>{{ $order->source ?? '' }}<div>{{ optional($order->placedBy)->name }}</div><div>Waitress: {{ optional($order->waitress)->name ?: '—' }}</div></td>
                        <td>{{ optional(optional($sale)->customer)->name }}<div>{{ $order ? $order->serviceLabel() : '' }}</div></td>
                        <td>{{ $item->product_name }} × {{ $item->qty }}<div>{{ ucfirst($item->department) }} · {{ $item->status }}</div></td>
                        <td>{{ $placed ? $placed->format('d M H:i') : '' }}</td>
                        <td>{{ optional($item->acceptedBy)->name }}<div>{{ optional($item->accepted_at)->format('H:i') }}</div></td>
                        <td>{{ $item->original_estimate_minutes ? $item->original_estimate_minutes.' min' : '—' }}
                            @if($item->estimate_minutes && $item->estimate_minutes != $item->original_estimate_minutes)
                                <div>now {{ $item->estimate_minutes }} min</div>
                            @endif
                        </td>
                        <td>{{ optional($item->readyBy)->name }}<div>{{ optional($item->ready_at)->format('H:i') }}</div></td>
                        <td>{{ optional($item->deliveredBy)->name }}<div>{{ optional($item->delivered_at)->format('H:i') }}</div></td>
                        <td>{{ $mins($placed, $item->accepted_at) }}</td>
                        <td>{{ $mins($item->accepted_at, $item->ready_at) }}</td>
                        <td>{{ $mins($item->ready_at, $item->delivered_at) }}</td>
                        <td>{{ $mins($placed, $item->delivered_at) }}</td>
                        <td>
                            @if($sale)
                                <a href="{{ route('sale.invoice', $sale->id) }}">{{ $sale->reference_no }}</a>
                                <div>{{ (int)$sale->payment_status === 4 ? 'Paid' : ((int)$sale->payment_status === 3 ? 'Partial' : 'Unpaid') }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <h4>Rejections</h4>
        <table class="table">
            <thead><tr><th>When</th><th>Item</th><th>Qty</th><th>Reason</th><th>Resolution</th><th>Staff</th></tr></thead>
            <tbody>
            @foreach($rejections as $rejection)
                <tr>
                    <td>{{ $rejection->created_at }}</td>
                    <td>{{ optional($rejection->item)->product_name }}</td>
                    <td>{{ $rejection->qty }}</td>
                    <td>{{ $rejection->reason }}</td>
                    <td>{{ $rejection->resolution }} @if($rejection->remake_item_id) · remake #{{ $rejection->remake_item_id }} @endif</td>
                    <td>{{ optional($rejection->user)->name }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <h4>Add a table</h4>
        <form method="post" action="{{ route('restaurant.tables.store') }}" class="form-inline">
            @csrf
            <input class="form-control" name="name" placeholder="Table name" required>
            <button class="btn btn-default" type="submit">Add</button>
        </form>
    </section>
@endsection
