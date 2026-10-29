@extends('layout.main')
@section('content')
<section class="container-fluid" style="color:#1f2a44;">
    <div style="background:#fff;border:1px solid #e6ebf3;border-radius:16px;padding:24px;">
        <p style="letter-spacing:.16em;text-transform:uppercase;color:#8a7424;font-size:12px;font-weight:700;">System test</p>
        <h1 style="color:#0c1830;margin:0;">{{ $report->tester_name }}</h1>
        <p style="color:#6f7b91;">{{ $report->created_at }} · <a href="{{ route('admin.help') }}">Back to Help</a></p>
        @php
            $working = 0;
            $issues = 0;
            foreach ($items as $row) {
                if (($row['result'] ?? '') === 'pass') $working++;
                if (($row['result'] ?? '') === 'fail') $issues++;
            }
        @endphp
        <p><strong>{{ $working }}</strong> working · <strong>{{ $issues }}</strong> with an issue</p>
        @foreach($items as $key => $row)
            @if(($row['result'] ?? '') === 'fail')
                <div style="border-top:1px solid #eef2f7;padding:10px 0;">
                    <strong>{{ $titles[$key] ?? $key }}</strong>
                    <div style="color:#b42318;">Has an issue</div>
                    @if(!empty($row['note']))<div>{{ $row['note'] }}</div>@endif
                </div>
            @endif
        @endforeach
        <h2 style="color:#0c1830;font-size:20px;">Working</h2>
        @foreach($items as $key => $row)
            @if(($row['result'] ?? '') === 'pass')
                <div style="border-top:1px solid #eef2f7;padding:8px 0;">{{ $titles[$key] ?? $key }}</div>
            @endif
        @endforeach
    </div>
</section>
@endsection
