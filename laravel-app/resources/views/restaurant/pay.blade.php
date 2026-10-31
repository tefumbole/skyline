@extends('restaurant.layout')
@section('title', 'Pay '.$sale->reference_no)
@section('content')
    <h1>Pay {{ $sale->reference_no }}</h1>
    <div class="card">
        <p>{{ optional($sale->customer)->name }}</p>
        <p>Invoice {{ number_format((float) $sale->grand_total, 0) }} · Paid {{ number_format((float) $sale->paid_amount, 0) }} · Due {{ number_format((float) $due, 0) }} XAF</p>
        @if(!empty($empty))
            <p><strong>There is nothing left to pay on this order.</strong></p>
        @elseif($paid)
            <p><strong>This invoice is paid.</strong> Scanning the code again will not charge you a second time.</p>
        @else
            <p class="muted">Choose Mobile Money or card. Opening this page does not take payment. Cash is collected by the cashier.</p>
            <form method="post" action="{{ route('restaurant.pay.start', [$sale->id, \App\Support\SalePayQr::token($sale)]) }}">
                @csrf
                <button type="submit">Pay with MoMo or Card</button>
            </form>
        @endif
    </div>
@endsection
