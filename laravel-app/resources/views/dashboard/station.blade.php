@extends('layout.main')
@section('content')
@php
    $board = $board ?? ['role' => 'restaurant', 'date' => '', 'orders' => 0, 'cook_seconds' => 0, 'cook_count' => 0, 'awaiting' => 0, 'accepted' => 0, 'ready' => 0, 'delivered' => 0];
    $role = $board['role'] ?? 'restaurant';
    $average = ((int) ($board['cook_count'] ?? 0)) > 0
        ? \App\Support\StationDashboard::clock(intdiv((int) $board['cook_seconds'], (int) $board['cook_count']))
        : '—';
    if ($role === 'bar') {
        $kicker = 'Bar';
        $title = 'Today behind the bar';
        $ordersLabel = 'Drink orders';
        $ordersHint = 'Orders that reached the bar today';
        $timeLabel = 'Time preparing';
        $timeHint = 'From accept to ready';
        $ordersUrl = route('restaurant.orders', ['lane' => 'bar', 'tab' => 'awaiting']);
        $acceptedUrl = route('restaurant.orders', ['lane' => 'bar', 'tab' => 'accepted']);
    } elseif ($role === 'waiter') {
        $kicker = 'Floor';
        $title = 'Today on your round';
        $ordersLabel = 'Orders you placed';
        $ordersHint = 'Bills you opened today';
        $timeLabel = 'Time to deliver';
        $timeHint = 'From ready to delivered';
        $ordersUrl = route('restaurant.ready');
        $acceptedUrl = route('restaurant.ready');
    } else {
        $kicker = 'Restaurant';
        $title = 'Today in the kitchen';
        $ordersLabel = 'Orders placed';
        $ordersHint = 'Food orders that reached the kitchen today';
        $timeLabel = 'Time cooking';
        $timeHint = 'From accept to ready';
        $ordersUrl = route('restaurant.orders', ['lane' => 'restaurant', 'tab' => 'awaiting']);
        $acceptedUrl = route('restaurant.orders', ['lane' => 'restaurant', 'tab' => 'accepted']);
    }
@endphp
<section class="station-board">
    <p class="station-kicker">{{ $kicker }}</p>
    <h1>{{ $title }}</h1>
    <p class="station-date">{{ $board['date'] ?? '' }}</p>
    <div class="station-grid">
        <a href="{{ $ordersUrl }}">
            <span>{{ $ordersLabel }}</span>
            <strong>{{ number_format((int) ($board['orders'] ?? 0)) }}</strong>
            <em>{{ $ordersHint }}</em>
        </a>
        <div>
            <span>{{ $timeLabel }}</span>
            <strong>{{ \App\Support\StationDashboard::clock($board['cook_seconds'] ?? 0) }}</strong>
            <em>{{ $timeHint }} · average {{ $average }}</em>
        </div>
        @if($role === 'waiter')
            <a href="{{ route('restaurant.ready') }}">
                <span>Waiting for you</span>
                <strong>{{ number_format((int) ($board['ready'] ?? 0)) }}</strong>
                <em>Ready items you still have to deliver</em>
            </a>
            <div>
                <span>Delivered</span>
                <strong>{{ number_format((int) ($board['delivered'] ?? 0)) }}</strong>
                <em>Orders you delivered today</em>
            </div>
        @else
            <a href="{{ $ordersUrl }}">
                <span>Awaiting</span>
                <strong>{{ number_format((int) ($board['awaiting'] ?? 0)) }}</strong>
                <em>Waiting to be accepted</em>
            </a>
            <a href="{{ $acceptedUrl }}">
                <span>Accepted</span>
                <strong>{{ number_format((int) ($board['accepted'] ?? 0)) }}</strong>
                <em>Being prepared now</em>
            </a>
        @endif
    </div>
</section>
<style>
    .station-board { max-width: 980px; margin: 8px auto 28px; }
    .station-kicker { margin: 0; color: #8a6230; font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
    .station-board h1 { margin: 6px 0 0; color: #0c1830; font-size: 36px; font-weight: 800; letter-spacing: -.03em; }
    .station-date { margin: 6px 0 0; color: #5c6b82; }
    .station-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 18px; }
    .station-grid a, .station-grid div {
        display: block; background: #0c1830; color: #fff; border-radius: 18px; padding: 18px 18px 16px;
        text-decoration: none !important; box-shadow: 0 14px 32px rgba(12, 24, 48, .16);
    }
    .station-grid a:hover { color: #fff; }
    .station-grid span { display: block; color: #e1b368; font-size: 13px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .station-grid strong { display: block; margin-top: 6px; font-size: 42px; line-height: 1; font-weight: 800; letter-spacing: -.04em; }
    .station-grid em { display: block; margin-top: 8px; color: rgba(255,255,255,.72); font-style: normal; font-size: 14px; }
    @media (max-width: 700px) {
        .station-board h1 { font-size: 28px; }
        .station-grid { grid-template-columns: 1fr; }
        .station-grid strong { font-size: 36px; }
    }
</style>
@endsection
