@extends('layout.main')

@section('content')
@php $cmTab = 'courses.index'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        <div class="mb-4">
            <h1 class="cm-title">Edit Course</h1>
            <p class="cm-subtitle">{{ $course->name }}</p>
        </div>
        <div class="cm-page-card">
            <form method="POST" action="{{ route('courses.update', $course->id) }}">
                @csrf
                @include('course_manager.partials.form', ['course' => $course])
                <button type="submit" class="cm-btn-primary">Save Changes</button>
                <a href="{{ route('courses.index') }}" class="cm-btn-outline ml-2">Cancel</a>
            </form>
        </div>
    </div>
</section>
@endsection
