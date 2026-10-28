@extends('layout.main')
@section('content')
<section class="container-fluid">
    <h1>Seating &amp; setup sheet — {{ \Carbon\Carbon::parse($date)->format('l, j F Y') }}</h1>
    <p>
        <a href="{{ route('coth.admin') }}">&larr; All reservations</a>
    </p>

    <form method="GET" class="form-inline" style="margin-bottom:16px;">
        <label>Date:
            <select class="form-control" name="date" onchange="this.form.submit()">
                @foreach($dates as $d)
                    <option value="{{ $d }}" @if($d === $date) selected @endif>{{ $d }}</option>
                @endforeach
            </select>
        </label>
    </form>

    @forelse($grouped as $sectionName => $rows)
        <h2>{{ $sectionName }}</h2>
        <table class="table table-bordered table-condensed">
            <thead><tr><th>Ref</th><th>Table</th><th>Seats</th><th>Extras</th><th>Guests</th><th>Furniture</th><th>Customer</th></tr></thead>
            <tbody>
            @foreach($rows as $r)
                @php
                    $tbl = $r->table;
                    $extraQty = (int) $r->extra_chairs_qty;
                    $totalGuests = ($tbl ? (int) $tbl->capacity : 0) + $extraQty;
                    $chip = $tbl ? $visuals->chipFor($tbl) : '';
                @endphp
                <tr>
                    <td>{{ $r->reference }}</td>
                    <td>{{ $tbl ? $tbl->code : '' }}@if($chip) <small>({{ $chip }})</small>@endif</td>
                    <td>{{ $tbl ? $tbl->capacity : '' }}</td>
                    <td>@if($extraQty > 0){{ $extraQty }} chair(s)@else—@endif</td>
                    <td>{{ $totalGuests }}</td>
                    <td>
                        {{ $tbl && $tbl->seating_style ? ucfirst($tbl->seating_style) : '' }}
                        @if($tbl && $tbl->extra_chair_furniture_type) · {{ $tbl->extra_chair_furniture_type }}@endif
                    </td>
                    <td>{{ $r->customer_name }}<br><small>{{ $r->phone }}</small></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @empty
        <p>No confirmed reservations for this date yet.</p>
    @endforelse
</section>
@endsection
