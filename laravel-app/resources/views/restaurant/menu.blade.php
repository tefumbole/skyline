@extends('restaurant.layout')
@section('title', 'Menu · Skyline Hotel')
@section('content')
    <h1>Menu</h1>
    <p class="muted">One order, one bill. Restaurant dishes go to the kitchen. Bar drinks go to the bar. After you place the order, the tracking page keeps the countdown.</p>
    <form method="post" action="{{ route('restaurant.checkout') }}">
        @csrf
        <input type="hidden" name="checkout_token" value="{{ $token }}">
        @forelse($sections as $section)
            <h2>{{ $section['title'] }}</h2>
            @foreach($section['blocks'] as $block)
                <div class="card">
                    <h2>{{ $block['title'] }}</h2>
                    @forelse($block['products'] as $product)
                        <div class="row" style="align-items:flex-end; margin-bottom:10px;">
                            <div>
                                <strong>{{ $product->name }}</strong>
                                <div class="muted">
                                    @if((float) $product->price > 0)
                                        {{ number_format((float) $product->price, 0) }} XAF
                                    @else
                                        Ask for the price
                                    @endif
                                    @if($product->product_details)
                                        · {{ $product->product_details }}
                                    @endif
                                </div>
                                <input type="hidden" name="product_id[]" value="{{ $product->id }}">
                            </div>
                            <div>
                                <label>Qty</label>
                                <input type="number" name="qty[]" min="0" step="1" value="0">
                            </div>
                            <div>
                                <label>Note for this item</label>
                                <input type="text" name="item_comment[]" maxlength="255" placeholder="Optional">
                            </div>
                        </div>
                    @empty
                        <p class="muted">Nothing in this section yet.</p>
                    @endforelse
                </div>
            @endforeach
        @empty
            <div class="card"><p class="muted">The menu is not loaded yet.</p></div>
        @endforelse
        <div class="card">
            <h2>Your details</h2>
            <div class="row">
                <div>
                    <label>Name</label>
                    <input name="name" required value="{{ old('name') }}">
                </div>
                <div>
                    <label>Phone (WhatsApp)</label>
                    <input name="phone" required value="{{ old('phone') }}">
                </div>
                <div>
                    <label>Country</label>
                    <input name="country" value="{{ old('country', 'Cameroon') }}">
                </div>
            </div>
            <div class="row">
                <div>
                    <label>Service</label>
                    <select name="service_type" required>
                        <option value="dine_in">Dine In</option>
                        <option value="take_away">Take Away</option>
                    </select>
                </div>
                <div>
                    <label>Table (dine in)</label>
                    <select name="restaurant_table_id">
                        <option value="">No table</option>
                        @foreach($tables as $table)
                            <option value="{{ $table->id }}">{{ $table->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label>Comment</label>
            <textarea name="customer_comment" rows="2">{{ old('customer_comment') }}</textarea>
            <p style="margin-top:16px;"><button type="submit">Place order</button></p>
            <p class="muted">You can pay now from the confirmation page, or later. The order still goes to Restaurant or Bar, and the tracking page counts down to the ready time.</p>
        </div>
    </form>
@endsection
