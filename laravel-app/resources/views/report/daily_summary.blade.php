@extends('layout.main')
@section('content')
<section class="sky-days">
    <div class="sky-days-card">
        <div class="sky-days-head">
            <div>
                <h3>Daily sales</h3>
                <p>{{ $title }}</p>
            </div>
            <div class="sky-days-nav">
                <a href="{{ $prevUrl }}">Previous</a>
                @if($nextUrl)
                    <a href="{{ $nextUrl }}">Next</a>
                @endif
            </div>
        </div>
        <div class="sky-days-table">
            <table>
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Sales</th>
                        <th>Cash</th>
                        <th>MoMo</th>
                        <th>Due</th>
                        <th>Customers</th>
                        <th>Orders</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($days as $day)
                        <tr class="{{ $day['orders'] > 0 ? 'has-sales' : '' }}">
                            <td><a href="{{ $day['url'] }}">{{ $day['label'] }}</a></td>
                            <td>{{ number_format((float) $day['sales'], 0, '.', ',') }}</td>
                            <td>{{ number_format((float) $day['cash'], 0, '.', ',') }}</td>
                            <td>{{ number_format((float) $day['momo'], 0, '.', ',') }}</td>
                            <td>{{ number_format((float) $day['due'], 0, '.', ',') }}</td>
                            <td>{{ (int) $day['customers'] }}</td>
                            <td>{{ (int) $day['orders'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
<style>
    .sky-days { background: #f4f6f8; margin: -8px -12px 0; padding: 12px; }
    .sky-days-card { background: #fff; border-radius: 14px; padding: 12px; }
    .sky-days-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
    .sky-days-card h3 { margin: 0; color: #0c1830; font-weight: 800; }
    .sky-days-card p { margin: 4px 0 0; color: #5c6570; }
    .sky-days-nav { display: flex; gap: 8px; }
    .sky-days-nav a {
        background: #0c1830; color: #fff; border-radius: 999px; padding: 8px 14px;
        font-weight: 700; text-decoration: none;
    }
    .sky-days-table { overflow-x: auto; margin-top: 12px; }
    .sky-days table { width: 100%; border-collapse: collapse; color: #0c1830; }
    .sky-days th, .sky-days td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #e6e8ee; white-space: nowrap; }
    .sky-days th { font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: #8a6230; }
    .sky-days td a { color: #0c1830; font-weight: 800; text-decoration: none; }
    .sky-days tr.has-sales td { background: #f7f1e3; }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#daily-sale-report-menu, #daily-sale-summary").addClass("active");
</script>
@endsection
