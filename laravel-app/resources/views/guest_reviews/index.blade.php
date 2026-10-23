@extends('layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><h4>Guest Reviews</h4></div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Guest</th>
                                    <th>Country</th>
                                    <th>Phone</th>
                                    <th>Scores</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td>{{ $row->created_at }}</td>
                                        <td>{{ $row->guest_name ?: 'Guest' }}</td>
                                        <td>{{ $row->country }}</td>
                                        <td>{{ $row->phone }}</td>
                                        <td>{{ $row->cleanliness }}/{{ $row->on_time }}/{{ $row->service }}/{{ $row->food_quality }}</td>
                                        <td>{{ $row->comment }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6">No reviews yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <p class="text-muted">Scores are cleanliness, on time, service, and food. Phone numbers stay on this page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
