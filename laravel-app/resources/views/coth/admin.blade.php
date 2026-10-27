@extends('layout.main')
@section('content')
<section class="container-fluid">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <h1>COTH Reservations</h1>
    <p><a href="{{ route('coth.admin.export', request()->query()) }}">Export CSV</a></p>

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
            <thead><tr><th>Ref</th><th>Date</th><th>Table</th><th>Customer</th><th>Final</th><th>Paid</th><th>Balance</th><th>Status</th><th>Payment</th><th>Sale</th></tr></thead>
            <tbody>
            @forelse($reservations as $row)
                <tr>
                    <td><a href="{{ $row->publicUrl() }}" target="_blank" rel="noopener">{{ $row->reference }}</a></td>
                    <td>{{ optional($row->event_date)->format('Y-m-d') }}</td>
                    <td>{{ optional($row->table)->code }}<br><small>{{ optional(optional($row->table)->section)->name }}</small></td>
                    <td>{{ $row->customer_name }}<br><small>{{ $row->phone }}</small></td>
                    <td>{{ number_format($row->final_price) }}</td>
                    <td>{{ number_format($row->amount_paid) }}</td>
                    <td>{{ number_format($row->balance) }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ $row->payment_status }}</td>
                    <td>@if($row->sale_id)<a href="{{ url('sales/gen_invoice/'.$row->sale_id) }}">Invoice</a>@endif</td>
                </tr>
            @empty
                <tr><td colspan="10">No reservations.</td></tr>
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

    <h2>Tables</h2>
    <p>Leave position blank to keep the public floor labelled “Illustrative layout.” Use 0–100 for left and top.</p>
    @foreach($tables as $table)
        <form method="POST" action="{{ route('coth.admin.table', $table->id) }}" class="form-inline" style="margin-bottom:8px;">
            @csrf
            <strong style="display:inline-block;width:120px;">{{ $table->code }}</strong>
            <input class="form-control" name="name" value="{{ $table->name }}" style="width:140px;">
            <input class="form-control" name="capacity" type="number" value="{{ $table->capacity }}" style="width:80px;">
            <input class="form-control" name="price" type="number" value="{{ $table->price }}" style="width:120px;">
            <select class="form-control" name="seating_style">
                <option value="standard" @if($table->seating_style==='standard') selected @endif>Standard</option>
                <option value="couch" @if($table->seating_style==='couch') selected @endif>Couch</option>
            </select>
            <input class="form-control" name="pos_x" value="{{ $table->pos_x }}" placeholder="Left %" style="width:90px;">
            <input class="form-control" name="pos_y" value="{{ $table->pos_y }}" placeholder="Top %" style="width:90px;">
            <label><input type="checkbox" name="is_active" value="1" @if($table->is_active) checked @endif> Active</label>
            <button class="btn btn-default" type="submit">Save</button>
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
