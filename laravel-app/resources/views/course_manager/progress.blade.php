@extends('layout.main')

@section('content')
@php $cmTab = 'courses.progress'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap" style="gap:12px;">
            <div>
                <h1 class="cm-title"><i class="dripicons-graph-line"></i> Student Progress</h1>
                <p class="cm-subtitle">Track course completion and generate certificates.</p>
            </div>
            <div class="d-flex" style="gap:10px;">
                <div class="cm-stat" style="min-width:120px;border-color:#bbf7d0;">
                    <p class="label" style="color:#15803d;">COMPLETED</p>
                    <p class="value green">{{ $stats['completed'] }}</p>
                </div>
                <div class="cm-stat" style="min-width:120px;border-color:#bfdbfe;">
                    <p class="label" style="color:#1d4ed8;">AVG PROGRESS</p>
                    <p class="value" style="color:#1d4ed8;">{{ number_format($stats['avg'], 0) }}%</p>
                </div>
            </div>
        </div>
        @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        <form method="GET" class="mb-3">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search student or course…">
        </form>
        <div class="cm-page-card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $row)
                            <tr>
                                <td><strong>{{ $row->student_name ?: '—' }}</strong></td>
                                <td>{{ $row->course_name }}</td>
                                <td>{{ number_format((float)$row->progress_percentage, 0) }}%</td>
                                <td><span class="cm-badge">{{ $row->status }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('courses.progress.update', $row->id) }}" class="form-inline">
                                        @csrf
                                        <input type="number" name="progress_percentage" min="0" max="100" class="form-control form-control-sm mr-1" style="width:80px;" value="{{ (int)$row->progress_percentage }}">
                                        <select name="status" class="form-control form-control-sm mr-1">
                                            @foreach(['not_started','in_progress','completed'] as $s)
                                                <option value="{{ $s }}" {{ $row->status === $s ? 'selected' : '' }}>{{ str_replace('_',' ', ucfirst($s)) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="cm-btn-outline">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No progress records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->appends(request()->query())->links() }}</div>
        </div>
    </div>
</section>
@endsection
