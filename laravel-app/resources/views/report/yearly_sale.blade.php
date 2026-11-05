@extends('layout.main')
@section('content')
<section class="sky-cal">
    @include('report.partials.period_tabs', ['current' => 'yearly'])
    <div class="sky-cal-card">
        <h3>Yearly sales</h3>
        <div class="sky-month-grid">
            @foreach ($rows as $row)
                <a class="sky-month {{ $row['total'] > 0 ? 'has-sales' : '' }}" href="{{ $row['url'] }}">
                    <span>{{ $row['year'] }}</span>
                    <strong>{{ number_format((float) $row['total'], 0, '.', ',') }}</strong>
                    <small>Cash {{ number_format((float) $row['cash'], 0, '.', ',') }} · MoMo {{ number_format((float) $row['momo'], 0, '.', ',') }} · Due {{ number_format((float) $row['due'], 0, '.', ',') }}</small>
                </a>
            @endforeach
        </div>
    </div>
</section>
<style>
    .sky-cal { background: #f4f6f8; margin: -8px -12px 0; padding: 12px; }
    .sky-cal-card { background: #fff; border-radius: 14px; padding: 12px; }
    .sky-cal-card h3 { margin: 0 0 12px; color: #0c1830; font-weight: 800; }
    .sky-month-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .sky-month {
        display: flex; flex-direction: column; gap: 4px; padding: 12px; border-radius: 12px;
        border: 1px solid #e6e8ee; color: #0c1830; text-decoration: none; background: #fff;
    }
    .sky-month.has-sales { background: #f7f1e3; border-color: #e1b368; }
    .sky-month span, .sky-month strong { font-weight: 800; }
    .sky-month strong { font-size: 22px; }
    .sky-month small { color: #5c6570; }
    @media (max-width: 720px) { .sky-month-grid { grid-template-columns: 1fr; } }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#daily-sale-report-menu, #daily-sale-yearly").addClass("active");
</script>
@endsection
