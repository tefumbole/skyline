@extends('layout.main')
@section('content')
<section class="container-fluid" style="color:#1f2a44; max-width: 880px;">
    <div style="background:#fff;border:1px solid #e6ebf3;border-radius:16px;padding:24px;">
        <p style="letter-spacing:.16em;text-transform:uppercase;color:#8a7424;font-size:12px;font-weight:700;margin:0;">Site test</p>
        <h1 style="color:#0c1830;margin:8px 0 0;">{{ $report->tester_name }}</h1>
        <p style="color:#6f7b91;margin:6px 0 0;">
            {{ $report->phone }}
            · {{ $report->updated_at }}
            · {{ $report->status === 'submitted' ? 'Submitted' : 'In progress' }}
            · <a href="{{ route('admin.help', ['tab' => 'results']) }}">Site Test Results</a>
        </p>
        <p style="font-size:56px;line-height:1;font-weight:700;color:#0c1830;margin:18px 0 4px;">{{ $summary['score'] }}<span style="font-size:24px;">%</span></p>
        @if($summary['complete'])
            <p style="margin:0 0 18px;">Every check was tested. {{ $summary['passed'] }} working, {{ $summary['failed'] }} with an issue.</p>
        @else
            <p style="margin:0 0 18px;">This is not the final site score. {{ count($summary['open']) }} checks are still open. {{ $summary['passed'] }} working, {{ $summary['failed'] }} with an issue, {{ $summary['answered'] }} of {{ $summary['total'] }} tested.</p>
        @endif

        <h2 style="color:#0c1830;font-size:20px;">What is wrong</h2>
        @if(count($summary['issues']) === 0)
            <p style="color:#6f7b91;">Nothing was marked as an issue.</p>
        @endif
        @foreach($summary['issues'] as $issue)
            <div style="border-top:1px solid #eef2f7;padding:12px 0;">
                <strong>{{ $issue['label'] }}</strong>
                <div style="color:#b42318;">Has an issue</div>
                @if($issue['note'] !== '')
                    <div>{{ $issue['note'] }}</div>
                @endif
            </div>
        @endforeach

        <h2 style="color:#0c1830;font-size:20px;margin-top:22px;">All comments</h2>
        @if(count($summary['comments']) === 0)
            <p style="color:#6f7b91;">No comments.</p>
        @endif
        @foreach($summary['comments'] as $comment)
            <div style="border-top:1px solid #eef2f7;padding:12px 0;">
                <strong>{{ $comment['label'] }}</strong>
                <div>{{ $comment['note'] }}</div>
            </div>
        @endforeach

        @if(! $summary['complete'])
            <h2 style="color:#0c1830;font-size:20px;margin-top:22px;">Still open</h2>
            @foreach($summary['open'] as $open)
                <div style="border-top:1px solid #eef2f7;padding:8px 0;">{{ $open['label'] }}</div>
            @endforeach
        @endif
    </div>
</section>
@endsection
