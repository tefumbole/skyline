@extends('layout.main')
@section('content')
@php
    $ranges = ['day' => 'Day', 'month' => 'Month', 'year' => 'Year', 'customer' => 'Customer'];
@endphp
<section class="sky-sales">
    <nav class="sky-sale-ranges" aria-label="Payment report">
        @foreach ($ranges as $key => $label)
            <a class="sky-sub {{ $range === $key ? 'on' : '' }}" href="{{ route('report.payments', ['range' => $key]) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="sky-sale-head">
        <h3>{{ $heading }}</h3>
        <div>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.payments', ['range' => $range, 'print' => 'letter']) }}">Letterhead</a>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.payments', ['range' => $range, 'print' => 'thermal']) }}">Thermal</a>
        </div>
    </div>
    <div class="sky-sale-cards">
        <div><span>Collected</span><strong>{{ number_format((float) $cards['total'], 0, '.', ',') }}</strong></div>
        <div><span>Cash</span><strong>{{ number_format((float) $cards['cash'], 0, '.', ',') }}</strong></div>
        <div><span>MoMo / OM</span><strong>{{ number_format((float) $cards['momo'], 0, '.', ',') }}</strong></div>
    </div>
    <table class="sky-sale-table">
        <thead>
            <tr>
                <th>{{ $range === 'customer' ? 'Customer' : 'Period' }}</th>
                <th class="num">Collected</th>
                <th class="num">Cash</th>
                <th class="num">MoMo / OM</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>
                        @if (! empty($row['url']))
                            <a href="{{ $row['url'] }}">{{ $row['label'] }}</a>
                        @else
                            {{ $row['label'] }}
                        @endif
                    </td>
                    <td class="num">{{ number_format((float) $row['total'], 0, '.', ',') }}</td>
                    <td class="num">{{ number_format((float) $row['cash'], 0, '.', ',') }}</td>
                    <td class="num">{{ number_format((float) $row['momo'], 0, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No payments in this period.</td></tr>
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
    .sky-sale-head h3 { margin: 0; color: #0c1830; font-weight: 800; }
    .sky-sale-export { display: inline-flex; min-height: 32px; align-items: center; margin-left: 6px; padding: 4px 10px; border-radius: 8px; background: #0c1830; color: #fff; text-decoration: none; font-weight: 700; font-size: 13px; }
    .sky-sale-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 10px 0; }
    .sky-sale-cards div { background: #fff; border-radius: 12px; text-align: center; padding: 8px; }
    .sky-sale-cards span { display: block; color: #8b93a7; }
    .sky-sale-cards strong { font-size: 22px; color: #0c1830; }
    .sky-sale-table { width: 100%; border-collapse: collapse; background: #fff; }
    .sky-sale-table th, .sky-sale-table td { padding: 8px; text-align: left; }
    .sky-sale-table .num { text-align: right; }
    .sky-sale-table thead th { background: #0c1830; color: #fff; }
    .sky-sale-table tbody tr:nth-child(even) { background: #f3f5f8; }
    .sky-sale-table a { color: #0c1830; font-weight: 700; }
    @media (max-width: 720px) { .sky-sale-cards { grid-template-columns: 1fr; } }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#payment-report-menu, #payment-report-{{ $range }}").addClass("active");
</script>
@endsection
