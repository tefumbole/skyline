@extends('layout.main')

@section('content')
@php $cmTab = 'courses.registrations'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap" style="gap:12px;">
            <div>
                <h1 class="cm-title">Registration Management</h1>
                <p class="cm-subtitle">Monitor and manage course enrollments / applications.</p>
            </div>
            <a href="{{ route('courses.registrations') }}" class="cm-btn-outline"><i class="dripicons-clockwise"></i> Refresh Data</a>
        </div>
        @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        <div class="row mb-3">
            <div class="col-6 col-md-3 mb-2"><div class="cm-stat"><p class="label">Total Registrations</p><p class="value">{{ $stats['total'] }}</p></div></div>
            <div class="col-6 col-md-3 mb-2"><div class="cm-stat"><p class="label">Total Revenue</p><p class="value green">${{ number_format($stats['revenue'], 0) }}</p></div></div>
            <div class="col-6 col-md-3 mb-2"><div class="cm-stat"><p class="label">Confirmed</p><p class="value">{{ $stats['confirmed'] }}</p></div></div>
            <div class="col-6 col-md-3 mb-2"><div class="cm-stat"><p class="label">Pending</p><p class="value">{{ $stats['pending'] }}</p></div></div>
        </div>
        <form method="GET" class="form-inline mb-3">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control mr-2 mb-2" placeholder="Search client name, email…">
            <select name="status" class="form-control mr-2 mb-2">
                <option value="all">All Statuses</option>
                @foreach(['pending','confirmed','completed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="cm-btn-primary mb-2">Filter</button>
        </form>
        <div class="cm-page-card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Contact</th>
                            <th>Date</th>
                            <th>Courses</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $reg)
                            <tr>
                                <td>
                                    <strong>{{ $reg->client_name }}</strong>
                                    <div class="small text-muted">{{ $reg->reference_number }}</div>
                                </td>
                                <td>
                                    <div>{{ $reg->client_email }}</div>
                                    <div class="small text-muted">{{ $reg->client_phone }}</div>
                                </td>
                                <td>{{ optional($reg->created_at)->format('d M Y') }}</td>
                                <td>{{ $reg->course_names ?: '—' }}</td>
                                <td class="cm-price">${{ number_format((float)$reg->total_price, 0) }}</td>
                                <td><span class="cm-badge">{{ $reg->status }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('courses.registrations.update', $reg->id) }}" class="form-inline">
                                        @csrf
                                        <select name="status" class="form-control form-control-sm mr-1">
                                            @foreach(['pending','confirmed','completed','cancelled'] as $s)
                                                <option value="{{ $s }}" {{ $reg->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                            @endforeach
                                        </select>
                                        <select name="payment_status" class="form-control form-control-sm mr-1">
                                            @foreach(['pending','paid','refunded'] as $s)
                                                <option value="{{ $s }}" {{ $reg->payment_status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="cm-btn-outline">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No registrations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->appends(request()->query())->links() }}</div>
        </div>
    </div>
</section>
@endsection
