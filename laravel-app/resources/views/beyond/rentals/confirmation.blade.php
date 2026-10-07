@extends('beyond.layout')
@section('title', 'Booking Request Received')
@section('content')
<div class="min-h-screen bg-gray-50 py-16 px-4">
    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-lg border border-gray-100 p-8 text-center">
        <h1 class="text-2xl font-extrabold text-brand-blue mb-2">Request received</h1>
        <p class="text-gray-600 mb-4">
            @if($booking)
                Thank you. Your rental booking request
                <strong>{{ $booking->reference_no }}</strong> is under review.
            @else
                Reference <strong>{{ $reference }}</strong>.
            @endif
        </p>
        <p class="text-sm text-gray-500">We will contact you on WhatsApp with the next steps.</p>
        <a href="{{ url('/rentals') }}" class="inline-block mt-6 text-brand-blue font-semibold hover:underline">Back to Rentals</a>
    </div>
</div>
@endsection
