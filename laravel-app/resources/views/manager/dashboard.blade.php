@extends('layout.main')
@section('content')
@php
    $charts = $charts ?? [];
    $today = $today ?? ['date' => '', 'sales' => 0, 'customers' => 0, 'momo' => 0, 'orange' => 0, 'mobile' => 0];
    $roleName = Auth::user()->roleName() ?: 'Manager';
    $money = function ($amount) {
        return number_format((float) $amount, 0, '.', ',');
    };
@endphp
<div class="beyond-dashboard">
    <div class="beyond-dashboard-hero">
        <div>
            <h1>Dashboard</h1>
            <p class="welcome-line">
                Welcome back, {{ Auth::user()->name }}
                <span class="beyond-role-badge">{{ $roleName }}</span>
            </p>
        </div>
    </div>

    <div class="beyond-stat-grid">
        <div class="beyond-stat-card" style="cursor: default;">
            <div>
                <div class="label">Today's sales</div>
                <div class="value">{{ $money($today['sales']) }}</div>
                <div class="hint">FCFA · {{ $today['date'] }}</div>
            </div>
        </div>
        <div class="beyond-stat-card" style="cursor: default;">
            <div>
                <div class="label">Customers served</div>
                <div class="value">{{ (int) $today['customers'] }}</div>
                <div class="hint">Bills opened today</div>
            </div>
        </div>
    </div>

    <div class="beyond-chart-panel" style="margin-bottom: 16px;">
        <h5>Payments today</h5>
        <div class="beyond-stat-grid" style="margin: 12px 0 0;">
            <div class="beyond-stat-card" style="cursor: default;">
                <div>
                    <div class="label">MoMo</div>
                    <div class="value">{{ $money($today['momo']) }}</div>
                    <div class="hint">MTN Mobile Money, FCFA</div>
                </div>
            </div>
            <div class="beyond-stat-card" style="cursor: default;">
                <div>
                    <div class="label">Orange Money (OM)</div>
                    <div class="value">{{ $money($today['orange']) }}</div>
                    <div class="hint">Orange, FCFA</div>
                </div>
            </div>
            @if((float) $today['mobile'] > 0)
                <div class="beyond-stat-card" style="cursor: default;">
                    <div>
                        <div class="label">MoMo / Orange</div>
                        <div class="value">{{ $money($today['mobile']) }}</div>
                        <div class="hint">Network was not on the bill, FCFA</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if(count($charts) === 0)
        <div class="beyond-chart-panel">
            <h5>No charts yet</h5>
            <p class="text-muted mb-0">This account has no menus that can be drawn as a graph.</p>
        </div>
    @else
        <div class="beyond-chart-grid">
            @foreach($charts as $chart)
                <div class="beyond-chart-panel">
                    <h5>{{ $chart['title'] }}</h5>
                    <div class="beyond-chart-canvas-wrap {{ $chart['type'] === 'doughnut' ? 'is-sm' : '' }}">
                        <canvas id="{{ $chart['id'] }}"></canvas>
                    </div>
                    <p class="text-muted small mb-0 mt-2">{{ $chart['hint'] }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    var charts = @json($charts);
    var palette = ['#0c1830', '#e1b368', '#7b61ff', '#f59e0b', '#14b8a6', '#e11d48', '#64748b', '#0b3f90'];
    charts.forEach(function (chart) {
        var el = document.getElementById(chart.id);
        if (!el) return;
        var labels = chart.labels || [];
        var values = (chart.values || []).map(function (value) { return Number(value) || 0; });
        var sum = values.reduce(function (total, value) { return total + value; }, 0);
        if (chart.type === 'doughnut' && sum === 0) {
            labels = ['None yet'];
            values = [1];
        }
        var colors = [];
        labels.forEach(function (_, index) {
            colors.push((chart.colors && chart.colors[index]) || palette[index % palette.length]);
        });
        if (chart.type === 'doughnut' && sum === 0) {
            colors = ['#e3e9f4'];
        }
        new Chart(el, {
            type: chart.type === 'doughnut' ? 'doughnut' : 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: chart.type === 'doughnut' && sum > 0 },
                tooltips: {
                    callbacks: {
                        label: function (item, data) {
                            var value = data.datasets[0].data[item.index];
                            var name = data.labels[item.index] || '';
                            if (chart.money) {
                                return name + ': ' + Number(value).toLocaleString() + ' FCFA';
                            }
                            return name + ': ' + value;
                        }
                    }
                },
                scales: chart.type === 'doughnut' ? {} : {
                    yAxes: [{ ticks: { beginAtZero: true }, gridLines: { color: '#f1f5f9' } }],
                    xAxes: [{ gridLines: { display: false }, barPercentage: 0.62 }]
                }
            }
        });
    });
})();
</script>
@endsection
