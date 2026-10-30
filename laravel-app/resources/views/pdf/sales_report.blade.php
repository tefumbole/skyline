<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sale Report</title>
    @include('pdf.partials._invoice_styles')
    <style type="text/css">
        .period { text-align: center; margin: 0 0 10px; font-size: 11px; }
        table.sale-lines { width: 100%; border-collapse: collapse; margin: 0; font-size: 9px; }
        table.sale-lines th, table.sale-lines td {
            border: 1px solid #c5cedd;
            padding: 5px 6px;
            vertical-align: middle;
        }
        table.sale-lines th {
            background: #0c1830;
            color: #fffaf3;
            text-align: left;
            font-weight: bold;
        }
        table.sale-lines td.num, table.sale-lines th.num { text-align: right; }
        table.sale-lines tbody tr { page-break-inside: avoid; }
        table.sale-lines tfoot td {
            background: #f4efe4;
            font-weight: bold;
            border-top: 2px solid #0c1830;
        }
    </style>
</head>
<body>
@include('pdf.partials._invoice_open')
<div class="inv-title">Sale Report</div>
<p class="period">{{ date('j F Y', strtotime($start_date)) }} to {{ date('j F Y', strtotime($end_date)) }} · {{ $warehouse_name }}</p>
<table class="sale-lines">
    <thead>
        <tr>
            <th>Date</th>
            <th>Reference</th>
            <th>Product</th>
            <th class="num">Qty</th>
            <th class="num">Price</th>
            <th>Warehouse</th>
            <th class="num">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($lines as $line)
            <tr>
                <td>{{ $line['date'] }}</td>
                <td>{{ $line['reference'] }}</td>
                <td>{{ $line['product'] }}</td>
                <td class="num">{{ number_format((float) $line['qty'], 0) }}</td>
                <td class="num">{{ number_format((float) $line['price'], 0) }}</td>
                <td>{{ $line['warehouse'] }}</td>
                <td class="num">{{ number_format((float) $line['total'], 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No sales in this period.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">Total</td>
            <td class="num">{{ number_format((float) $total, 0) }}</td>
        </tr>
    </tfoot>
</table>
@include('pdf.partials._invoice_close')
</body>
</html>
