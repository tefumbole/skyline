@extends('layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header"><h4>Membership applications</h4></div>
            <div class="card-body">
                @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>ID</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->reference }}</td>
                                <td>{{ $row->full_name }}<div class="text-muted">{{ $row->nationality }}</div></td>
                                <td>{{ $row->phone }}</td>
                                <td>{{ $row->id_type }} {{ $row->id_number }}</td>
                                <td>{{ number_format((float) $row->fee, 0) }} XAF</td>
                                <td>{{ $row->status }}</td>
                                <td>
                                    @if($row->id_photo_path)<a href="{{ url('/public/'.$row->id_photo_path) }}" target="_blank">ID</a>@endif
                                    @if($row->selfie_path)<a href="{{ url('/public/'.$row->selfie_path) }}" target="_blank">Selfie</a>@endif
                                    @if($row->status === 'PENDING')
                                        <form method="POST" action="{{ route('membership.admin.approve', $row->id) }}" style="display:inline;">@csrf<button class="btn btn-sm btn-success" type="submit">Approve</button></form>
                                        <form method="POST" action="{{ route('membership.admin.reject', $row->id) }}" style="display:inline;">@csrf<button class="btn btn-sm btn-danger" type="submit">Reject</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No applications yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
