<nav class="sky-period" aria-label="Daily sales">
    <a class="{{ ($current ?? '') === 'daily' ? 'on' : '' }}" href="{{ url('report/daily_sale/'.date('Y').'/'.date('m')) }}">Daily</a>
    <a class="{{ ($current ?? '') === 'monthly' ? 'on' : '' }}" href="{{ url('report/monthly_sale/'.date('Y')) }}">Monthly</a>
    <a class="{{ ($current ?? '') === 'yearly' ? 'on' : '' }}" href="{{ route('report.yearlySale') }}">Yearly</a>
</nav>
<style>
    .sky-period { display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 12px; }
    .sky-period a {
        display: inline-flex; align-items: center; min-height: 34px; padding: 4px 14px;
        border-radius: 999px; background: #f7f1e3; border: 1px solid #e1b368; color: #6b5420;
        text-decoration: none; font-weight: 800; font-size: 13px;
    }
    .sky-period a.on { background: #0c1830; border-color: #0c1830; color: #fff; }
</style>
