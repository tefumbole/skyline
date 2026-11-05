@extends('layout.main')
@section('content')
@php
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
@endphp
<section class="sky-cal">
    @include('report.partials.period_tabs', ['current' => 'monthly'])
    <div class="sky-cal-card">
        <div class="sky-cal-head">
            <a href="{{ url('report/monthly_sale/'.($year - 1)) }}">Previous</a>
            <h3>{{ $year }}</h3>
            <a href="{{ url('report/monthly_sale/'.($year + 1)) }}">Next</a>
        </div>
        <div class="sky-month-grid">
            @foreach ($months as $index => $name)
                @php
                    $amount = (float) str_replace(',', '', (string) ($total[$index] ?? 0));
                    $start = sprintf('%04d-%02d-01', $year, $index + 1);
                    $end = date('Y-m-t', strtotime($start));
                    $url = route('report.sales', ['range' => 'custom', 'start_date' => $start, 'end_date' => $end, 'warehouse_id' => $warehouse_id]);
                @endphp
                <a class="sky-month {{ $amount > 0 ? 'has-sales' : '' }}" href="{{ $url }}">
                    <span>{{ $name }}</span>
                    <strong>{{ number_format($amount, 0, '.', ',') }}</strong>
                </a>
            @endforeach
        </div>
    </div>
</section>
<style>
    .sky-cal { background: #f4f6f8; margin: -8px -12px 0; padding: 12px; }
    .sky-cal-card { background: #fff; border-radius: 14px; padding: 12px; }
    .sky-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .sky-cal-head h3 { margin: 0; color: #0c1830; font-weight: 800; }
    .sky-cal-head a { color: #0c1830; font-weight: 700; text-decoration: none; }
    .sky-month-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .sky-month {
        display: flex; flex-direction: column; gap: 4px; min-height: 78px; padding: 12px;
        border: 1px solid #e6e8ee; border-radius: 12px; color: #0c1830; text-decoration: none; background: #fff;
    }
    .sky-month.has-sales { background: #f7f1e3; border-color: #e1b368; }
    .sky-month span { font-weight: 800; }
    .sky-month strong { font-size: 20px; }
    @media (max-width: 720px) { .sky-month-grid { grid-template-columns: 1fr; } }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#daily-sale-report-menu, #daily-sale-monthly").addClass("active");
</script>
@endsection
