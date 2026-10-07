@extends('layout.main')

@section('content')
@php $cmTab = 'courses.certificates'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap" style="gap:12px;">
            <div>
                <h1 class="cm-title"><i class="dripicons-trophy"></i> Certificates</h1>
                <p class="cm-subtitle">Manage issued course certificates.</p>
            </div>
            <form method="GET"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search student or cert #"></form>
        </div>
        @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        <div class="cm-page-card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Cert #</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Issued Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $cert)
                            <tr>
                                <td><code>{{ $cert->certificate_number }}</code></td>
                                <td><strong>{{ $cert->student_name }}</strong></td>
                                <td>{{ $cert->course_name }}</td>
                                <td>{{ optional($cert->completion_date ?: $cert->created_at)->format('d M Y') }}</td>
                                <td><span class="cm-badge">{{ $cert->status }}</span></td>
                                <td>
                                    @if($cert->status === 'active')
                                        <form method="POST" action="{{ route('courses.certificates.revoke', $cert->id) }}" onsubmit="return confirm('Revoke certificate?');">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Revoke</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No certificates found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->appends(request()->query())->links() }}</div>
        </div>
    </div>
</section>
@endsection
