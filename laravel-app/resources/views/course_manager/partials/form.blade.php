@php
    $c = $course;
    $curriculumLines = '';
    if ($c) {
        $sections = $c->sections;
        $curriculumLines = collect($sections)->pluck('title')->filter()->implode("\n");
    }
@endphp
<div class="row">
    <div class="col-md-8 form-group">
        <label>Course Name *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', optional($c)->name) }}">
    </div>
    <div class="col-md-4 form-group">
        <label>Category</label>
        <input type="text" name="category" class="form-control" value="{{ old('category', optional($c)->category ?: 'Training') }}">
    </div>
</div>
<div class="form-group">
    <label>Description</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', optional($c)->description) }}</textarea>
</div>
<div class="row">
    <div class="col-md-3 form-group">
        <label>Price (USD)</label>
        <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', optional($c)->price ?: 0) }}">
    </div>
    <div class="col-md-3 form-group">
        <label>Duration</label>
        <input type="text" name="duration" class="form-control" placeholder="8-10 weeks" value="{{ old('duration', optional($c)->duration) }}">
    </div>
    <div class="col-md-3 form-group">
        <label>Delivery Mode</label>
        <input type="text" name="delivery_mode" class="form-control" placeholder="Online / On-site" value="{{ old('delivery_mode', optional($c)->delivery_mode) }}">
    </div>
    <div class="col-md-3 form-group">
        <label>Status</label>
        <select name="status" class="form-control">
            @foreach(['active','draft','archived'] as $st)
                <option value="{{ $st }}" {{ old('status', optional($c)->status ?: 'active') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-4 form-group">
        <label>Icon key</label>
        <input type="text" name="icon" class="form-control" placeholder="Brain / Cloud / Shield" value="{{ old('icon', optional($c)->icon ?: 'Briefcase') }}">
    </div>
    <div class="col-md-4 form-group">
        <label>Color</label>
        <input type="color" name="color" class="form-control" style="max-width:80px;padding:2px;" value="{{ old('color', optional($c)->color ?: '#003D82') }}">
    </div>
</div>
<div class="form-group">
    <label>Curriculum sections (one title per line)</label>
    <textarea name="curriculum" class="form-control" rows="6" placeholder="Module 1: Foundations&#10;Module 2: Practice">{{ old('curriculum', $curriculumLines) }}</textarea>
</div>
