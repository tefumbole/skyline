@extends('layout.main')

@section('content')
@php $cmTab = 'courses.invoices'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap" style="gap:12px;">
            <div>
                <h1 class="cm-title"><i class="dripicons-document"></i> Invoices</h1>
                <p class="cm-subtitle">Manage client billing and invoices.</p>
            </div>
            <form method="GET"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search client or invoice #"></form>
        </div>
        <div class="cm-page-card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $inv)
                            <tr>
                                <td><code>{{ $inv->invoice_number }}</code></td>
                                <td>
                                    <strong>{{ $inv->client_name }}</strong>
                                    <div class="small text-muted">{{ $inv->email }}</div>
                                </td>
                                <td>{{ optional($inv->created_at)->format('d M Y') }}</td>
                                <td class="cm-price">${{ number_format((float)$inv->total, 0) }}</td>
                                <td><span class="cm-badge">{{ $inv->payment_status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No invoices found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->appends(request()->query())->links() }}</div>
        </div>
    </div>
</section>
@endsection
