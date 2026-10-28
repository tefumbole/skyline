@extends('layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-1"><i class="dripicons-photo-group"></i> Gallery</h4>
                <p class="text-muted">Add a photo, video, or link. Give it a description and file it under an event. It appears on the public Gallery page.</p>

                @if(session('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                @include('site_content.gallery_tab')
            </div>
        </div>
    </div>
</section>
@endsection
