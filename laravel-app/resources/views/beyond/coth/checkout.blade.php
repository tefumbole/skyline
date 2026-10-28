{{--
  Back-compat shim. The old single-page checkout is now split into
  verify.blade.php (Stage 2) and payment.blade.php (Stage 3). The
  controller's checkout() action issues a 302 to the correct stage,
  so this view should never render. If it does, show a plain link so
  the customer can still move forward.
--}}
@extends('beyond.layout')
@section('title', 'Continue · '.$reservation->reference)
@section('body_class', 'bg-[#0A0F2A]')
@section('content')
<div class="text-white min-h-[70vh] py-10 px-4 text-center">
    <p class="text-brand-gold uppercase tracking-[0.22em] text-xs">Chill on the Hill</p>
    <h1 class="font-serif text-3xl mt-2 mb-4">Continue your reservation</h1>
    @php $next = $reservation->phone_verified_at ? route('coth.payment', $reservation->public_token) : route('coth.verify', $reservation->public_token); @endphp
    <a class="inline-block bg-brand-gold text-[#0A0A0A] font-bold uppercase tracking-[0.12em] rounded-full px-6 py-3" href="{{ $next }}">
        Continue &rarr;
    </a>
</div>
@endsection
