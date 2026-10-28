@extends('layout.main')
@section('content')
<section class="container-fluid">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <h1>COTH Reservations</h1>
    <p>
        <a href="{{ route('coth.admin.export', request()->query()) }}">Export CSV</a>
        &nbsp;·&nbsp;
        <a href="{{ route('coth.admin.seating', ['date' => $availabilityDate]) }}">Seating sheet for {{ $availabilityDate }}</a>
    </p>

    <form method="GET" class="form-inline" style="margin-bottom:16px;">
        <input class="form-control" type="date" name="date" value="{{ $filters['date'] ?? '' }}">
        <select class="form-control" name="section">
            <option value="">Section</option>
            @foreach($sections as $section)
                <option value="{{ $section->id }}" @if((string)($filters['section'] ?? '') === (string)$section->id) selected @endif>{{ $section->name }}</option>
            @endforeach
        </select>
        <input class="form-control" name="customer" value="{{ $filters['customer'] ?? '' }}" placeholder="Customer or phone">
        <select class="form-control" name="payment">
            <option value="">Payment</option>
            @foreach(['unpaid','pending','partial','paid','failed','needs_reconciliation'] as $status)
                <option value="{{ $status }}" @if(($filters['payment'] ?? '') === $status) selected @endif>{{ $status }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Ref</th><th>Date</th><th>Table</th><th>Extras</th><th>Customer</th><th>Final table</th><th>Chairs</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Payment</th><th>Actions</th></tr></thead>
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
                            {{ (int) $r->extra_chairs_qty }} ch<br><small>{{ number_format((int) $r->extra_chair_rate) }} / each</small><br><small>Guests: {{ $totalGuests }}</small>
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
                            &middot; <a href="#" onclick="document.getElementById('amend-{{ $r->id }}').style.display='block';return false;">Amend chairs</a>
                        @endif
                    </td>
                </tr>
                @if($r->status === 'confirmed')
                    <tr id="amend-{{ $r->id }}" style="display:none;background:#f9fafb;">
                        <td colspan="13">
                            <form method="POST" action="{{ route('coth.admin.amend_chairs', $r->id) }}" class="form-inline">
                                @csrf
                                <strong>Amend {{ $r->reference }}:</strong>
                                <label>Chairs:
                                    <input class="form-control" type="number" min="0" max="{{ max(1, (int) optional($r->table)->max_extra_chairs) }}" name="extra_chairs_qty_new" value="{{ (int) $r->extra_chairs_qty }}" style="width:90px;">
                                </label>
                                <label>Payment:
                                    <select class="form-control" name="payment_mode">
                                        <option value="none">No extra payment</option>
                                        <option value="manual">Manual (record now)</option>
                                        <option value="gateway">Send new MoMo/Visa link</option>
                                    </select>
                                </label>
                                <label>Amount (manual):
                                    <input class="form-control" type="number" min="0" name="payment_amount" placeholder="0" style="width:120px;">
                                </label>
                                <label>Method (manual):
                                    <select class="form-control" name="method">
                                        <option value="cash">Cash</option>
                                        <option value="momo">MoMo (staff-collected)</option>
                                        <option value="card_terminal">Card terminal</option>
                                    </select>
                                </label>
                                <label>Gateway method:
                                    <select class="form-control" name="gateway_method">
                                        <option value="momo">MoMo</option>
                                        <option value="card">Visa / card</option>
                                    </select>
                                </label>
                                <input class="form-control" name="note" placeholder="Note (optional)" style="width:220px;">
                                <button class="btn btn-primary">Apply amendment</button>
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

    <h2>Availability on {{ $availabilityDate }}</h2>
    <form method="GET">
        @foreach($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
        <select name="availability" class="form-control" style="max-width:240px;" onchange="this.form.submit()">
            @foreach($dates as $date)
                <option value="{{ $date }}" @if($date === $availabilityDate) selected @endif>{{ $date }}</option>
            @endforeach
        </select>
    </form>
    <div class="table-responsive">
        <table class="table table-condensed">
            <thead><tr><th>Table</th><th>Section</th><th>Status</th><th>Enabled</th></tr></thead>
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
                                @if(! $slot->is_enabled)<input type="hidden" name="is_enabled" value="1">@endif
                                <button class="btn btn-xs btn-default" type="submit">{{ $slot->is_enabled ? 'Disable this date' : 'Enable this date' }}</button>
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

    <h2>Shared extra-chair inventory</h2>
    <p>Set a daily cap on extra chairs available for the whole venue. Leave the total blank for no cap (per-table maxes still apply).</p>
    <div class="table-responsive">
        <table class="table table-condensed">
            <thead><tr><th>Date</th><th>Total chairs</th><th>Held</th><th>Booked</th><th>Available</th><th></th></tr></thead>
            <tbody>
                @foreach($dates as $d)
                    @php $inv = $chairInventory->get($d); @endphp
                    <tr>
                        <td>{{ $d }}</td>
                        <td>
                            <form method="POST" action="{{ route('coth.admin.chair_inventory') }}" class="form-inline">
                                @csrf
                                <input type="hidden" name="event_date" value="{{ $d }}">
                                <input class="form-control" type="number" min="0" name="total_capacity" value="{{ $inv && $inv->total_capacity !== null ? $inv->total_capacity : '' }}" placeholder="unlimited" style="width:120px;">
                                <button class="btn btn-xs btn-default">Save</button>
                            </form>
                        </td>
                        <td>{{ $inv ? (int) $inv->held : 0 }}</td>
                        <td>{{ $inv ? (int) $inv->booked : 0 }}</td>
                        <td>{{ $inv && $inv->total_capacity !== null ? max(0, (int) $inv->total_capacity - (int) $inv->held - (int) $inv->booked) : 'unlimited' }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2>Tables</h2>
    <p>Pick a 3D preset per table, toggle city-view/high-table, and configure extra-chair rules. Leave position blank to keep the public floor labelled "Illustrative layout."</p>
    @foreach($tables as $t)
        @php
            $allowedPresets = $visuals->presetsForTable($t);
            $resolvedRate = $pricing->extraChairRate($t);
        @endphp
        <form method="POST" action="{{ route('coth.admin.table', $t->id) }}" style="margin-bottom:16px;padding:10px;border:1px solid #e5e7eb;border-radius:8px;">
            @csrf
            <div class="form-inline" style="margin-bottom:6px;">
                <strong style="display:inline-block;width:120px;">{{ $t->code }}</strong>
                <input class="form-control" name="name" value="{{ $t->name }}" style="width:140px;">
                <input class="form-control" name="capacity" type="number" value="{{ $t->capacity }}" style="width:80px;">
                <input class="form-control" name="price" type="number" value="{{ $t->price }}" style="width:120px;">
                <select class="form-control" name="seating_style">
                    <option value="standard" @if($t->seating_style==='standard') selected @endif>Standard</option>
                    <option value="couch" @if($t->seating_style==='couch') selected @endif>Couch</option>
                    <option value="high_table" @if($t->seating_style==='high_table') selected @endif>High table</option>
                </select>
                <input class="form-control" name="pos_x" value="{{ $t->pos_x }}" placeholder="Left %" style="width:90px;">
                <input class="form-control" name="pos_y" value="{{ $t->pos_y }}" placeholder="Top %" style="width:90px;">
                <label><input type="checkbox" name="is_active" value="1" @if($t->is_active) checked @endif> Active</label>
            </div>
            <div class="form-inline" style="margin-bottom:6px;">
                <label>Preset:
                    <select class="form-control" name="visual_preset" style="width:260px;">
                        <option value="">— Default artwork —</option>
                        @foreach($allowedPresets as $p)
                            <option value="{{ $p['slug'] }}" @if($t->visual_preset === $p['slug']) selected @endif>{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label><input type="checkbox" name="is_city_view" value="1" @if($t->is_city_view) checked @endif> City view</label>
                <label><input type="checkbox" name="is_high_table" value="1" @if($t->is_high_table) checked @endif> High table</label>
                <img src="{{ $visuals->assetFor($t) }}" alt="preview" style="height:46px;margin-left:12px;border:1px solid #e5e7eb;border-radius:6px;background:#0c0c0c;padding:2px;">
            </div>
            <div class="form-inline" style="margin-bottom:6px;">
                <label><input type="checkbox" name="allow_extra_chairs" value="1" @if($t->allow_extra_chairs) checked @endif> Allow extra chairs</label>
                <label>Max chairs: <input class="form-control" type="number" min="0" max="50" name="max_extra_chairs" value="{{ (int) $t->max_extra_chairs }}" style="width:80px;"></label>
                <label>Rate (FCFA):
                    <input class="form-control" type="number" min="0" name="extra_chair_rate" value="{{ $t->extra_chair_rate !== null ? (int) $t->extra_chair_rate : '' }}" placeholder="auto" style="width:120px;">
                </label>
                <label>Furniture notes:
                    <input class="form-control" name="extra_chair_furniture_type" value="{{ $t->extra_chair_furniture_type }}" placeholder="e.g. matching navy chair" style="width:220px;">
                </label>
                <small>
                    Resolved rate: {{ $resolvedRate !== null ? number_format($resolvedRate).' FCFA' : 'not configured' }}
                </small>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    @endforeach

    <h2>Rules</h2>
    @if($settings)
        <form method="POST" action="{{ route('coth.admin.settings') }}">
            @csrf
            <div class="row">
                <div class="col-sm-3"><label>Deposit %</label><input class="form-control" name="deposit_percent" type="number" value="{{ $settings->deposit_percent }}"></div>
                <div class="col-sm-3"><label>Discount %</label><input class="form-control" name="discount_percent" type="number" value="{{ $settings->discount_percent }}"></div>
                <div class="col-sm-3"><label>Hold minutes</label><input class="form-control" name="hold_minutes" type="number" value="{{ $settings->hold_minutes }}"></div>
                <div class="col-sm-3"><label>Event end</label><input class="form-control" type="datetime-local" name="event_end_at" value="{{ $event && $event->event_end_at ? $event->event_end_at->format('Y-m-d\TH:i') : '' }}"></div>
            </div>
            <label>Discount dates (one per line or comma separated)</label>
            <textarea class="form-control" name="discount_dates" rows="3">{{ implode("\n", $settings->discountDateList()) }}</textarea>
            <label style="margin-top:8px;"><input type="checkbox" name="discount_includes_addons" value="1" @if($settings->discount_includes_addons) checked @endif> Apply date discount to extra chairs too</label>
            <button class="btn btn-primary" style="margin-top:8px;" type="submit">Save rules</button>
        </form>
    @endif

    <h2>Audit</h2>
    <ul>
        @foreach($audits as $audit)
            <li>{{ $audit->created_at }} · {{ $audit->action }} · {{ $audit->note }}</li>
        @endforeach
    </ul>
</section>
@endsection
