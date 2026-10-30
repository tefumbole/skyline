@php $tab = isset($tab) ? $tab : ''; @endphp
<div class="pay-tabs" style="display:flex;flex-wrap:wrap;gap:8px;margin:0 0 16px">
    <a class="btn {{ $tab === 'payment' ? 'btn-primary' : 'btn-default' }}" href="{{ route('payment.index') }}">Payment</a>
    <a class="btn {{ $tab === 'payout' ? 'btn-primary' : 'btn-default' }}" href="{{ route('payout.index') }}">Payout</a>
    <a class="btn {{ $tab === 'request' ? 'btn-primary' : 'btn-default' }}" href="{{ route('payout.request') }}">Request for Payment</a>
    <a class="btn {{ $tab === 'donations' ? 'btn-primary' : 'btn-default' }}" href="{{ route('momo.payments') }}">Mobile Payments</a>
    <a class="btn {{ $tab === 'deposits' ? 'btn-primary' : 'btn-default' }}" href="{{ route('deposit.index') }}">Deposits</a>
</div>
