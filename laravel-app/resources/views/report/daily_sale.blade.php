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
    $monthTotal = 0;
    $daysWith = 0;
    $bestDay = 0;
    $bestAmount = 0;
    foreach ($grand_total as $dayNo => $dayTotal) {
        $dayTotal = (float) $dayTotal;
        $monthTotal += $dayTotal;
        if ($dayTotal > 0) {
            $daysWith++;
        }
        if ($dayTotal > $bestAmount) {
            $bestAmount = $dayTotal;
            $bestDay = (int) $dayNo;
        }
    }
@endphp
<section class="sky-cal">
    @include('report.partials.period_tabs', ['current' => 'daily'])
    <div class="sky-cal-card">
        <div class="sky-cal-head">
            <a href="{{ url('report/daily_sale/'.$prev_year.'/'.$prev_month) }}">Previous</a>
            <h3>{{ $monthName }}</h3>
            <a href="{{ url('report/daily_sale/'.$next_year.'/'.$next_month) }}">Next</a>
        </div>
        <div class="sky-cal-stats">
            <div><span>Month sales</span><strong>{{ number_format($monthTotal, 0, '.', ',') }}</strong></div>
            <div><span>Days with sales</span><strong>{{ $daysWith }}</strong></div>
            <div><span>Best day</span><strong>{{ $bestDay ? $bestDay.' · '.number_format($bestAmount, 0, '.', ',') : '—' }}</strong></div>
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
                        $heat = ($bestAmount > 0 && $amount > 0) ? max(0.18, $amount / $bestAmount) : 0;
                    @endphp
                    <a class="sky-cal-day {{ $isToday ? 'is-today' : '' }} {{ $amount > 0 ? 'has-sales' : '' }}" href="{{ $url }}" @if($heat > 0) style="background: rgba(225, 179, 104, {{ round($heat, 2) }});" @endif>
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
    .sky-cal { background: #f3f6fb; margin: -8px -12px 0; padding: 12px; }
    .sky-cal-card { background: #fff; border-radius: 18px; box-shadow: 0 12px 32px rgba(12, 24, 48, .08); padding: 0 0 12px; overflow: hidden; }
    .sky-cal-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 14px 16px; background: #0c1830; }
    .sky-cal-head h3 { margin: 0; color: #e1b368; font-size: 18px; font-weight: 800; letter-spacing: .04em; }
    .sky-cal-head a { color: #fff; font-weight: 800; text-decoration: none; }
    .sky-cal-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 12px; }
    .sky-cal-stats div { background: #f7f4ee; border-radius: 12px; padding: 10px 12px; }
    .sky-cal-stats span { display: block; color: #8a6a32; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .sky-cal-stats strong { display: block; margin-top: 2px; color: #0c1830; font-size: 20px; }
    .sky-cal-warehouse { display: flex; flex-direction: column; gap: 4px; max-width: 240px; margin: 0 12px 10px; color: #5c6570; font-size: 12px; font-weight: 700; }
    .sky-cal-warehouse select { min-height: 36px; border: 1px solid #d5dbe3; border-radius: 8px; padding: 4px 8px; }
    .sky-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; padding: 0 12px 4px; }
    .sky-cal-dow { color: #8a6a32; font-size: 12px; font-weight: 800; padding: 4px; }
    .sky-cal-day {
        display: flex; flex-direction: column; justify-content: space-between; min-height: 92px;
        border: 1px solid #e6e8ee; border-radius: 12px; padding: 8px; color: #0c1830; text-decoration: none; background: #fff;
    }
    .sky-cal-day.is-empty { background: #f7f8fa; border-color: transparent; }
    .sky-cal-day.has-sales { border-color: #e1b368; }
    .sky-cal-day.is-today { box-shadow: inset 0 0 0 2px #0c1830; }
    .sky-cal-day span { font-weight: 800; }
    .sky-cal-day strong { font-size: 14px; color: #0c1830; }
    .sky-cal-day:hover { transform: translateY(-1px); }
    @media (max-width: 720px) {
        .sky-cal-stats { grid-template-columns: 1fr; }
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
