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
            <div class="floor-links">
                <a class="floor-report" href="{{ route('report.sales', ['range' => 'today']) }}">Open sales report</a>
                <a class="floor-report" href="{{ route('report.dailySummary') }}">Daily sales</a>
            </div>
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
            <div class="floor-pay-card is-cash">
                <span>Cash</span>
                <strong>{{ $money($cash) }}</strong>
            </div>
            <div class="floor-pay-card is-momo">
                <span>MoMo</span>
                <strong>{{ $money($momo) }}</strong>
            </div>
            <div class="floor-pay-card is-due">
                <span>Due</span>
                <strong>{{ $money($due) }}</strong>
            </div>
        </div>
        <div class="floor-counts">
            <div class="floor-pay-card">
                <span>Customers</span>
                <strong>{{ (int) ($floor['customers'] ?? 0) }}</strong>
            </div>
            <div class="floor-pay-card">
                <span>Orders</span>
                <strong>{{ (int) ($floor['orders'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    @php
        $periods = $salesPeriods ?? ['week' => null, 'month' => null, 'custom' => null, 'start_date' => '', 'end_date' => ''];
        $periodCards = [
            'week' => ['label' => 'This week', 'range' => 'week'],
            'month' => ['label' => 'This month', 'range' => 'month'],
        ];
    @endphp
    <div class="floor-periods">
        @foreach($periodCards as $key => $card)
            @php $row = isset($periods[$key]) ? $periods[$key] : ['sales' => 0, 'cash' => 0, 'momo' => 0, 'due' => 0, 'customers' => 0, 'orders' => 0, 'span' => '']; @endphp
            <a class="floor-period" href="{{ route('report.sales', ['range' => $card['range']]) }}">
                <span>{{ $card['label'] }}</span>
                <strong>{{ $money($row['sales']) }}</strong>
                <em>{{ $row['span'] ?? '' }}</em>
                <ul>
                    <li><b>Cash</b> {{ $money($row['cash']) }}</li>
                    <li><b>MoMo</b> {{ $money($row['momo']) }}</li>
                    <li><b>Due</b> {{ $money($row['due']) }}</li>
                    <li><b>Customers</b> {{ (int) ($row['customers'] ?? 0) }}</li>
                    <li><b>Orders</b> {{ (int) ($row['orders'] ?? 0) }}</li>
                </ul>
            </a>
        @endforeach
        <div class="floor-period is-custom">
            <span>Custom</span>
            @if(!empty($periods['custom']))
                <strong>{{ $money($periods['custom']['sales']) }}</strong>
                <em>{{ $periods['custom']['span'] ?? '' }}</em>
                <ul>
                    <li><b>Cash</b> {{ $money($periods['custom']['cash']) }}</li>
                    <li><b>MoMo</b> {{ $money($periods['custom']['momo']) }}</li>
                    <li><b>Due</b> {{ $money($periods['custom']['due']) }}</li>
                    <li><b>Customers</b> {{ (int) ($periods['custom']['customers'] ?? 0) }}</li>
                    <li><b>Orders</b> {{ (int) ($periods['custom']['orders'] ?? 0) }}</li>
                </ul>
            @else
                <strong>—</strong>
                <em>Pick a start and an end date</em>
            @endif
            <form method="get" action="{{ url('/admin') }}">
                <label>From <input type="date" name="start_date" value="{{ $periods['start_date'] ?? '' }}" required></label>
                <label>To <input type="date" name="end_date" value="{{ $periods['end_date'] ?? '' }}" required></label>
                <button type="submit">Show</button>
            </form>
        </div>
    </div>
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
    .floor-links { display: flex; flex-wrap: wrap; gap: 8px; }
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
    .floor-pay-card.is-cash strong { color: #f7f4ee; }
    .floor-pay-card.is-momo strong { color: #e1b368; }
    .floor-pay-card.is-due strong { color: #f0c27a; }
    .floor-periods {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 14px;
    }
    .floor-period {
        display: block;
        background: #fff;
        color: #0c1830;
        border: 1px solid #e3e9f4;
        border-radius: 18px;
        padding: 16px 16px 14px;
        text-decoration: none !important;
        box-shadow: 0 12px 32px rgba(12, 24, 48, 0.05);
    }
    .floor-period:hover { border-color: #e1b368; color: #0c1830; }
    .floor-period span {
        display: block;
        color: #8a6230;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }
    .floor-period strong {
        display: block;
        margin-top: 6px;
        font-size: 32px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #0c1830;
    }
    .floor-period em {
        display: block;
        margin-top: 4px;
        color: #5c6b82;
        font-style: normal;
        font-size: 13px;
    }
    .floor-period ul {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin: 12px 0 0;
        padding: 0;
        list-style: none;
    }
    .floor-period li {
        background: #f3f6fb;
        border-radius: 10px;
        padding: 8px;
        font-size: 14px;
        font-weight: 800;
        color: #0c1830;
    }
    .floor-period li b {
        display: block;
        margin-bottom: 2px;
        color: #5c6b82;
        font-size: 11px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .floor-period form {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: end;
        margin-top: 12px;
    }
    .floor-period label {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin: 0;
        color: #5c6b82;
        font-size: 12px;
        font-weight: 700;
    }
    .floor-period input[type="date"] {
        height: 38px;
        border: 1px solid #d7deea;
        border-radius: 10px;
        padding: 0 8px;
        color: #0c1830;
        background: #fff;
    }
    .floor-period button {
        height: 38px;
        border: 0;
        border-radius: 999px;
        padding: 0 16px;
        background: #0c1830;
        color: #e1b368;
        font-weight: 800;
    }
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
        .floor-staff-layout, .floor-lanes, .floor-pay, .floor-periods { grid-template-columns: 1fr; }
        .floor-roster { max-height: none; }
    }
    @media (max-width: 640px) {
        .floor-hero { padding: 18px; }
        .floor-hero-top, .floor-staff-head { flex-direction: column; }
        .floor-sales strong { font-size: 44px; }
        .floor-counts { grid-template-columns: 1fr; }
    }
</style>
