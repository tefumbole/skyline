@extends('layout.main')
@section('content')
@php
    $ranges = ['today' => "Today's Sales", 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];
    $export = ['range' => $range];
@endphp
<section class="sky-sales">
    <p class="sky-kicker">Sales Summary</p>
    <nav class="sky-sale-ranges" aria-label="Customers">
        @foreach ($ranges as $key => $label)
            <a class="sky-sub {{ $range === $key ? 'on' : '' }}" href="{{ route('report.customers', ['range' => $key]) }}">{{ $label }}</a>
        @endforeach
        <a class="on" href="{{ route('report.customers', ['range' => $range]) }}">Customer</a>
    </nav>
    <div class="sky-sale-head">
        <h3>{{ $heading }}</h3>
        <div>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.customers', $export + ['print' => 'letter']) }}">Letterhead</a>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.customers', $export + ['print' => 'thermal']) }}">Thermal</a>
        </div>
    </div>
    <p class="sky-period-line">{{ $period }}</p>
    <div class="sky-sale-table-wrap">
        <table class="sky-sale-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th class="num">Amount spent</th>
                    <th class="num">Visits</th>
                    <th>Items</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $row)
                    @php
                        $bits = [];
                        foreach ($row['items'] as $item => $qty) {
                            $qtyText = abs($qty - round($qty)) < 0.001 ? (string) (int) round($qty) : number_format($qty, 2, '.', ',');
                            $bits[] = $item.' × '.$qtyText;
                        }
                    @endphp
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format((float) $row['amount'], 0, '.', ',') }}</td>
                        <td class="num">{{ $row['orders'] }}</td>
                        <td>{{ implode(', ', $bits) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No customers in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<style>
    .sky-sales { background: #f4f6f8; margin: -8px -12px 0; padding: 12px; }
    .sky-kicker { margin: 0 0 6px; color: #8a7424; font-size: 12px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; }
    .sky-sale-ranges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
    .sky-sale-ranges a { display: inline-flex; align-items: center; min-height: 34px; padding: 4px 12px; border-radius: 999px; text-decoration: none; font-weight: 700; font-size: 13px; background: #f7f1e3; border: 1px solid #e1b368; color: #6b5420; }
    .sky-sale-ranges a.on { background: #0c1830; border-color: #0c1830; color: #fff; }
    .sky-sale-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .sky-sale-head h3 { margin: 0; color: #0c1830; font-weight: 800; }
    .sky-sale-export { display: inline-flex; align-items: center; min-height: 32px; margin-left: 6px; padding: 4px 10px; border-radius: 8px; background: #0c1830; color: #fff; text-decoration: none; font-size: 13px; font-weight: 700; }
    .sky-period-line { color: #5c6570; margin: 4px 0 8px; }
    .sky-sale-table { width: 100%; border-collapse: collapse; background: #fff; }
    .sky-sale-table th, .sky-sale-table td { padding: 8px; text-align: left; font-size: 14px; }
    .sky-sale-table .num { text-align: right; white-space: nowrap; }
    .sky-sale-table tbody tr:nth-child(even) { background: #f3f5f8; }
    .sky-sale-table thead th { background: #0c1830; color: #fff; }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#sale-report-menu, #sale-report-customer, #customer-report-menu").addClass("active");
</script>
@endsection
