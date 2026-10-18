@extends('restaurant.layout')
@section('title', 'Review '.$sale->reference_no)
@section('content')
    <h1>Review {{ $sale->reference_no }}</h1>
    @if($existing)
        <div class="card">
            <p>Thank you, {{ $existing->guest_name }} from {{ $existing->country }}.</p>
            <p>Delivery {{ $existing->delivery_time }}/5 · Food {{ $existing->food_quality }}/5 · Environment {{ $existing->environment }}/5 · Service {{ $existing->services }}/5</p>
        </div>
    @else
        <form class="card" method="post" action="{{ route('restaurant.review.store', [$sale->id, $order->token]) }}">
            @csrf
            <label>Name</label>
            <input name="guest_name" required value="{{ old('guest_name', optional($sale->customer)->name) }}">
            <label>Country</label>
            <input name="country" required value="{{ old('country', optional($sale->customer)->country) }}">
            @foreach(['delivery_time' => 'Order Delivery Time', 'food_quality' => 'Food Quality', 'environment' => 'Environment', 'services' => 'Services'] as $field => $label)
                <label>{{ $label }} (1–5)</label>
                <select name="{{ $field }}" required>
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            @endforeach
            <label>Comment</label>
            <textarea name="comment" rows="3"></textarea>
            <p style="margin-top:14px;"><button type="submit">Submit review</button></p>
        </form>
    @endif
@endsection
