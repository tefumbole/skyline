@extends('layout.main')
@section('content')
    <section class="container-fluid">
        <h3>Orders help</h3>
        @if(!$topic)
            <p>This is the full guide. Open Help from Coffee &amp; Tea, Restaurant, Bar, or Online orders to see only that menu.</p>
        @endif
        @if(!$topic || $topic === 'restaurant-coffee')
            <h4>Coffee &amp; Tea</h4>
            <p>Coffee and tea lines wait here until someone accepts them. Accept does not need a time. Reject takes the line off the bill. When the last active item is delivered, the guest review is sent once.</p>
        @endif
        @if(!$topic || $topic === 'restaurant')
            <h4>Restaurant</h4>
            <p>Food from a restaurant or food category lands here. Awaiting shows Accept and Reject only. Mark ready when it can leave the kitchen, then Delivered records the time on this menu’s log.</p>
        @endif
        @if(!$topic || $topic === 'restaurant-bar')
            <h4>Bar</h4>
            <p>Bar and drinks lines are prepared here. They stay on the same sale as the food. Rejecting an item that is still awaiting restores its stock and lowers the amount due.</p>
        @endif
        @if(!$topic || $topic === 'orders')
            <h4>Online orders</h4>
            <p>A website checkout uses this menu. The line keeps food or drinks as its department, and the lane is online. Guest tracking and Pay QR stay on the signed links.</p>
        @endif
        @if($topic)
            <p><a href="{{ route('restaurant.help') }}">Open the full guide</a></p>
        @endif
    </section>
    <script>
        (function () {
            var map = {
                '#restaurant-coffee': 'restaurant-coffee',
                '#restaurant': 'restaurant',
                '#restaurant-bar': 'restaurant-bar',
                '#orders': 'orders'
            };
            if (!location.hash || !map[location.hash]) return;
            if (new URLSearchParams(location.search).get('topic')) return;
            location.replace(location.pathname + '?topic=' + map[location.hash]);
        })();
    </script>
@endsection
