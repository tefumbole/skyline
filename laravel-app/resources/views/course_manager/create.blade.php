@extends('layout.main')

@section('content')
@php $cmTab = 'courses.create'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        <div class="mb-4">
            <h1 class="cm-title">Add Course</h1>
            <p class="cm-subtitle">Create a course that appears on the public Trainings and Register Now pages.</p>
        </div>
        <div class="cm-page-card">
            <form method="POST" action="{{ route('courses.store') }}">
                @csrf
                @include('course_manager.partials.form', ['course' => null])
                <button type="submit" class="cm-btn-gold"><i class="dripicons-plus"></i> Create Course</button>
                <a href="{{ route('courses.index') }}" class="cm-btn-outline ml-2">Cancel</a>
            </form>
        </div>
    </div>
</section>
@endsection
