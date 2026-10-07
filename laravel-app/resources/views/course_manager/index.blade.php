@extends('layout.main')

@section('content')
@php $cmTab = 'courses.index'; @endphp
<section class="forms">
    <div class="container-fluid cm-shell">
        @include('course_manager.partials.tabs')
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap" style="gap:12px;">
            <div>
                <h1 class="cm-title"><i class="fa fa-book"></i> Course Management</h1>
                <p class="cm-subtitle">Manage educational courses and training programs shown on the public Trainings page.</p>
            </div>
            <a href="{{ route('courses.create') }}" class="cm-btn-gold"><i class="dripicons-plus"></i> Add Course</a>
        </div>
        @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        <form method="GET" class="mb-3">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search courses…">
        </form>

        <div class="cm-page-card mb-3 py-2 px-3 d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <label class="mb-0 d-flex align-items-center" style="gap:8px;font-weight:600;cursor:pointer;">
                <input type="checkbox" id="cm-dnd-toggle" style="width:16px;height:16px;">
                Enable drag &amp; drop reorder
            </label>
            <span id="cm-dnd-hint" class="text-muted small" style="display:none;">Drag rows by the ☰ handle. Order saves automatically.</span>
        </div>

        <div class="cm-page-card">
            <div class="table-responsive">
                <table class="table mb-0" id="cm-courses-table">
                    <thead>
                        <tr>
                            <th style="width:40px;display:none;" class="cm-dnd-col"></th>
                            <th style="width:90px;" class="cm-arrows-col">Order</th>
                            <th>Course Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cm-courses-body">
                        @forelse($items as $course)
                            <tr data-id="{{ $course->id }}">
                                <td class="cm-dnd-col text-center text-muted" style="display:none;cursor:grab;">
                                    <span class="cm-drag-handle" title="Drag">☰</span>
                                </td>
                                <td class="cm-order cm-arrows-col text-nowrap">
                                    @foreach(['top'=>'⇈','up'=>'↑','down'=>'↓','bottom'=>'⇊'] as $dir => $icon)
                                        <form method="POST" action="{{ route('courses.move', $course->id) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="direction" value="{{ $dir }}">
                                            <button type="submit" title="{{ $dir }}">{{ $icon }}</button>
                                        </form>
                                    @endforeach
                                </td>
                                <td><strong>{{ $course->name }}</strong>
                                    @if($course->status !== 'active')
                                        <span class="cm-badge">{{ $course->status }}</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ \Illuminate\Support\Str::limit($course->description, 80) ?: '—' }}</td>
                                <td><span class="cm-badge">{{ $course->category ?: 'Training' }}</span></td>
                                <td class="cm-price">${{ number_format((float)$course->price, 0) }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('courses.course-feedback', $course->id) }}" class="cm-btn-outline"><i class="dripicons-message"></i> Feedback</a>
                                    <form method="POST" action="{{ route('courses.clone', $course->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Clone course"><i class="dripicons-copy"></i> Clone</button>
                                    </form>
                                    <a href="{{ route('courses.edit', $course->id) }}" class="btn btn-sm btn-primary" title="Edit"><i class="dripicons-pencil"></i></a>
                                    <form method="POST" action="{{ route('courses.destroy', $course->id) }}" class="d-inline" onsubmit="return confirm('Delete this course?');">
                                        @csrf
                                        <button class="btn btn-sm btn-danger" title="Delete"><i class="dripicons-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No courses found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var toggle = document.getElementById('cm-dnd-toggle');
    var hint = document.getElementById('cm-dnd-hint');
    var body = document.getElementById('cm-courses-body');
    var sortable = null;
    var reorderUrl = @json(route('courses.reorder'));
    var csrf = @json(csrf_token());

    function setDragMode(on) {
        document.querySelectorAll('.cm-dnd-col').forEach(function (el) {
            el.style.display = on ? '' : 'none';
        });
        document.querySelectorAll('.cm-arrows-col').forEach(function (el) {
            el.style.display = on ? 'none' : '';
        });
        if (hint) hint.style.display = on ? '' : 'none';
        if (body) body.classList.toggle('cm-dnd-active', on);

        if (on) {
            if (!sortable && body && window.Sortable) {
                sortable = Sortable.create(body, {
                    animation: 150,
                    handle: '.cm-drag-handle, .cm-dnd-col',
                    ghostClass: 'table-active',
                    filter: 'button, a, input, form',
                    preventOnFilter: false,
                    onEnd: function () {
                        var ids = Array.prototype.map.call(body.querySelectorAll('tr[data-id]'), function (tr) {
                            return tr.getAttribute('data-id');
                        });
                        fetch(reorderUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ids: ids })
                        }).catch(function () {});
                    }
                });
            }
        } else if (sortable) {
            sortable.destroy();
            sortable = null;
        }
    }

    if (toggle) {
        toggle.addEventListener('change', function () {
            setDragMode(toggle.checked);
        });
    }
})();
</script>
<style>
    #cm-courses-body.cm-dnd-active tr { cursor: grab; }
    #cm-courses-body.cm-dnd-active tr:active { cursor: grabbing; }
    .cm-drag-handle { font-size: 16px; user-select: none; }
</style>
@endsection
