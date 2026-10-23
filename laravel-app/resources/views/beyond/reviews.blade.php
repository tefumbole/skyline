@extends('beyond.layout')
@section('title', 'Reviews · Skyline Hotel')
@section('meta_description', 'Rate your stay and meal at Skyline Hotel.')

@push('head')
<style>
    .review-score input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .review-score span {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.75rem; height: 2.75rem; border-radius: 999px;
        border: 1px solid rgba(197,160,89,.7); color: #C5A059; font-weight: 700;
    }
    .review-score input:checked + span,
    .review-score input:focus + span { background: #C5A059; color: #0A0A0A; }
    details > summary::-webkit-details-marker { display: none; }
</style>
@endpush

@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-12 px-4">
    <div class="max-w-xl mx-auto text-center">
        <p class="text-[11px] tracking-[0.28em] uppercase text-brand-gold m-0">Skyline Hotel</p>
        <h1 class="font-serif text-3xl sm:text-4xl text-white mt-3">How was your visit?</h1>
        <p class="text-5xl font-serif text-[#C5A059] mt-6 mb-1">
            {{ $summary['count'] ? number_format((float) $summary['average'], 1) : '—' }}<span class="text-2xl text-white/50">/5</span>
        </p>
        <p class="text-white/60 text-sm mt-2">
            @if ($summary['count'])
                {{ $summary['count'] }} {{ $summary['count'] === 1 ? 'review' : 'reviews' }}
            @else
                No reviews yet. Be the first.
            @endif
        </p>

        @if ($summary['rows']->isNotEmpty())
            <div class="mt-8 space-y-3 text-left">
                @foreach ($summary['rows']->take(8) as $review)
                    <article class="rounded-xl border border-white/10 bg-white/[0.04] p-4">
                        <div class="flex justify-between gap-3 text-sm">
                            <strong>{{ $review->guest_name ?: 'Guest' }}</strong>
                            <span class="text-brand-gold">{{ $review->score }} / 5</span>
                        </div>
                        @if ($review->comment)
                            <p class="text-white/75 text-sm mt-2 mb-0">{{ $review->comment }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        @if ($saved)
            <div class="mt-8 rounded-2xl border border-brand-gold/40 bg-white/5 p-6 text-center">
                <h2 class="text-xl font-bold text-brand-gold m-0">Thank you</h2>
                <p class="text-white/80 mt-2 mb-0">Your review is on the page.</p>
                <a href="{{ url('/') }}" class="inline-block mt-5 text-sm font-semibold text-brand-gold underline">Back to Skyline Hotel</a>
            </div>
        @else
            @if (session('success'))
                <div class="mt-6 bg-green-950/40 border border-green-400/40 text-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mt-6 bg-red-950/40 border border-red-400/40 text-red-200 rounded-xl px-4 py-3 text-sm">
                    <ul class="list-disc pl-5 mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @if (!empty($verifyStep))
                <div class="mt-8 rounded-2xl border border-brand-gold/40 bg-white/5 p-6">
                    <h2 class="text-lg font-bold text-brand-gold m-0">Confirm your WhatsApp</h2>
                    <p class="text-sm text-white/70 mt-2">Enter the 6-digit code sent to {{ $maskedPhone }}.</p>
                    <form method="POST" action="{{ route('beyond.reviews.verify') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="text" name="otp" maxlength="6" required inputmode="numeric" autocomplete="one-time-code"
                               class="w-full rounded-md bg-black border border-white/15 px-3 py-3 text-center text-2xl tracking-widest font-bold text-white" placeholder="••••••">
                        <button type="submit" class="w-full bg-brand-gold text-[#0A0A0A] font-bold py-3 rounded-md">Confirm review</button>
                    </form>
                    <form method="POST" action="{{ route('beyond.reviews.resend') }}" class="mt-3 text-center">
                        @csrf
                        <button type="submit" class="text-sm text-brand-gold underline">Send the code again</button>
                    </form>
                </div>
            @endif

            <details class="mt-8" @if($errors->any() && empty($verifyStep)) open @endif>
                <summary class="list-none cursor-pointer inline-flex items-center justify-center bg-brand-gold text-[#0A0A0A] font-bold px-6 py-3 rounded-full">Add a review</summary>
                <form method="POST" action="{{ route('beyond.reviews.store') }}" id="guest-review-form" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="reference" value="{{ old('reference', $reference) }}">
                    <input type="hidden" name="signature" id="review-signature" value="{{ old('signature') }}">

                    @foreach ([
                        'cleanliness' => 'Cleanliness',
                        'on_time' => 'On time',
                        'service' => 'Service',
                        'food_quality' => 'Food',
                    ] as $field => $label)
                        <fieldset class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <legend class="px-1 text-sm font-semibold text-brand-gold">{{ $label }}</legend>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @for ($score = 1; $score <= 5; $score++)
                                    <label class="review-score cursor-pointer">
                                        <input type="radio" name="{{ $field }}" value="{{ $score }}" @if((int) old($field) === $score) checked @endif required>
                                        <span>{{ $score }}</span>
                                    </label>
                                @endfor
                            </div>
                            <p class="text-[11px] text-white/45 mt-2 mb-0">1 is low · 5 is high</p>
                        </fieldset>
                    @endforeach

                    <div>
                        <label class="text-sm font-semibold text-white/80">Name</label>
                        <input name="guest_name" value="{{ old('guest_name') }}" maxlength="80" class="w-full mt-1 rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-white/80">Country</label>
                        <select name="country" required class="w-full mt-1 rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                            <option value="">Select a country</option>
                            @foreach(array_merge($priorityCountries, $otherCountries) as $country)
                                <option value="{{ $country }}" @if(old('country', 'Cameroon') === $country) selected @endif>{{ $country }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-white/80">WhatsApp number</label>
                        <div class="mt-1 grid grid-cols-1 sm:grid-cols-[10rem_1fr] gap-2">
                            <select name="country_code" required class="rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                                @foreach($countryCodes as $code => $label)
                                    <option value="{{ $code }}" @if(old('country_code', '+237') === $code) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="tel" name="phone" value="{{ old('phone') }}" required pattern="[0-9]{6,15}" class="rounded-md bg-black border border-white/15 px-3 py-2 text-white" placeholder="6…">
                        </div>
                        <p class="text-[11px] text-white/45 mt-2 mb-0">We send a code to this number. It is not shown on the public page.</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-white/80">Comment</label>
                        <textarea name="comment" rows="3" maxlength="1000" class="w-full mt-1 rounded-md bg-black border border-white/15 px-3 py-2 text-white">{{ old('comment') }}</textarea>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-white/80">Signature</label>
                        <button type="button" id="review-signature-open" class="mt-2 inline-flex items-center justify-center bg-white/10 border border-brand-gold/50 text-brand-gold font-semibold px-5 py-3 rounded-md">Draw your signature</button>
                        <p id="review-signature-status" class="text-sm text-green-400 mt-2 mb-0 hidden">Signature added</p>
                        <img id="review-signature-preview" alt="" class="hidden max-h-20 mt-3 rounded bg-white border border-white/10">
                    </div>
                    <button type="submit" class="w-full bg-brand-gold text-[#0A0A0A] font-bold py-3 rounded-md">Submit review</button>
                </form>
            </details>
        @endif
    </div>
</div>

<div id="review-signature-modal" class="hidden fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80">
    <div class="bg-[#121212] border border-brand-gold/40 rounded-xl max-w-lg w-full p-5 text-white">
        <h3 class="font-bold text-lg mb-2 text-brand-gold">Signature</h3>
        <p class="text-sm text-white/60 mb-3">Sign in the box, then save it.</p>
        <canvas id="review-signature-pad" class="w-full rounded-md bg-white border border-brand-gold/40" style="height:180px;touch-action:none;"></canvas>
        <div class="mt-3 flex flex-wrap gap-2 justify-end">
            <button type="button" id="review-signature-clear" class="text-sm text-white/70 underline px-3 py-2">Clear</button>
            <button type="button" id="review-signature-close" class="px-4 py-2 text-sm text-white/80">Close</button>
            <button type="button" id="review-signature-save" class="bg-brand-gold text-[#0A0A0A] font-semibold px-4 py-2 rounded-md text-sm">Save signature</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
(function () {
    var canvas = document.getElementById('review-signature-pad');
    var modal = document.getElementById('review-signature-modal');
    var openBtn = document.getElementById('review-signature-open');
    var closeBtn = document.getElementById('review-signature-close');
    var clearBtn = document.getElementById('review-signature-clear');
    var saveBtn = document.getElementById('review-signature-save');
    var input = document.getElementById('review-signature');
    var preview = document.getElementById('review-signature-preview');
    var status = document.getElementById('review-signature-status');
    var form = document.getElementById('guest-review-form');
    if (!canvas || !modal || typeof SignaturePad === 'undefined') return;
    var pad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'rgb(12,24,48)' });
    function resize() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var data = pad.isEmpty() ? null : pad.toData();
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = 180 * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
        if (data) pad.fromData(data);
    }
    function showSigned(dataUrl) {
        input.value = dataUrl || '';
        if (dataUrl) {
            preview.src = dataUrl;
            preview.classList.remove('hidden');
            status.classList.remove('hidden');
        }
    }
    if (openBtn) openBtn.addEventListener('click', function () { modal.classList.remove('hidden'); setTimeout(resize, 30); });
    if (closeBtn) closeBtn.addEventListener('click', function () { modal.classList.add('hidden'); });
    if (clearBtn) clearBtn.addEventListener('click', function () { pad.clear(); });
    if (saveBtn) saveBtn.addEventListener('click', function () {
        if (pad.isEmpty()) { alert('Please sign the review.'); return; }
        showSigned(pad.toDataURL('image/png'));
        modal.classList.add('hidden');
    });
    if (form) form.addEventListener('submit', function (e) {
        if (!input.value) { e.preventDefault(); alert('Please sign the review.'); modal.classList.remove('hidden'); setTimeout(resize, 30); }
    });
    if (input.value) showSigned(input.value);
})();
</script>
@endpush
