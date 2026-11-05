@extends('layout.main')
@section('content')
@php
    $ranges = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];
    $money = function ($amount) {
        return number_format((float) $amount, 0, '.', ',');
    };
    $topAmount = $cards['top_amount'] ?? null;
    $topVisits = $cards['top_frequency'] ?? null;
@endphp
<section class="sky-sales">
    <nav class="sky-sale-ranges" aria-label="Table report">
        @foreach ($ranges as $key => $label)
            <a class="{{ $range === $key ? 'on' : '' }}" href="{{ route('report.tables', ['range' => $key]) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="sky-sale-head">
        <div>
            <h3>{{ $heading }}</h3>
            <p>{{ $period }}</p>
        </div>
        <div>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.tables', ['range' => $range, 'print' => 'letter']) }}">Letterhead</a>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.tables', ['range' => $range, 'print' => 'thermal']) }}">Thermal</a>
        </div>
    </div>
    <div class="sky-sale-cards">
        <div><span>Sold</span><strong>{{ $money($cards['sold'] ?? 0) }}</strong></div>
        <div><span>Paid</span><strong>{{ $money($cards['paid'] ?? 0) }}</strong></div>
        <div><span>Pending</span><strong>{{ $money($cards['pending'] ?? 0) }}</strong></div>
        <div><span>Bills</span><strong>{{ (int) ($cards['bills'] ?? 0) }}</strong></div>
    </div>
    <div class="sky-sale-cards">
        <div>
            <span>Highest by amount</span>
            <strong>{{ $topAmount ? $topAmount['name'] : '—' }}</strong>
            <small>{{ $topAmount ? $money($topAmount['sold']).' · '.$topAmount['bills'].' bill'.($topAmount['bills'] === 1 ? '' : 's') : 'No table sales' }}</small>
        </div>
        <div>
            <span>Highest by frequency</span>
            <strong>{{ $topVisits ? $topVisits['name'] : '—' }}</strong>
            <small>{{ $topVisits ? $topVisits['bills'].' bill'.($topVisits['bills'] === 1 ? '' : 's').' · '.$money($topVisits['sold']) : 'No table sales' }}</small>
        </div>
    </div>
    <table class="sky-sale-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Table</th>
                <th class="num">Sold</th>
                <th class="num">Paid</th>
                <th class="num">Pending</th>
                <th class="num">Bills</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['rank'] }}</td>
                    <td>
                        {{ $row['name'] }}
                        @if ($topAmount && (int) $topAmount['id'] === (int) $row['id'])
                            <span class="sky-tag">Top amount</span>
                        @endif
                        @if ($topVisits && (int) $topVisits['id'] === (int) $row['id'] && (! $topAmount || (int) $topAmount['id'] !== (int) $row['id']))
                            <span class="sky-tag">Most bills</span>
                        @endif
                    </td>
                    <td class="num">{{ $money($row['sold']) }}</td>
                    <td class="num">{{ $money($row['paid']) }}</td>
                    <td class="num">{{ $money($row['pending']) }}</td>
                    <td class="num">{{ $row['bills'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No table sales in this period.</td></tr>
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
    .sky-sale-head p { margin: 4px 0 0; color: #5c6570; }
    .sky-sale-export { display: inline-flex; min-height: 32px; align-items: center; margin-left: 6px; padding: 4px 10px; border-radius: 8px; background: #0c1830; color: #fff; text-decoration: none; font-weight: 700; font-size: 13px; }
    .sky-sale-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 12px 0; }
    .sky-sale-cards div { background: #fff; border-radius: 12px; padding: 10px; }
    .sky-sale-cards span { display: block; color: #8b93a7; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .sky-sale-cards strong { display: block; margin-top: 4px; color: #0c1830; font-size: 22px; }
    .sky-sale-cards small { display: block; margin-top: 2px; color: #5c6570; }
    .sky-sale-table { width: 100%; border-collapse: collapse; background: #fff; }
    .sky-sale-table th, .sky-sale-table td { padding: 8px; text-align: left; }
    .sky-sale-table .num { text-align: right; white-space: nowrap; }
    .sky-sale-table thead th { background: #0c1830; color: #fff; }
    .sky-sale-table tbody tr:nth-child(even) { background: #f3f5f8; }
    .sky-tag { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; background: #e1b368; color: #0c1830; font-size: 11px; font-weight: 700; }
    @media (max-width: 800px) {
        .sky-sale-cards { grid-template-columns: 1fr 1fr; }
        .sky-sale-head { flex-wrap: wrap; }
    }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#table-report-menu, #table-report-{{ $range }}").addClass("active");
</script>
@endsection
