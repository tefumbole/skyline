@extends('layout.main')
@section('content')
<style>
    .role-board { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-top: 18px; }
    .role-stat { display: block; padding: 18px 16px 16px; border-radius: 18px; background: #fff; box-shadow: 0 12px 32px rgba(12,24,48,.06); color: #0c1830; text-decoration: none; }
    .role-stat strong { display: block; font-size: 40px; line-height: 1; letter-spacing: -.04em; }
    .role-stat span { display: block; margin-top: 8px; color: #5c6b82; font-weight: 700; }
    .role-stat.is-link { box-shadow: inset 0 -3px 0 #e1b368, 0 12px 32px rgba(12,24,48,.06); }
    .role-stat.is-link:hover { background: #f3f6fb; color: #0c1830; text-decoration: none; }
</style>
<div class="beyond-dashboard">
    <div class="beyond-dashboard-hero">
        <div>
            <h1>{{ $title }}</h1>
            <p class="welcome-line">
                Welcome back, {{ Auth::user()->name }}
                <span class="beyond-role-badge">{{ $title }}</span>
            </p>
            <p class="welcome-line">{{ $lead }}</p>
        </div>
    </div>
    <div class="role-board">
        @foreach($cards as $card)
            @if($card['url'])
                <a class="role-stat is-link" href="{{ $card['url'] }}">
                    <strong>{{ number_format($card['value']) }}</strong>
                    <span>{{ $card['label'] }}</span>
                </a>
            @else
                <div class="role-stat">
                    <strong>{{ number_format($card['value']) }}</strong>
                    <span>{{ $card['label'] }}</span>
                </div>
            @endif
        @endforeach
    </div>
</div>
@endsection
