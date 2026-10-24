@extends('restaurant.layout')
@section('title', 'Order confirmed')
@section('content')
    <h1>Order {{ $sale->reference_no }}</h1>
    <div class="card">
        <p><strong>{{ $sale->customer->name ?? 'Guest' }}</strong>
            @if($order) · {{ $order->serviceLabel() }} · {{ $order->source === 'frontend' ? 'Frontend' : 'POS' }} @endif
        </p>
        <p>Total {{ number_format((float) $sale->grand_total, 0) }} · Paid {{ number_format((float) $sale->paid_amount, 0) }}
            · {{ (int) $sale->payment_status === 4 ? 'Paid' : ((int) $sale->payment_status === 3 ? 'Partial' : 'Unpaid') }}</p>
        @if($order && $order->customer_comment && empty($public))
            <p class="muted">Note: {{ $order->customer_comment }}</p>
        @endif
        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Department</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($lines as $line)
                @php
                    $prep = $order ? $order->items->firstWhere('product_sale_id', $line->id) : null;
                    $product = \App\Product::find($line->product_id);
                @endphp
                <tr>
                    <td>{{ $product->name ?? ('#'.$line->product_id) }}
                        @if($prep && $prep->customer_comment)<div class="muted">{{ $prep->customer_comment }}</div>@endif
                    </td>
                    <td>{{ $line->qty }}</td>
                    <td>{{ $prep ? ucfirst($prep->department) : '—' }}</td>
                    <td>{{ $prep ? ucfirst($prep->status) : 'On the bill' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if($order)
            <p style="margin-top:14px;"><a class="btn" href="{{ $order->trackUrl() }}">Track this order</a></p>
        @endif
    </div>
    <div class="card">
        <h2>{{ !empty($public) ? 'Checkout' : 'Invoice' }}</h2>
        @if(!empty($public) && $order)
            <form method="post" action="{{ route('restaurant.public.note', [$sale->id, $order->token]) }}" style="margin-bottom:16px;">
                @csrf
                <label for="customer_comment">Note, optional</label>
                <textarea id="customer_comment" name="customer_comment" rows="3" maxlength="500" placeholder="Anything the kitchen or bar should know">{{ old('customer_comment', $order->customer_comment) }}</textarea>
                <p class="row" style="margin-top:10px;">
                    <button type="submit" name="next" value="stay" class="btn ghost">Save note</button>
                    <button type="submit" name="next" value="pay">Pay with MoMo or Card</button>
                </p>
            </form>
        @endif
        <p class="row">
            <a class="btn ghost" href="{{ route('sale.invoice', $sale->id) }}">View Invoice</a>
            <a class="btn ghost" href="{{ route('sale.invoice', $sale->id) }}" target="_blank">Print Invoice</a>
        </p>
        @if(empty($public) && auth()->check())
            <form method="post" action="{{ route('sale.sendmail') }}" style="margin-top:10px;">
                @csrf
                <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                <button class="btn ghost" type="submit">Send Invoice</button>
            </form>
            <p style="margin-top:10px;"><a class="btn" href="{{ route('payment.index') }}">Pay</a>
                <a class="btn ghost" href="{{ \App\Support\SalePayQr::payUrl($sale) }}">MoMo / Card link</a></p>
            <p class="muted">Printing or sending the invoice does not mark it paid. Pay opens the existing payments screen. Cash is taken there. MoMo and card use the invoice link.</p>
        @endif
    </div>
    @if($order && empty($public) && auth()->check())
        <div class="card">
            <h2>Waitress / waiter</h2>
            <form method="post" action="{{ route('restaurant.waitress', $order->id) }}">
                @csrf
                <select name="waitress_user_id">
                    <option value="">Unassigned</option>
                    @foreach($staff as $person)
                        <option value="{{ $person->id }}" @if((int)$order->waitress_user_id === (int)$person->id) selected @endif>{{ $person->name }}</option>
                    @endforeach
                </select>
                <p style="margin-top:10px;"><button type="submit">Save assignment</button></p>
            </form>
        </div>
    @endif
@endsection
