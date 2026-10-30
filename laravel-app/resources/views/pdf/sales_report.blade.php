<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detailed Sale Report</title>
    @include('pdf.partials._invoice_styles')
    <style type="text/css">
        table.sale-lines { width: 100%; border-collapse: collapse; margin: 0 0 10px; font-size: 9px; }
        table.sale-lines th, table.sale-lines td { border: 1px solid #dfe3ec; padding: 3px 4px; }
        table.sale-lines th { background: #0c1830; color: #fffaf3; text-align: left; }
        table.sale-lines td.num, table.sale-lines th.num { text-align: right; }
        tr.sale-head td { background: #f4efe4; font-weight: bold; }
        .period { text-align: center; margin: 0 0 8px; font-size: 11px; }
        .totals { width: 100%; border-collapse: collapse; margin: 0 0 10px; }
        .totals td { border: 1px solid #dfe3ec; padding: 5px 6px; width: 25%; background: #f8f9fc; }
    </style>
</head>
<body>
@include('pdf.partials._invoice_open')
@php
    $money = function ($amount) {
        return number_format((float) $amount, 0);
    };
@endphp
<div class="inv-title">Detailed Sale Report</div>
<p class="period">{{ date('j F Y', strtotime($start_date)) }} to {{ date('j F Y', strtotime($end_date)) }} · {{ $warehouse_name }}</p>
<table class="totals">
    <tr>
        <td><strong>Sales</strong><br>{{ $totals['count'] }}</td>
        <td><strong>Grand total</strong><br>{{ $money($totals['grand']) }}</td>
        <td><strong>Paid</strong><br>{{ $money($totals['paid']) }}</td>
        <td><strong>Due</strong><br>{{ $money($totals['due']) }}</td>
    </tr>
</table>
<table class="sale-lines">
    <thead>
        <tr>
            <th>Product</th>
            <th class="num">Qty</th>
            <th class="num">Unit price</th>
            <th class="num">Discount</th>
            <th class="num">Tax</th>
            <th class="num">Line total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sales as $sale)
            @php
                $due = (float) $sale->grand_total - (float) $sale->paid_amount;
                $pay = (int) $sale->payment_status === 4 ? 'Paid' : ((int) $sale->payment_status === 3 ? 'Partial' : ((int) $sale->payment_status === 2 ? 'Due' : 'Pending'));
            @endphp
            <tr class="sale-head">
                <td colspan="6">
                    {{ date('Y-m-d H:i', strtotime($sale->created_at)) }}
                    · {{ $sale->reference_no }}
                    · {{ $sale->customer ? $sale->customer->name : 'Walk-in' }}
                    · {{ $sale->warehouse ? $sale->warehouse->name : '' }}
                    @if($sale->user) · {{ $sale->user->name }} @endif
                    · {{ $pay }}
                    · Total {{ $money($sale->grand_total) }}
                    · Paid {{ $money($sale->paid_amount) }}
                    · Due {{ $money($due) }}
                </td>
            </tr>
            @foreach($sale->products as $line)
                @php
                    $name = $line->product ? $line->product->name : 'Item';
                    if ($line->variant_id && isset($variantNames[$line->variant_id])) {
                        $name .= ' ['.$variantNames[$line->variant_id].']';
                    }
                @endphp
                <tr>
                    <td>{{ $name }}</td>
                    <td class="num">{{ $line->qty + 0 }}</td>
                    <td class="num">{{ $money($line->net_unit_price) }}</td>
                    <td class="num">{{ $money($line->discount) }}</td>
                    <td class="num">{{ $money($line->tax) }}</td>
                    <td class="num">{{ $money($line->total) }}</td>
                </tr>
            @endforeach
        @empty
            <tr>
                <td colspan="6">No sales in this period.</td>
            </tr>
        @endforelse
        <tr class="sale-head">
            <td colspan="5" class="num">Period total</td>
            <td class="num">{{ $money($totals['grand']) }}</td>
        </tr>
    </tbody>
</table>
@include('pdf.partials._invoice_close')
</body>
</html>
