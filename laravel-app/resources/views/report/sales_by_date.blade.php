@extends('layout.main')
@section('content')
@php
    $money = function ($amount) {
        return number_format((float) $amount, 0);
    };
    $query = ['start_date' => $start_date, 'end_date' => $end_date, 'warehouse_id' => $warehouse_id];
@endphp
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">Detailed Sale Report</h3>
                <p class="text-center mb-0">{{ date('j F Y', strtotime($start_date)) }} to {{ date('j F Y', strtotime($end_date)) }} · {{ $warehouse_name }}</p>
            </div>
            <form method="get" action="{{ route('report.sales') }}">
                <div class="row mb-3">
                    <div class="col-md-3 offset-md-1 mt-3">
                        <label><strong>From</strong></label>
                        <input type="date" name="start_date" class="form-control" value="{{ $start_date }}" required>
                    </div>
                    <div class="col-md-3 mt-3">
                        <label><strong>To</strong></label>
                        <input type="date" name="end_date" class="form-control" value="{{ $end_date }}" required>
                    </div>
                    <div class="col-md-3 mt-3">
                        <label><strong>{{ trans('file.Warehouse') }}</strong></label>
                        <select name="warehouse_id" class="form-control">
                            <option value="0">{{ trans('file.All Warehouse') }}</option>
                            @foreach($lims_warehouse_list as $warehouse)
                                <option value="{{ $warehouse->id }}" @if((int) $warehouse_id === (int) $warehouse->id) selected @endif>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mt-3">
                        <label class="d-block">&nbsp;</label>
                        <button class="btn btn-primary" type="submit">{{ trans('file.submit') }}</button>
                    </div>
                </div>
            </form>
            <p class="px-3">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('report.sales', ['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'warehouse_id' => $warehouse_id]) }}">Today</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('report.sales', ['start_date' => date('Y-m-01'), 'end_date' => date('Y-m-t'), 'warehouse_id' => $warehouse_id]) }}">This month</a>
                <a class="btn btn-sm btn-primary" href="{{ route('report.sales', $query + ['export' => 'pdf']) }}">Export PDF</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('report.sales', $query + ['export' => 'csv']) }}">Export CSV</a>
            </p>
            <div class="row px-3 pb-3">
                <div class="col-md-3"><strong>{{ $totals['count'] }}</strong> sales</div>
                <div class="col-md-3"><strong>Grand total</strong> {{ $money($totals['grand']) }}</div>
                <div class="col-md-3"><strong>Paid</strong> {{ $money($totals['paid']) }}</div>
                <div class="col-md-3"><strong>Due</strong> {{ $money($totals['due']) }}</div>
            </div>
        </div>
    </div>
    <div class="table-responsive mb-4">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit price</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Tax</th>
                    <th class="text-right">Line total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    @php
                        $due = (float) $sale->grand_total - (float) $sale->paid_amount;
                        $lines = $sale->products;
                        $pay = (int) $sale->payment_status === 4 ? 'Paid' : ((int) $sale->payment_status === 3 ? 'Partial' : ((int) $sale->payment_status === 2 ? 'Due' : 'Pending'));
                    @endphp
                    <tr class="table-active">
                        <td>{{ date('Y-m-d H:i', strtotime($sale->created_at)) }}</td>
                        <td>{{ $sale->reference_no }}</td>
                        <td colspan="4">
                            {{ $sale->customer ? $sale->customer->name : 'Walk-in' }}
                            · {{ $sale->warehouse ? $sale->warehouse->name : '' }}
                            @if($sale->user) · {{ $sale->user->name }} @endif
                            · {{ $pay }}
                        </td>
                        <td class="text-right" colspan="3">
                            Total {{ $money($sale->grand_total) }}
                            · Paid {{ $money($sale->paid_amount) }}
                            · Due {{ $money($due) }}
                        </td>
                    </tr>
                    @forelse($lines as $line)
                        @php
                            $name = $line->product ? $line->product->name : 'Item';
                            if ($line->variant_id && isset($variantNames[$line->variant_id])) {
                                $name .= ' ['.$variantNames[$line->variant_id].']';
                            }
                        @endphp
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ $name }}</td>
                            <td class="text-right">{{ $line->qty + 0 }}</td>
                            <td class="text-right">{{ $money($line->net_unit_price) }}</td>
                            <td class="text-right">{{ $money($line->discount) }}</td>
                            <td class="text-right">{{ $money($line->tax) }}</td>
                            <td class="text-right">{{ $money($line->total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-muted">No line items on this sale.</td>
                        </tr>
                    @endforelse
                    @if((float) $sale->order_discount || (float) $sale->order_tax || (float) $sale->shipping_cost)
                        <tr>
                            <td colspan="9" class="text-right text-muted">
                                @if((float) $sale->order_discount) Order discount {{ $money($sale->order_discount) }} @endif
                                @if((float) $sale->order_tax) · Order tax {{ $money($sale->order_tax) }} @endif
                                @if((float) $sale->shipping_cost) · Shipping {{ $money($sale->shipping_cost) }} @endif
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No sales in this period.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="8" class="text-right">Period total</th>
                    <th class="text-right">{{ $money($totals['grand']) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
<script type="text/javascript">
    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #sale-report-menu").addClass("active");
</script>
@endsection
