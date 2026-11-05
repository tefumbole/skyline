@extends('layout.main')
@section('content')
@php
    $cells = [];
    for ($blank = 1; $blank < (int) $start_day; $blank++) {
        $cells[] = null;
    }
    for ($day = 1; $day <= (int) $number_of_day; $day++) {
        $cells[] = $day;
    }
    while (count($cells) % 7 !== 0) {
        $cells[] = null;
    }
    $todayKey = date('Y-n-j');
    $monthName = date('F Y', strtotime($year.'-'.$month.'-01'));
@endphp
<section class="sky-cal">
    @include('report.partials.period_tabs', ['current' => 'daily'])
    <div class="sky-cal-card">
        <div class="sky-cal-head">
            <a href="{{ url('report/daily_sale/'.$prev_year.'/'.$prev_month) }}">Previous</a>
            <h3>{{ $monthName }}</h3>
            <a href="{{ url('report/daily_sale/'.$next_year.'/'.$next_month) }}">Next</a>
        </div>
        {{ Form::open(['route' => ['report.dailySaleByWarehouse', $year, $month], 'method' => 'post', 'id' => 'report-form']) }}
        <input type="hidden" name="warehouse_id_hidden" value="{{ $warehouse_id }}">
        <label class="sky-cal-warehouse">
            Warehouse
            <select id="warehouse_id" name="warehouse_id">
                <option value="0">{{ trans('file.All Warehouse') }}</option>
                @foreach ($lims_warehouse_list as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </label>
        {{ Form::close() }}
        <div class="sky-cal-grid">
            @foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $name)
                <div class="sky-cal-dow">{{ $name }}</div>
            @endforeach
            @foreach ($cells as $day)
                @if ($day === null)
                    <div class="sky-cal-day is-empty"></div>
                @else
                    @php
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $url = route('report.sales', ['range' => 'custom', 'start_date' => $date, 'end_date' => $date, 'warehouse_id' => $warehouse_id]);
                        $amount = (float) ($grand_total[$day] ?? 0);
                        $isToday = $year.'-'.(int) $month.'-'.$day === $todayKey;
                    @endphp
                    <a class="sky-cal-day {{ $isToday ? 'is-today' : '' }} {{ $amount > 0 ? 'has-sales' : '' }}" href="{{ $url }}">
                        <span>{{ $day }}</span>
                        @if ($amount > 0)
                            <strong>{{ number_format($amount, 0, '.', ',') }}</strong>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</section>
<style>
    .sky-cal { background: #f4f6f8; margin: -8px -12px 0; padding: 12px; }
    .sky-cal-card { background: #fff; border-radius: 14px; padding: 12px; }
    .sky-cal-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .sky-cal-head h3 { margin: 0; color: #0c1830; font-weight: 800; }
    .sky-cal-head a { color: #0c1830; font-weight: 700; text-decoration: none; }
    .sky-cal-warehouse { display: flex; flex-direction: column; gap: 4px; max-width: 240px; margin: 10px 0; color: #5c6570; font-size: 12px; font-weight: 700; }
    .sky-cal-warehouse select { min-height: 36px; border: 1px solid #d5dbe3; border-radius: 8px; padding: 4px 8px; }
    .sky-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
    .sky-cal-dow { color: #8b93a7; font-size: 12px; font-weight: 800; padding: 4px; }
    .sky-cal-day {
        display: flex; flex-direction: column; justify-content: space-between; min-height: 78px;
        border: 1px solid #e6e8ee; border-radius: 10px; padding: 8px; color: #0c1830; text-decoration: none; background: #fff;
    }
    .sky-cal-day.is-empty { background: #f7f8fa; border-color: transparent; }
    .sky-cal-day.has-sales { background: #f7f1e3; border-color: #e1b368; }
    .sky-cal-day.is-today { box-shadow: inset 0 0 0 2px #0c1830; }
    .sky-cal-day span { font-weight: 800; }
    .sky-cal-day strong { font-size: 13px; }
    @media (max-width: 720px) {
        .sky-cal-dow { font-size: 10px; }
        .sky-cal-day { min-height: 58px; padding: 4px; }
        .sky-cal-day strong { font-size: 11px; }
    }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#daily-sale-report-menu, #daily-sale-daily").addClass("active");
    $('#warehouse_id').val($('input[name="warehouse_id_hidden"]').val());
    $('#warehouse_id').on('change', function () {
        $('#report-form').submit();
    });
</script>
@endsection
