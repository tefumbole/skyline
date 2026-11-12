<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $general_setting->site_title ?? 'Bill' }} — {{ $customer->name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin: 0; background: #f3f6fb; color: #0c1830; font-family: Helvetica, Arial, sans-serif; }
        .toolbar { padding: 16px; }
        .toolbar button { min-height: 40px; padding: 0 16px; border: 0; border-radius: 10px; background: #0c1830; color: #fff; font-weight: 700; }
        .sheet { max-width: 420px; margin: 0 auto 24px; padding: 18px 16px 24px; background: #fff; }
        h1 { margin: 0; font-size: 22px; }
        .muted { color: #5c6b82; font-size: 13px; }
        .order { margin-top: 16px; }
        .order h2 { margin: 0 0 6px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 0; vertical-align: top; font-size: 14px; }
        td.amt { text-align: right; white-space: nowrap; }
        .totals { margin-top: 16px; border-top: 1px solid #d7deea; padding-top: 8px; }
        .totals td { font-weight: 700; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { max-width: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print</button></div>
    <article class="sheet">
        <p class="muted">{{ $general_setting->site_title ?? '' }}</p>
        <h1>{{ $customer->name }}</h1>
        <p class="muted">{{ $sales->count() }} {{ $sales->count() === 1 ? 'order' : 'orders' }}</p>
        @php
            $grand = 0;
            $paid = 0;
        @endphp
        @foreach($sales as $sale)
            @php
                $grand += (float) $sale->grand_total;
                $paid += (float) $sale->paid_amount;
                $saleLines = isset($lines[$sale->id]) ? $lines[$sale->id] : collect();
            @endphp
            <section class="order">
                <h2>{{ $sale->reference_no }}</h2>
                <p class="muted">{{ $sale->created_at ? $sale->created_at->format('j M Y H:i') : '' }}</p>
                <table>
                    @forelse($saleLines as $line)
                        <tr>
                            <td>{{ optional($line->product)->name ?: 'Item' }} × {{ $line->qty + 0 }}</td>
                            <td class="amt">{{ number_format((float) $line->total, 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>Order total</td>
                            <td class="amt">{{ number_format((float) $sale->grand_total, 0) }}</td>
                        </tr>
                    @endforelse
                </table>
            </section>
        @endforeach
        <table class="totals">
            <tr><td>Total</td><td class="amt">{{ number_format($grand, 0) }} XAF</td></tr>
            <tr><td>Paid</td><td class="amt">{{ number_format($paid, 0) }} XAF</td></tr>
            <tr><td>Due</td><td class="amt">{{ number_format(max(0, $grand - $paid), 0) }} XAF</td></tr>
        </table>
    </article>
</body>
</html>
