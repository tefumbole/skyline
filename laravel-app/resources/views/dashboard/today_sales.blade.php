@php
    $dashMoney = number_format((float) ($dash_total ?? 0), 0, '.', ',');
    $dashQty = (float) ($dash_qty ?? 0);
    $dashQtyText = abs($dashQty - round($dashQty)) < 0.001 ? (string) (int) round($dashQty) : number_format($dashQty, 2, '.', ',');
@endphp
<section class="dash-sales">
    <div class="dash-sale-head">
        <h4>Today's Sales</h4>
        <a href="{{ route('report.sales', ['range' => 'today']) }}">Open report</a>
    </div>
    <div class="dash-sale-cards">
        <div><span>Total Amount</span><strong>{{ $dashMoney }}</strong></div>
        <div><span>Cash</span><strong>{{ number_format((float) ($dash_cash ?? 0), 0, '.', ',') }}</strong></div>
        <div><span>MoMo / OM</span><strong>{{ number_format((float) ($dash_momo ?? 0), 0, '.', ',') }}</strong></div>
    </div>
    <div class="dash-sale-table-wrap">
        <table class="dash-sale-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th class="num">Sales Total (F)</th>
                    <th class="num">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($dash_products ?? []) as $row)
                    @php
                        $qty = (float) $row['qty'];
                        $qtyText = abs($qty - round($qty)) < 0.001 ? (string) (int) round($qty) : number_format($qty, 2, '.', ',');
                    @endphp
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format((float) $row['total'], 0, '.', '') }}</td>
                        <td class="num">{{ $qtyText }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No sales today.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th>Summary</th>
                    <th class="num">{{ $dashMoney }}</th>
                    <th class="num">{{ $dashQtyText }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
<style>
    .dash-sales { background: #f4f6f8; border-radius: 12px; margin: 0 12px 12px; padding: 12px; }
    .dash-sale-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .dash-sale-head h4 { margin: 0; color: #0c1830; font-weight: 800; }
    .dash-sale-head a { color: #0c1830; font-weight: 700; }
    .dash-sale-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 8px; }
    .dash-sale-cards div { background: #fff; border-radius: 12px; text-align: center; padding: 8px; }
    .dash-sale-cards span { display: block; color: #8b93a7; font-size: 13px; }
    .dash-sale-cards strong { display: block; margin-top: 2px; font-size: 22px; color: #1a1a1a; }
    .dash-sale-table { width: 100%; border-collapse: collapse; background: #fff; }
    .dash-sale-table th, .dash-sale-table td { padding: 6px 8px; font-size: 14px; color: #1a1a1a; }
    .dash-sale-table .num { text-align: right; white-space: nowrap; }
    .dash-sale-table tbody tr:nth-child(even) { background: #f3f5f8; }
    .dash-sale-table tfoot th { background: #0c1830; color: #fff; }
    .dash-span-switch .dash-span { border: 0; cursor: pointer; margin-left: 4px; }
    .dash-span-switch .dash-span:not(.on) { opacity: .45; }
    @media (max-width: 640px) {
        .dash-sale-cards { grid-template-columns: 1fr; }
        .dash-sale-cards strong { font-size: 20px; }
    }
</style>
