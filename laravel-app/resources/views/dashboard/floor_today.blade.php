@php
    $floor = $floor ?? \App\Support\FloorDashboard::blank();
    $sales = (float) ($floor['sales'] ?? 0);
    $cash = (float) ($floor['cash'] ?? 0);
    $momo = (float) ($floor['momo'] ?? 0);
    $due = (float) ($floor['due'] ?? 0);
    $paySum = $cash + $momo + $due;
    $momoPct = $paySum > 0 ? round($momo / $paySum * 100, 2) : 0;
    $cashPct = $paySum > 0 ? round($cash / $paySum * 100, 2) : 0;
    $duePct = $paySum > 0 ? max(0, round(100 - $momoPct - $cashPct, 2)) : 0;
    $staff = $floor['staff'] ?? [];
    $peak = 1;
    foreach ($staff as $person) {
        $peak = max($peak, (int) $person['waiting'], (int) $person['placed'], (int) $person['delivered']);
    }
    $lanes = [
        'waiting' => ['label' => 'Waiting', 'hint' => 'Still with the person who placed it'],
        'placed' => ['label' => 'Placed', 'hint' => 'Orders opened today'],
        'delivered' => ['label' => 'Delivered', 'hint' => 'Carried out today'],
    ];
    $money = function ($amount) {
        return number_format((float) $amount, 0, '.', ',');
    };
@endphp
<section class="floor-board">
    <div class="floor-hero">
        <div class="floor-hero-top">
            <div>
                <p class="floor-kicker">Today on the floor</p>
                <h2>{{ $floor['date'] ?? '' }}</h2>
            </div>
            <a class="floor-report" href="{{ route('report.sales', ['range' => 'today']) }}">Open sales report</a>
        </div>

        <a class="floor-sales" href="{{ route('report.sales', ['range' => 'today']) }}">
            <span>Total sales</span>
            <strong>{{ $money($sales) }}</strong>
            <em>FCFA · bills opened today</em>
        </a>

        <div class="floor-split" aria-hidden="true">
            <i class="is-momo" style="width: {{ $momoPct }}%"></i>
            <i class="is-cash" style="width: {{ $cashPct }}%"></i>
            <i class="is-due" style="width: {{ $duePct }}%"></i>
        </div>

        <div class="floor-pay">
            <div class="floor-pay-card is-momo">
                <span>MoMo</span>
                <strong>{{ $money($momo) }}</strong>
            </div>
            <div class="floor-pay-card is-cash">
                <span>Cash</span>
                <strong>{{ $money($cash) }}</strong>
            </div>
            <div class="floor-pay-card is-due">
                <span>Due</span>
                <strong>{{ $money($due) }}</strong>
            </div>
        </div>

        <div class="floor-counts">
            <a href="{{ route('report.customers', ['range' => 'today']) }}">
                <span>Customers served</span>
                <strong>{{ number_format((int) ($floor['customers'] ?? 0)) }}</strong>
                <em>Distinct customers on today’s bills</em>
            </a>
            <a href="{{ route('restaurant.orders', ['lane' => 'restaurant']) }}">
                <span>Orders</span>
                <strong>{{ number_format((int) ($floor['orders'] ?? 0)) }}</strong>
                <em>Restaurant orders opened today</em>
            </a>
        </div>
    </div>

    <div class="floor-staff">
        <div class="floor-staff-head">
            <div>
                <h3>Staff by orders</h3>
                <p>Waiting, placed, and delivered</p>
            </div>
            <div class="floor-legend">
                <span><i class="is-waiting"></i> Waiting</span>
                <span><i class="is-placed"></i> Placed</span>
                <span><i class="is-delivered"></i> Delivered</span>
            </div>
        </div>

        <div class="floor-staff-layout">
            <div class="floor-chart-panel">
                @if(count($staff))
                    <div class="floor-chart-wrap">
                        <canvas id="floor-staff-chart"></canvas>
                    </div>
                @else
                    <div class="floor-empty">No staff orders yet today.</div>
                @endif
            </div>
            <div class="floor-roster">
                @forelse($staff as $person)
                    <article>
                        <h4>{{ $person['name'] }}</h4>
                        @foreach(['waiting' => 'Waiting', 'placed' => 'Placed', 'delivered' => 'Delivered'] as $key => $label)
                            <div class="floor-meter">
                                <span>{{ $label }}</span>
                                <b><i class="is-{{ $key }}" style="width: {{ $peak > 0 ? round(((int) $person[$key]) / $peak * 100, 2) : 0 }}%"></i></b>
                                <em>{{ (int) $person[$key] }}</em>
                            </div>
                        @endforeach
                    </article>
                @empty
                    <div class="floor-empty">Orders will appear here as the floor starts taking them.</div>
                @endforelse
            </div>
        </div>

        <div class="floor-lanes">
            @foreach($lanes as $key => $lane)
                @php
                    $ranked = $staff;
                    usort($ranked, function ($a, $b) use ($key) {
                        if ((int) $a[$key] === (int) $b[$key]) {
                            return strcasecmp($a['name'], $b['name']);
                        }
                        return (int) $a[$key] < (int) $b[$key] ? 1 : -1;
                    });
                    $ranked = array_values(array_filter($ranked, function ($row) use ($key) {
                        return (int) $row[$key] > 0;
                    }));
                    $top = count($ranked) ? (int) $ranked[0][$key] : 1;
                @endphp
                <div class="floor-lane is-{{ $key }}">
                    <header>
                        <h4>{{ $lane['label'] }}</h4>
                        <p>{{ $lane['hint'] }}</p>
                    </header>
                    @forelse($ranked as $row)
                        <div class="floor-lane-row">
                            <span>{{ $row['name'] }}</span>
                            <b><i style="width: {{ round(((int) $row[$key]) / $top * 100, 2) }}%"></i></b>
                            <em>{{ (int) $row[$key] }}</em>
                        </div>
                    @empty
                        <div class="floor-lane-empty">None yet</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
    <script type="application/json" id="floor-staff-data">@json($staff)</script>
