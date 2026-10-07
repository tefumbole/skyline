@extends('layout.main')

@section('content')
@php $cmTab = 'courses.feedback'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        <div class="mb-4">
            <h1 class="cm-title"><i class="dripicons-message"></i> Course Feedback</h1>
            <p class="cm-subtitle">Review and manage student feedback.</p>
        </div>
        @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        <div class="cm-page-card mb-3">
            <form method="GET" class="form-row align-items-end">
                <div class="col-md-5 form-group mb-md-0">
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search student or feedback…">
                </div>
                <div class="col-md-3 form-group mb-md-0">
                    <select name="course_id" class="form-control">
                        <option value="">All Courses</option>
                        @foreach($courseList as $c)
                            <option value="{{ $c->id }}" {{ request('course_id') === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group mb-md-0">
                    <select name="rating" class="form-control">
                        <option value="">All Ratings</option>
                        @for($i=5;$i>=1;$i--)
                            <option value="{{ $i }}" {{ (string)request('rating') === (string)$i ? 'selected' : '' }}>{{ $i }} ★</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="cm-btn-primary w-100" style="justify-content:center;">Filter</button>
                </div>
            </form>
        </div>
        <div class="cm-page-card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Student</th>
                            <th>Rating</th>
                            <th>Feedback</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $fb)
                            @php $courseName = optional($courseList->firstWhere('id', $fb->course_id))->name; @endphp
                            <tr>
                                <td>{{ optional($fb->created_at)->format('d M Y') }}</td>
                                <td>{{ $courseName ?: '—' }}</td>
                                <td><strong>{{ $fb->student_name ?: '—' }}</strong></td>
                                <td>{{ $fb->rating }} ★</td>
                                <td>{{ \Illuminate\Support\Str::limit($fb->feedback_text, 120) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('courses.feedback.destroy', $fb->id) }}" onsubmit="return confirm('Delete feedback?');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger"><i class="dripicons-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No feedback found matching criteria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->appends(request()->query())->links() }}</div>
        </div>
    </div>
</section>
@endsection
