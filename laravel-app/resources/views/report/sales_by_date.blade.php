@extends('layout.main')
@section('content')
@php
    $ranges = [
        'today' => "Today's Sales",
        'week' => 'This Week',
        'month' => 'This Month',
        'custom' => 'Custom',
    ];
    $money = number_format((float) $total, 0, '.', ',');
@endphp
<section class="sky-sales">
    <nav class="sky-sale-ranges" aria-label="Sales report">
        @foreach ($ranges as $key => $label)
            <a class="{{ $range === $key ? 'on' : '' }}" href="{{ route('report.sales', ['range' => $key]) }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if ($range === 'custom')
        <form class="sky-sale-custom" method="get" action="{{ route('report.sales') }}">
            <input type="hidden" name="range" value="custom">
            <label>From
                <input type="date" name="start_date" value="{{ $picked ? $start_date : '' }}" required>
            </label>
            <label>To
                <input type="date" name="end_date" value="{{ $picked ? $end_date : '' }}" required>
            </label>
            <button type="submit">Show</button>
        </form>
    @endif

    <div class="sky-sale-cards">
        <div class="sky-sale-card">
            <span>Total Amount</span>
            <strong>{{ $money }}</strong>
        </div>
        <div class="sky-sale-card">
            <span>Total Sales</span>
            <strong>{{ $money }}</strong>
        </div>
    </div>

    @if ($picked)
        @php
            $exportQuery = ['range' => $range, 'start_date' => $start_date, 'end_date' => $end_date];
            $qtyTotalText = abs($qty_total - round($qty_total)) < 0.001 ? (string) (int) round($qty_total) : number_format($qty_total, 2, '.', ',');
        @endphp
        <div class="sky-sale-tools">
            <input id="sky-sale-filter" type="search" placeholder="Filter Product Name" autocomplete="off" aria-label="Filter Product Name">
            <a class="sky-sale-export" target="_blank" href="{{ route('report.sales', $exportQuery + ['print' => 'letter']) }}">Letterhead</a>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.sales', $exportQuery + ['print' => 'thermal']) }}">Thermal</a>
        </div>
        <div class="sky-sale-table-wrap">
            <table class="sky-sale-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th class="num">Sales Total (F)</th>
                        <th class="num">Quantity</th>
                    </tr>
                </thead>
                <tbody id="sky-sale-rows">
                    @forelse ($products as $row)
                        @php
                            $qty = (float) $row['qty'];
                            $qtyText = abs($qty - round($qty)) < 0.001 ? (string) (int) round($qty) : number_format($qty, 2, '.', ',');
                        @endphp
                        <tr data-name="{{ strtolower($row['name']) }}">
                            <td>{{ $row['name'] }}</td>
                            <td class="num">{{ number_format((float) $row['total'], 0, '.', '') }}</td>
                            <td class="num">{{ $qtyText }}</td>
                        </tr>
                    @empty
                        <tr class="sky-sale-empty">
                            <td colspan="3">No sales in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>Summary</th>
                        <th class="num">{{ number_format((float) $total, 0, '.', ',') }}</th>
                        <th class="num">{{ $qtyTotalText }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p id="sky-sale-none" class="sky-sale-none" hidden>No products match that name.</p>
    @else
        <p class="sky-sale-none">Select a start and end date.</p>
    @endif
</section>
<style>
    .sky-sales { background: #f4f6f8; margin: -8px -12px 0; padding: 8px 10px 16px; }
    .sky-sale-ranges { display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 8px; }
    .sky-sale-ranges a {
        display: inline-flex; align-items: center; min-height: 34px; padding: 4px 12px;
        border-radius: 999px; border: 1px solid #d5dbe3; background: #fff; color: #1a1a1a;
        text-decoration: none; font-weight: 700; font-size: 13px;
    }
    .sky-sale-ranges a.on { background: #0c1830; border-color: #0c1830; color: #fff; }
    .sky-sale-custom { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; margin: 0 0 8px; }
    .sky-sale-custom label { display: flex; flex-direction: column; gap: 2px; color: #5c6570; font-size: 12px; font-weight: 700; margin: 0; }
    .sky-sale-custom input { min-height: 36px; border: 1px solid #d5dbe3; border-radius: 8px; padding: 6px 8px; background: #fff; }
    .sky-sale-custom button { min-height: 36px; border: 0; border-radius: 8px; padding: 6px 14px; background: #0c1830; color: #fff; font-weight: 700; }
    .sky-sale-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-width: 760px; margin: 0 auto 4px; }
    .sky-sale-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05); text-align: center; padding: 8px 8px 6px; }
    .sky-sale-card span { display: block; color: #8b93a7; font-size: 13px; }
    .sky-sale-card strong { display: block; margin-top: 2px; font-size: 22px; line-height: 1.1; color: #1a1a1a; font-weight: 800; }
    .sky-sale-tools { display: flex; align-items: center; gap: 8px; }
    #sky-sale-filter {
        flex: 1 1 auto; min-width: 0; border: 0; background: transparent; color: #1a1a1a;
        font-size: 15px; padding: 6px 4px; outline: none;
    }
    #sky-sale-filter::placeholder { color: #b0b6c0; }
    .sky-sale-export {
        flex: 0 0 auto; display: inline-flex; align-items: center; min-height: 32px; padding: 4px 10px;
        border-radius: 8px; background: #0c1830; color: #fff; text-decoration: none; font-size: 13px; font-weight: 700;
    }
    .sky-sale-table-wrap { overflow-x: auto; }
    .sky-sale-table { width: 100%; border-collapse: collapse; background: #fff; }
    .sky-sale-table th, .sky-sale-table td { padding: 6px 8px; font-size: 14px; color: #1a1a1a; }
    .sky-sale-table th { font-weight: 800; background: #fff; }
    .sky-sale-table .num { text-align: right; white-space: nowrap; }
    .sky-sale-table tbody tr:nth-child(odd) { background: #fff; }
    .sky-sale-table tbody tr:nth-child(even) { background: #f3f5f8; }
    .sky-sale-table tfoot th { background: #0c1830; color: #fff; }
    .sky-sale-empty td, .sky-sale-none { color: #6b7280; padding: 8px 4px; }
    @media (max-width: 640px) {
        .sky-sale-card strong { font-size: 20px; }
        .sky-sale-table th, .sky-sale-table td { padding: 5px 6px; font-size: 13px; }
    }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#sale-report-{{ $range }}").addClass("active");
    var filter = document.getElementById('sky-sale-filter');
    if (filter) {
        filter.addEventListener('input', function () {
            var q = filter.value.trim().toLowerCase();
            var shown = 0;
            document.querySelectorAll('#sky-sale-rows tr').forEach(function (row) {
                if (row.classList.contains('sky-sale-empty')) return;
                var match = q === '' || (row.getAttribute('data-name') || '').indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) shown++;
            });
            var none = document.getElementById('sky-sale-none');
            if (none) none.hidden = q === '' || shown > 0;
        });
    }
</script>
@endsection