</section>
<style>
    .floor-board { margin: 0 0 28px; }
    .floor-hero {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(900px 280px at 100% -10%, rgba(225, 179, 104, 0.28), transparent 55%),
            radial-gradient(700px 240px at -10% 120%, rgba(225, 179, 104, 0.16), transparent 50%),
            #0c1830;
        color: #fff;
        border-radius: 22px;
        padding: 26px 26px 22px;
        box-shadow: 0 22px 50px rgba(12, 24, 48, 0.22);
    }
    .floor-hero-top, .floor-staff-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
    }
    .floor-kicker {
        margin: 0 0 4px;
        color: #e1b368;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }
    .floor-hero h2 { margin: 0; font-size: 28px; font-weight: 800; letter-spacing: -0.03em; }
    .floor-report {
        display: inline-flex;
        align-items: center;
        min-height: 40px;
        padding: 0 16px;
        border-radius: 999px;
        background: rgba(225, 179, 104, 0.16);
        color: #e1b368 !important;
        font-weight: 800;
        text-decoration: none !important;
        border: 1px solid rgba(225, 179, 104, 0.45);
    }
    .floor-report:hover { background: #e1b368; color: #0c1830 !important; }
    .floor-sales {
        display: block;
        margin-top: 22px;
        text-decoration: none !important;
        color: inherit !important;
    }
    .floor-sales span, .floor-pay-card span, .floor-counts span {
        display: block;
        color: rgba(255,255,255,0.72);
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .floor-sales strong {
        display: block;
        margin: 4px 0 2px;
        font-size: 68px;
        line-height: 0.95;
        font-weight: 800;
        letter-spacing: -0.045em;
        color: #e1b368;
    }
    .floor-sales em, .floor-counts em {
        font-style: normal;
        color: rgba(255,255,255,0.62);
        font-size: 13px;
    }
    .floor-split {
        display: flex;
        height: 10px;
        margin: 22px 0 14px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(255,255,255,0.08);
    }
    .floor-split i { display: block; height: 100%; }
    .floor-split .is-momo { background: #e1b368; }
    .floor-split .is-cash { background: #f7f4ee; }
    .floor-split .is-due { background: #c9842a; }
    .floor-legend .is-waiting, .floor-meter .is-waiting { background: #c9842a; }
    .floor-legend .is-placed, .floor-meter .is-placed { background: #0c1830; }
    .floor-legend .is-delivered, .floor-meter .is-delivered { background: #e1b368; }
    .floor-pay, .floor-counts, .floor-lanes {
        display: grid;
        gap: 12px;
    }
    .floor-pay { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .floor-pay-card, .floor-counts a {
        border-radius: 16px;
        padding: 14px 16px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.08);
    }
    .floor-pay-card strong, .floor-counts strong {
        display: block;
        margin-top: 4px;
        font-size: 30px;
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: -0.03em;
    }
    .floor-pay-card.is-momo strong { color: #e1b368; }
    .floor-pay-card.is-due strong { color: #f0c27a; }
    .floor-counts { grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 12px; }
    .floor-counts a { text-decoration: none !important; color: inherit !important; }
    .floor-counts a:hover { border-color: rgba(225, 179, 104, 0.7); }
    .floor-counts strong { color: #fff; font-size: 40px; }
    .floor-staff {
        margin-top: 16px;
        background: #fff;
        border: 1px solid #e3e9f4;
        border-radius: 22px;
        padding: 22px;
        box-shadow: 0 12px 32px rgba(15, 35, 80, 0.05);
    }
    .floor-staff h3 { margin: 0; color: #0c1830; font-size: 22px; font-weight: 800; }
    .floor-staff-head p, .floor-lane header p { margin: 4px 0 0; color: #64748b; font-size: 13px; }
    .floor-legend { display: flex; flex-wrap: wrap; gap: 12px; color: #334155; font-size: 13px; font-weight: 700; }
    .floor-legend span, .floor-meter { display: flex; align-items: center; gap: 8px; }
    .floor-legend i { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
    .floor-staff-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.8fr);
        gap: 18px;
        margin-top: 18px;
    }
    .floor-chart-wrap { height: 320px; position: relative; }
    .floor-roster { display: flex; flex-direction: column; gap: 12px; max-height: 320px; overflow: auto; }
    .floor-roster article {
        border: 1px solid #e8edf5;
        border-radius: 14px;
        padding: 12px 12px 8px;
        background: #f8fafc;
    }
    .floor-roster h4 { margin: 0 0 8px; color: #0c1830; font-size: 15px; font-weight: 800; }
    .floor-meter { margin-bottom: 6px; font-size: 12px; color: #64748b; font-weight: 700; }
    .floor-meter span { width: 72px; }
    .floor-meter b {
        flex: 1;
        height: 7px;
        border-radius: 999px;
        background: #e8edf5;
        overflow: hidden;
        display: block;
    }
    .floor-meter i { display: block; height: 100%; border-radius: 999px; }
    .floor-meter em { width: 22px; text-align: right; font-style: normal; color: #0c1830; }
    .floor-lanes { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 18px; }
    .floor-lane {
        border-radius: 16px;
        padding: 14px;
        background: #f8fafc;
        border: 1px solid #e8edf5;
        min-height: 180px;
    }
    .floor-lane header h4 { margin: 0; color: #0c1830; font-size: 16px; font-weight: 800; }
    .floor-lane-row { display: grid; grid-template-columns: minmax(0, 1fr) 72px 28px; gap: 8px; align-items: center; margin-top: 10px; }
    .floor-lane-row span { color: #0c1830; font-weight: 700; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .floor-lane-row b { height: 8px; border-radius: 999px; background: #e8edf5; overflow: hidden; display: block; }
    .floor-lane-row i { display: block; height: 100%; border-radius: 999px; background: #0c1830; }
    .floor-lane.is-waiting .floor-lane-row i { background: #c9842a; }
    .floor-lane.is-delivered .floor-lane-row i { background: #e1b368; }
    .floor-lane-row em { font-style: normal; font-weight: 800; color: #0c1830; text-align: right; }
    .floor-lane-empty, .floor-empty { color: #64748b; padding: 18px 0; }
    @media (max-width: 991px) {
        .floor-sales strong { font-size: 52px; }
        .floor-staff-layout, .floor-lanes, .floor-pay { grid-template-columns: 1fr; }
        .floor-roster { max-height: none; }
    }
    @media (max-width: 640px) {
        .floor-hero { padding: 18px; }
        .floor-hero-top, .floor-staff-head { flex-direction: column; }
        .floor-sales strong { font-size: 44px; }
        .floor-counts { grid-template-columns: 1fr; }
    }
</style>
