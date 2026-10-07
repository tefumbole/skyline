@extends('layout.main')

@section('content')
@php $cmTab = 'courses.index'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        <div class="mb-4">
            <h1 class="cm-title">Feedback — {{ $course->name }}</h1>
            <p class="cm-subtitle">Student ratings for this course.</p>
        </div>
        <div class="cm-page-card">
            <a href="{{ route('courses.index') }}" class="cm-btn-outline mb-3">← Back to Course List</a>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Rating</th>
                            <th>Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $fb)
                            <tr>
                                <td>{{ optional($fb->created_at)->format('d M Y') }}</td>
                                <td>{{ $fb->student_name ?: '—' }}</td>
                                <td>{{ $fb->rating }} ★</td>
                                <td>{{ $fb->feedback_text }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No feedback for this course yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
