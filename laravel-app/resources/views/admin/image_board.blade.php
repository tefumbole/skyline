@extends('layout.main')

@section('content')
<style>
    .board-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; }
    .board-card { border: 1px solid #e6d3a1; border-radius: 14px; overflow: hidden; background: #fffdf8; }
    .board-card img { width: 100%; height: 160px; object-fit: cover; display: block; background: #f6f1e7; }
    .board-card .meta { padding: 8px 10px 12px; }
    .board-card p { margin: 0 0 8px; font-size: 13px; color: #3f2a12; word-break: break-word; }
</style>
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <h4 class="mb-1"><i class="dripicons-photo-group"></i> {{ $title }}</h4>
                    <a href="{{ $live }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">View live page</a>
                </div>
                <p class="text-muted">Upload images for the public {{ strtolower($title) }}. They appear on the live page as soon as you upload them.</p>

                @if(session('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ $board === 'menu' ? route('admin.menu.store') : route('admin.gallery.store') }}" enctype="multipart/form-data" class="mb-4" style="max-width:640px;">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Images</label>
                        <input type="file" name="images[]" class="form-control-file" accept="image/jpeg,image/png,image/gif,image/webp" multiple required>
                        <small class="text-muted">JPG, PNG, GIF, or WEBP. Up to 20 images at a time.</small>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="dripicons-upload"></i> Upload</button>
                </form>

                @if($items->isEmpty())
                    <p class="text-muted">No images yet.</p>
                @else
                    <div class="board-grid">
                        @foreach($items as $item)
                            <article class="board-card">
                                <img src="{{ $item->fileUrl() }}" alt="{{ $item->title }}">
                                <div class="meta">
                                    <p>{{ $item->title ?: 'Image' }}</p>
                                    <form method="POST" action="{{ route('admin.media.delete', $item->id) }}" onsubmit="return confirm('Remove this image?');">
                                        @csrf
                                        <input type="hidden" name="board" value="{{ $board }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
