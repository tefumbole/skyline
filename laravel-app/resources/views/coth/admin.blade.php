@extends('layout.main')
@section('content')
@php
    $tab = request()->query('tab', 'tables');
    $prefixCounts = [];
    foreach ($tables as $row) {
        if (preg_match('/^([A-Za-z0-9]+)-/', (string) $row->code, $match)) {
            $sid = $row->coth_section_id;
            $bit = strtoupper($match[1]);
            if (! isset($prefixCounts[$sid][$bit])) {
                $prefixCounts[$sid][$bit] = 0;
            }
            $prefixCounts[$sid][$bit]++;
        }
    }
    $prefixes = [];
    foreach ($prefixCounts as $sid => $counts) {
        arsort($counts);
        $prefixes[$sid] = array_key_first($counts);
    }
    $tabs = [
        'tables' => 'Tables',
        'list' => 'List',
        'reservations' => 'Reservations',
        'availability' => 'Availability',
        'chairs' => 'Extra chairs',
        'rules' => 'Rules',
        'audit' => 'Audit',
        'locations' => 'Locations',
    ];
    if (! isset($tabs[$tab])) {
        $tab = 'tables';
    }
@endphp
<style>
    .cothx {
        background: #fff; color: #1f2a44; border: 1px solid #e6ebf3; border-radius: 16px;
        box-shadow: 0 8px 24px rgba(12, 24, 48, 0.06); padding: 24px; margin: 0 0 24px;
    }
    .cothx h1 { font-weight: 600; font-size: 28px; margin: 0; color: #0c1830; }
    .cothx a { color: #0c1830; }
    .cothx-kicker { letter-spacing: .16em; text-transform: uppercase; color: #8a7424; font-size: 12px; font-weight: 700; margin: 0 0 8px; }
    .cothx-links { color: #6f7b91; margin: 10px 0 0; }
    .cothx-links a { color: #0c1830; font-weight: 600; }
    .cothx-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin: 22px 0 18px; }
    .cothx-tab {
        border: 2px solid #C5A059; color: #3f2a12; background: #fffaf3;
        border-radius: 999px; padding: 8px 16px; font-size: 13px; font-weight: 600;
    }
    .cothx-tab:hover { background: #f6ead0; color: #1a1208; text-decoration: none; }
    .cothx-tab.is-on { background: #C5A059; color: #1a1208; border-color: #8a6230; }
    .cothx-panel { display: none; }
    .cothx-panel.is-on { display: block; }
    .cothx-lead { color: #6f7b91; margin: 0 0 16px; }
    .cothx-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .cothx-chip {
        border: 1px solid #d5dce8; background: #fff; color: #1f2a44;
        border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 600;
    }
    .cothx-chip:hover { border-color: #0c1830; color: #0c1830; }
    .cothx-chip.is-on { border-color: #0c1830; color: #fff; background: #0c1830; }
    .cothx-card {
        display: grid; grid-template-columns: 160px 1fr; gap: 16px; align-items: start;
        background: #fff; border: 1px solid #e6ebf3; border-radius: 16px;
        padding: 14px; margin-bottom: 14px;
    }
    .cothx-loc {
        background: #fff; border: 1px solid #e6ebf3; border-radius: 16px;
        padding: 14px 16px; margin-bottom: 14px;
    }
    .cothx-photo { height: 140px; border-radius: 12px; background: #f3f6fb; display: flex; align-items: center; justify-content: center; }
    .cothx-photo img { max-width: 100%; max-height: 130px; object-fit: contain; }
    .cothx-code { font-size: 20px; font-weight: 600; color: #0c1830; margin: 0 0 10px; }
    .cothx-sec { font-size: 14px; font-weight: 500; color: #6f7b91; }
    .cothx-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
    .cothx label { display: block; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #6f7b91; margin-bottom: 4px; }
    .cothx .form-control { background: #fff; color: #1f2a44; border: 1px solid #d5dce8; border-radius: 8px; }
    .cothx .btn-primary { border-radius: 8px; font-weight: 600; }
    .cothx-checks { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; margin-top: 10px; }
    .cothx-checks label { text-transform: none; letter-spacing: 0; font-size: 13px; color: #1f2a44; margin: 0; }
    .cothx-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .cothx-table th { text-align: left; color: #6f7b91; font-weight: 600; padding: 8px; border-bottom: 1px solid #e6ebf3; }
    .cothx-table td { padding: 10px 8px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
    .cothx .pagination { margin-top: 12px; }
    .cothx-alert { border-radius: 12px; padding: 10px 14px; margin-bottom: 14px; }
    .cothx-alert.ok { background: #e8f7ee; color: #146c43; }
    .cothx-alert.bad { background: #fdecec; color: #b42318; }
    .cothx-audit { list-style: none; padding: 0; margin: 0; }
    .cothx-audit li { padding: 10px 0; border-bottom: 1px solid #eef2f7; color: #1f2a44; }
    .cothx-list-tools { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin-bottom: 14px; }
    .cothx-list-tools .form-control { max-width: 280px; }
    #cothx-print h2 { color: #0c1830; margin: 0 0 4px; }
    .cothx-list-row.is-hidden { display: none; }
    @media print {
        body * { visibility: hidden; }
        #cothx-print, #cothx-print * { visibility: visible; }
        #cothx-print { position: absolute; left: 0; top: 0; width: 100%; background: #fff; }
        #cothx-print .no-print { display: none !important; }
        #cothx-print input { border: 0; background: transparent; padding: 0; }
    }
    @media (max-width: 900px) {
        .cothx-card, .cothx-grid { grid-template-columns: 1fr; }
    }
</style>
<section class="container-fluid">
    <div class="cothx">
        <p class="cothx-kicker">Chill on the Hill</p>
        <h1>Table desk</h1>
        <p class="cothx-links">
            <a href="{{ route('coth.admin.export', request()->query()) }}">Export CSV</a>
            &nbsp;·&nbsp;
            <a href="{{ route('coth.admin.seating', ['date' => $availabilityDate]) }}">Seating sheet for {{ $availabilityDate }}</a>
        </p>
        @if(session('message'))<div class="cothx-alert ok">{{ session('message') }}</div>@endif
        @if(session('not_permitted'))<div class="cothx-alert bad">{{ session('not_permitted') }}</div>@endif

        <nav class="cothx-tabs" aria-label="Chill on the Hill">
            @foreach ($tabs as $key => $label)
                <a class="cothx-tab {{ $tab === $key ? 'is-on' : '' }}" href="{{ route('coth.admin', array_merge(request()->except('page'), ['tab' => $key])) }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="cothx-panel {{ $tab === 'tables' ? 'is-on' : '' }}">
            @php
                $cothPresetMenu = [];
                foreach ($visuals->presets() as $presetRow) {
                    $images = [];
                    foreach ($presetRow['applies_to_capacities'] as $cap) {
                        $url = $visuals->templateImage($presetRow['slug'], $cap);
                        if ($url) {
                            $images[(string) $cap] = $url;
                        }
                    }
                    $cothPresetMenu[] = [
                        'slug' => $presetRow['slug'],
                        'name' => $presetRow['name'],
                        'style' => $presetRow['style'],
                        'capacities' => $presetRow['applies_to_capacities'],
                        'images' => $images,
                    ];
                }
            @endphp
            <p class="cothx-lead">Set the name, price, and extra chairs. The colour list follows the style and uses the template library, so a curved couch can be shades of gray or teal blue.</p>
            <div class="cothx-chips" id="cothx-secs">
                <button type="button" class="cothx-chip is-on" data-sec="all">All sections</button>
                @foreach ($sections as $section)
                    <button type="button" class="cothx-chip" data-sec="{{ $section->code }}">{{ $section->name }}</button>
                @endforeach
            </div>
            @foreach ($tables as $t)
                @php
                    $allowedPresets = $visuals->presetsForTable($t);
                    $selectedPreset = $visuals->resolvePreset($visuals->styleFor($t), (int) $t->capacity, $t->visual_preset);
                    $resolvedRate = $pricing->extraChairRate($t);
                    $sec = optional($t->section)->code ?: 'other';
                @endphp
                <form class="cothx-card" data-sec="{{ $sec }}" method="POST" action="{{ route('coth.admin.table', $t->id) }}">
                    @csrf
                    <input type="hidden" name="tab" value="tables">
                    <div class="cothx-photo">
                        <img class="cothx-shot" src="{{ $visuals->assetFor($t) }}" alt="{{ $t->code }}">
                    </div>
                    <div>
                        <h2 class="cothx-code">{{ $t->code }} <span class="cothx-sec">{{ optional($t->section)->name }}</span></h2>
                        <div class="cothx-grid">
                            <div><label>Name</label><input class="form-control" name="name" value="{{ $t->name }}"></div>
                            <div><label>Price (FCFA)</label><input class="form-control" name="price" type="number" value="{{ $t->price }}"></div>
                            <div><label>Seats</label><input class="form-control cothx-cap" name="capacity" type="number" value="{{ $t->capacity }}"></div>
                            <div>
                                <label>Style</label>
                                <select class="form-control cothx-style" name="seating_style">
                                    <option value="standard" @if($t->seating_style==='standard') selected @endif>Regular chairs</option>
                                    <option value="couch" @if($t->seating_style==='couch') selected @endif>Curved couch</option>
                                    <option value="high_table" @if($t->seating_style==='high_table') selected @endif>High chairs</option>
                                </select>
                            </div>
                            <div>
                                <label>Colour</label>
                                <select class="form-control cothx-colour" name="visual_preset">
                                    @forelse($allowedPresets as $p)
                                        <option value="{{ $p['slug'] }}" @if($selectedPreset && $selectedPreset['slug'] === $p['slug']) selected @endif>{{ $p['name'] }}</option>
                                    @empty
                                        <option value="">No template for this size</option>
                                    @endforelse
                                </select>
                            </div>
                            <div><label>Left %</label><input class="form-control" name="pos_x" value="{{ $t->pos_x }}" placeholder="Optional"></div>
                            <div><label>Top %</label><input class="form-control" name="pos_y" value="{{ $t->pos_y }}" placeholder="Optional"></div>
                            <div><label>Extra chair rate</label><input class="form-control" type="number" min="0" name="extra_chair_rate" value="{{ $t->extra_chair_rate !== null ? (int) $t->extra_chair_rate : '' }}" placeholder="{{ $resolvedRate !== null ? number_format($resolvedRate) : 'auto' }}"></div>
                        </div>
                        <div class="cothx-checks">
                            <label><input type="checkbox" name="is_active" value="1" @if($t->is_active) checked @endif> Active</label>
                            <label><input type="checkbox" name="is_city_view" value="1" @if($t->is_city_view) checked @endif> City view</label>
                            <label><input type="checkbox" name="is_high_table" value="1" @if($t->is_high_table) checked @endif> High table</label>
                            <label><input type="checkbox" name="allow_extra_chairs" value="1" @if($t->allow_extra_chairs) checked @endif> Allow extra chairs</label>
                            <label>Max extra <input class="form-control" type="number" min="0" max="50" name="max_extra_chairs" value="{{ (int) $t->max_extra_chairs }}" style="width:80px;display:inline-block;"></label>
                            <label>Notes <input class="form-control" name="extra_chair_furniture_type" value="{{ $t->extra_chair_furniture_type }}" placeholder="Chair note" style="width:180px;display:inline-block;"></label>
                            <button class="btn btn-primary" type="submit">Save {{ $t->code }}</button>
                        </div>
                        <p class="cothx-lead" style="margin:8px 0 0;">Guest rate today: {{ $resolvedRate !== null ? number_format($resolvedRate).' FCFA' : 'not set' }}</p>
                    </div>
                </form>
            @endforeach
        </div>

        <div class="cothx-panel {{ $tab === 'list' ? 'is-on' : '' }}">
            <form method="POST" action="{{ route('coth.admin.list') }}" id="cothx-print">
                @csrf
                <input type="hidden" name="tab" value="list">
                <h2 id="cothx-list-title">All tables</h2>
                <p class="cothx-lead">Print this list for manual adjustments. Choose one location, or keep every table. Notes are saved. Use Add for anything extra you want on the sheet.</p>
                <div class="cothx-list-tools no-print">
                    <div>
                        <label>Section</label>
                        <select class="form-control" id="cothx-list-sec">
                            <option value="all">All sections</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->code }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary" type="button" onclick="window.print()">Print</button>
                    <button class="btn btn-primary" type="submit">Save notes</button>
                </div>
                <table class="cothx-table">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Section</th>
                            <th>Seats</th>
                            <th>Price (FCFA)</th>
                            <th>Notes</th>
                            <th>Add</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($tables as $t)
                        <tr class="cothx-list-row" data-sec="{{ optional($t->section)->code ?: 'other' }}" data-name="{{ optional($t->section)->name }}">
                            <td><strong>{{ $t->code }}</strong></td>
                            <td>{{ optional($t->section)->name }}</td>
                            <td>{{ $t->capacity }}</td>
                            <td>{{ number_format((int) $t->price) }}</td>
                            <td><input class="form-control" name="note[{{ $t->id }}]" value="{{ $t->extra_chair_furniture_type }}"></td>
                            <td><input class="form-control" name="add[{{ $t->id }}]" value="{{ $t->list_note }}" placeholder="Add a line"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </form>
        </div>

        <div class="cothx-panel {{ $tab === 'locations' ? 'is-on' : '' }}">
            <p class="cothx-lead">Rename a location here. Guests see the name on the experience cards, and every table in that location takes the new code. HALL-T10-01 becomes GARDEN-T10-01 if you change HALL to GARDEN.</p>
            @foreach ($sections as $section)
                @php
                    $prefix = $prefixes[$section->id] ?? strtoupper($section->code);
                    $count = $tables->where('coth_section_id', $section->id)->count();
                    $sample = $tables->first(function ($row) use ($section) {
                        return (int) $row->coth_section_id === (int) $section->id;
                    });
                @endphp
                <form method="POST" action="{{ route('coth.admin.section', $section->id) }}" class="cothx-loc">
                    @csrf
                    <input type="hidden" name="tab" value="locations">
                    <h2 class="cothx-code">{{ $section->name }} <span class="cothx-sec">{{ $count }} tables</span></h2>
                    <div class="cothx-grid">
                        <div>
                            <label>Name guests see</label>
                            <input class="form-control" name="name" value="{{ $section->name }}" required maxlength="80">
                        </div>
                        <div>
                            <label>Table code</label>
                            <input class="form-control" name="prefix" value="{{ $prefix }}" required maxlength="12" pattern="[A-Za-z][A-Za-z0-9]{1,11}" title="Letters and numbers, 2 to 12 characters">
                        </div>
                        <div style="align-self:end;">
                            <button class="btn btn-primary" type="submit">Save location</button>
                        </div>
                    </div>
                    <p class="cothx-lead" style="margin:8px 0 0;">Tables here start with {{ $prefix }}@if($sample) — for example {{ $sample->code }}@endif.</p>
                </form>
            @endforeach
        </div>

        <div class="cothx-panel {{ $tab === 'reservations' ? 'is-on' : '' }}">
            <form method="GET" class="cothx-grid" style="margin-bottom:16px;">
                <input type="hidden" name="tab" value="reservations">
                <div><label>Date</label><input class="form-control" type="date" name="date" value="{{ $filters['date'] ?? '' }}"></div>
                <div>
                    <label>Section</label>
                    <select class="form-control" name="section">
                        <option value="">All</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" @if((string)($filters['section'] ?? '') === (string)$section->id) selected @endif>{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Customer</label><input class="form-control" name="customer" value="{{ $filters['customer'] ?? '' }}" placeholder="Name or phone"></div>
                <div>
                    <label>Payment</label>
                    <select class="form-control" name="payment">
                        <option value="">Any</option>
                        @foreach(['unpaid','pending','partial','paid','failed','needs_reconciliation'] as $status)
                            <option value="{{ $status }}" @if(($filters['payment'] ?? '') === $status) selected @endif>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="align-self:end;"><button class="btn btn-primary" type="submit">Filter</button></div>
            </form>
            <div style="overflow-x:auto;">
                <table class="cothx-table">
                    <thead><tr><th>Ref</th><th>Date</th><th>Table</th><th>Extras</th><th>Customer</th><th>Final</th><th>Chairs</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Payment</th><th></th></tr></thead>
                    <tbody>
                    @forelse($reservations as $row)
                        @php
                            $r = $row;
                            $totalGuests = ((int) optional($r->table)->capacity) + (int) $r->extra_chairs_qty;
                        @endphp
                        <tr>
                            <td><a href="{{ $r->publicUrl() }}" target="_blank" rel="noopener">{{ $r->reference }}</a></td>
                            <td>{{ optional($r->event_date)->format('Y-m-d') }}</td>
                            <td>{{ optional($r->table)->code }}<br><small>{{ optional(optional($r->table)->section)->name }}</small></td>
                            <td>
                                @if ((int) $r->extra_chairs_qty > 0)
                                    {{ (int) $r->extra_chairs_qty }} chairs<br><small>{{ number_format((int) $r->extra_chair_rate) }} each</small><br><small>{{ $totalGuests }} guests</small>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $r->customer_name }}<br><small>{{ $r->phone }}</small></td>
                            <td>{{ number_format((int) $r->final_price) }}</td>
                            <td>{{ number_format((int) $r->extra_chairs_amount) }}</td>
                            <td>{{ number_format((int) ($r->booking_total ?: $r->final_price)) }}</td>
                            <td>{{ number_format((int) $r->amount_paid) }}</td>
                            <td>{{ number_format((int) $r->balance) }}</td>
                            <td>{{ $r->status }}</td>
                            <td>{{ $r->payment_status }}</td>
                            <td>
                                @if($r->sale_id)<a href="{{ url('sales/gen_invoice/'.$r->sale_id) }}">Invoice</a>@endif
                                @if($r->status === 'confirmed')
                                    <a href="#" onclick="document.getElementById('amend-{{ $r->id }}').style.display='block';return false;">Amend chairs</a>
                                @endif
                            </td>
                        </tr>
                        @if($r->status === 'confirmed')
                            <tr id="amend-{{ $r->id }}" style="display:none;">
                                <td colspan="13">
                                    <form method="POST" action="{{ route('coth.admin.amend_chairs', $r->id) }}" class="cothx-grid">
                                        @csrf
                                        <input type="hidden" name="tab" value="reservations">
                                        <div><label>Chairs</label><input class="form-control" type="number" min="0" max="{{ max(1, (int) optional($r->table)->max_extra_chairs) }}" name="extra_chairs_qty_new" value="{{ (int) $r->extra_chairs_qty }}"></div>
                                        <div>
                                            <label>Payment</label>
                                            <select class="form-control" name="payment_mode">
                                                <option value="none">No extra payment</option>
                                                <option value="manual">Manual</option>
                                                <option value="gateway">MoMo / Visa link</option>
                                            </select>
                                        </div>
                                        <div><label>Amount</label><input class="form-control" type="number" min="0" name="payment_amount" placeholder="0"></div>
                                        <div>
                                            <label>Method</label>
                                            <select class="form-control" name="method">
                                                <option value="cash">Cash</option>
                                                <option value="momo">MoMo</option>
                                                <option value="card_terminal">Card terminal</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label>Gateway</label>
                                            <select class="form-control" name="gateway_method">
                                                <option value="momo">MoMo</option>
                                                <option value="card">Visa / card</option>
                                            </select>
                                        </div>
                                        <div><label>Note</label><input class="form-control" name="note" placeholder="Optional"></div>
                                        <div style="align-self:end;"><button class="btn btn-primary">Apply</button></div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="13">No reservations.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $reservations->appends(request()->query())->links() }}
        </div>

        <div class="cothx-panel {{ $tab === 'availability' ? 'is-on' : '' }}">
            <form method="GET" style="max-width:280px;margin-bottom:14px;">
                <input type="hidden" name="tab" value="availability">
                @foreach($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                <label>Night</label>
                <select name="availability" class="form-control" onchange="this.form.submit()">
                    @foreach($dates as $date)
                        <option value="{{ $date }}" @if($date === $availabilityDate) selected @endif>{{ $date }}</option>
                    @endforeach
                </select>
            </form>
            <table class="cothx-table">
                <thead><tr><th>Table</th><th>Section</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($slots as $slot)
                    <tr>
                        <td>{{ optional($slot->table)->code }}</td>
                        <td>{{ optional(optional($slot->table)->section)->name }}</td>
                        <td>{{ $slot->status }}</td>
                        <td>
                            @if($slot->status !== 'booked')
                                <form method="POST" action="{{ route('coth.admin.slot', $slot->id) }}">
                                    @csrf
                                    <input type="hidden" name="tab" value="availability">
                                    <input type="hidden" name="availability" value="{{ $availabilityDate }}">
                                    @if(! $slot->is_enabled)<input type="hidden" name="is_enabled" value="1">@endif
                                    <button class="btn btn-primary" type="submit">{{ $slot->is_enabled ? 'Disable this night' : 'Enable this night' }}</button>
                                </form>
                            @else
                                Booked
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="cothx-panel {{ $tab === 'chairs' ? 'is-on' : '' }}">
            <p class="cothx-lead">Set a daily cap on extra chairs for the whole venue. Leave the total blank for no cap.</p>
            <table class="cothx-table">
                <thead><tr><th>Date</th><th>Total chairs</th><th>Held</th><th>Booked</th><th>Available</th></tr></thead>
                <tbody>
                @foreach($dates as $d)
                    @php $inv = $chairInventory->get($d); @endphp
                    <tr>
                        <td>{{ $d }}</td>
                        <td>
                            <form method="POST" action="{{ route('coth.admin.chair_inventory') }}" style="display:flex;gap:8px;align-items:center;">
                                @csrf
                                <input type="hidden" name="tab" value="chairs">
                                <input type="hidden" name="event_date" value="{{ $d }}">
                                <input class="form-control" type="number" min="0" name="total_capacity" value="{{ $inv && $inv->total_capacity !== null ? $inv->total_capacity : '' }}" placeholder="unlimited" style="width:140px;">
                                <button class="btn btn-primary">Save</button>
                            </form>
                        </td>
                        <td>{{ $inv ? (int) $inv->held : 0 }}</td>
                        <td>{{ $inv ? (int) $inv->booked : 0 }}</td>
                        <td>{{ $inv && $inv->total_capacity !== null ? max(0, (int) $inv->total_capacity - (int) $inv->held - (int) $inv->booked) : 'unlimited' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="cothx-panel {{ $tab === 'rules' ? 'is-on' : '' }}">
            @if($settings)
                <form method="POST" action="{{ route('coth.admin.settings') }}">
                    @csrf
                    <input type="hidden" name="tab" value="rules">
                    <div class="cothx-grid">
                        <div><label>Deposit %</label><input class="form-control" name="deposit_percent" type="number" value="{{ $settings->deposit_percent }}"></div>
                        <div><label>Discount %</label><input class="form-control" name="discount_percent" type="number" value="{{ $settings->discount_percent }}"></div>
                        <div><label>Hold minutes</label><input class="form-control" name="hold_minutes" type="number" value="{{ $settings->hold_minutes }}"></div>
                        <div><label>Event end</label><input class="form-control" type="datetime-local" name="event_end_at" value="{{ $event && $event->event_end_at ? $event->event_end_at->format('Y-m-d\TH:i') : '' }}"></div>
                    </div>
                    <label style="margin-top:12px;">Discount dates</label>
                    <textarea class="form-control" name="discount_dates" rows="3">{{ implode("\n", $settings->discountDateList()) }}</textarea>
                    <label class="cothx-checks"><input type="checkbox" name="discount_includes_addons" value="1" @if($settings->discount_includes_addons) checked @endif> Apply the date discount to extra chairs too</label>
                    <button class="btn btn-primary" style="margin-top:12px;" type="submit">Save rules</button>
                </form>
            @endif
        </div>

        <div class="cothx-panel {{ $tab === 'audit' ? 'is-on' : '' }}">
            <ul class="cothx-audit">
                @foreach($audits as $audit)
                    <li>{{ $audit->created_at }} · {{ $audit->action }} · {{ $audit->note }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
<script>
var cothPresets = @json($cothPresetMenu ?? []);
function cothChoices(style, cap) {
    cap = parseInt(cap, 10);
    return cothPresets.filter(function (preset) {
        if (preset.style !== style) return false;
        return !preset.capacities.length || preset.capacities.indexOf(cap) !== -1;
    });
}
function cothPaint(form) {
    var style = form.querySelector('.cothx-style');
    var capInput = form.querySelector('.cothx-cap');
    var colour = form.querySelector('.cothx-colour');
    if (!style || !colour) return;
    var cap = capInput ? capInput.value : '';
    var keep = colour.value;
    var list = cothChoices(style.value, cap);
    colour.innerHTML = '';
    if (!list.length) {
        var empty = document.createElement('option');
        empty.value = '';
        empty.textContent = 'No template for this size';
        colour.appendChild(empty);
        return;
    }
    list.forEach(function (preset) {
        var opt = document.createElement('option');
        opt.value = preset.slug;
        opt.textContent = preset.name;
        colour.appendChild(opt);
    });
    colour.value = list.some(function (preset) { return preset.slug === keep; }) ? keep : list[0].slug;
    var chosen = list.filter(function (preset) { return preset.slug === colour.value; })[0];
    var img = form.querySelector('.cothx-shot');
    var shot = chosen && chosen.images ? chosen.images[String(parseInt(cap, 10))] : '';
    if (img && shot) img.src = shot;
}
document.querySelectorAll('.cothx-card').forEach(function (form) {
    ['.cothx-style', '.cothx-colour', '.cothx-cap'].forEach(function (sel) {
        var field = form.querySelector(sel);
        if (field) field.addEventListener('change', function () { cothPaint(form); });
    });
});
var listSec = document.getElementById('cothx-list-sec');
if (listSec) {
    listSec.addEventListener('change', function () {
        var sec = listSec.value;
        var title = 'All tables';
        document.querySelectorAll('.cothx-list-row').forEach(function (row) {
            var show = sec === 'all' || row.getAttribute('data-sec') === sec;
            row.classList.toggle('is-hidden', !show);
            if (show && sec !== 'all') title = row.getAttribute('data-name') || title;
        });
        document.getElementById('cothx-list-title').textContent = title;
    });
}
document.querySelectorAll('#cothx-secs .cothx-chip').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#cothx-secs .cothx-chip').forEach(function (chip) { chip.classList.remove('is-on'); });
        btn.classList.add('is-on');
        var sec = btn.getAttribute('data-sec');
        document.querySelectorAll('.cothx-card').forEach(function (card) {
            card.style.display = (sec === 'all' || card.getAttribute('data-sec') === sec) ? '' : 'none';
        });
    });
});
</script>
@endsection
