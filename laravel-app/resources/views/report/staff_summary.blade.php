@extends('layout.main')
@section('content')
@php
    $ranges = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];
@endphp
<section class="sky-sales">
    <nav class="sky-sale-ranges" aria-label="Staff report">
        @foreach ($ranges as $key => $label)
            <a class="{{ $range === $key ? 'on' : '' }}" href="{{ route('report.staff', ['range' => $key]) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="sky-sale-head">
        <h3>{{ $heading }}</h3>
        <div>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.staff', ['range' => $range, 'print' => 'letter']) }}">Letterhead</a>
            <a class="sky-sale-export" target="_blank" href="{{ route('report.staff', ['range' => $range, 'print' => 'thermal']) }}">Thermal</a>
        </div>
    </div>
    <p class="sky-period-line">{{ $period }}</p>
    <div class="sky-role-cards">
        @foreach ($sections as $section)
            @php
                $orders = 0;
                $worth = 0;
                $served = 0;
                foreach ($section['people'] as $person) {
                    $orders += $person['orders'];
                    $worth += $person['worth'];
                    $served += $person['served'];
                }
            @endphp
            <div>
                <span>{{ $section['title'] }}</span>
                <strong>{{ $orders }} orders</strong>
                <small>
                    @if ($section['show_worth'])
                        {{ number_format($worth, 0, '.', ',') }} · {{ $served }} customers
                    @else
                        {{ $served }} customers served
                    @endif
                </small>
            </div>
        @endforeach
    </div>
    @foreach ($sections as $section)
        <h4>{{ $section['title'] }}</h4>
        <table class="sky-sale-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="num">{{ $section['show_worth'] ? 'Orders' : 'Orders prepared' }}</th>
                    @if ($section['show_worth'])
                        <th class="num">Worth</th>
                    @endif
                    <th class="num">Customers served</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($section['people'] as $person)
                    <tr>
                        <td>{{ $person['name'] }}</td>
                        <td class="num">{{ $person['orders'] }}</td>
                        @if ($section['show_worth'])
                            <td class="num">{{ number_format((float) $person['worth'], 0, '.', ',') }}</td>
                        @endif
                        <td class="num">{{ $person['served'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No {{ strtolower($section['title']) }} in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</section>
<style>
    .sky-sales { background: #f4f6f8; margin: -8px -12px 0; padding: 12px; }
    .sky-sale-ranges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
    .sky-sale-ranges a { display: inline-flex; min-height: 34px; align-items: center; padding: 4px 12px; border-radius: 999px; background: #fff; border: 1px solid #d5dbe3; color: #1a1a1a; text-decoration: none; font-weight: 700; }
    .sky-sale-ranges a.on { background: #0c1830; color: #fff; border-color: #0c1830; }
    .sky-sale-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .sky-sale-head h3, .sky-sales h4 { color: #0c1830; font-weight: 800; }
    .sky-sale-export { display: inline-flex; min-height: 32px; align-items: center; margin-left: 6px; padding: 4px 10px; border-radius: 8px; background: #0c1830; color: #fff; text-decoration: none; font-weight: 700; font-size: 13px; }
    .sky-period-line { color: #5c6570; }
    .sky-role-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px; }
    .sky-role-cards div { background: #fff; border-radius: 12px; padding: 10px; }
    .sky-role-cards span { display: block; color: #8b93a7; }
    .sky-role-cards strong { display: block; font-size: 20px; color: #0c1830; }
    .sky-role-cards small { color: #5c6570; }
    .sky-sale-table { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 16px; }
    .sky-sale-table th, .sky-sale-table td { padding: 8px; text-align: left; }
    .sky-sale-table .num { text-align: right; }
    .sky-sale-table thead th { background: #0c1830; color: #fff; }
    .sky-sale-table tbody tr:nth-child(even) { background: #f3f5f8; }
    @media (max-width: 720px) { .sky-role-cards { grid-template-columns: 1fr; } }
</style>
<script>
    $("ul#report").siblings('a').attr('aria-expanded', 'true');
    $("ul#report").addClass("show");
    $("#user-report-menu").addClass("active");
</script>
@endsection
