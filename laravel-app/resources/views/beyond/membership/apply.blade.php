@extends('beyond.layout')
@section('title', 'Become a Member · Skyline Hotel')

@section('content')
<div class="bg-[#0c1830] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-2xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0">Skyline Hotel</p>
        <h1 class="font-serif text-3xl text-white mt-2">Become a Member</h1>
        @if (empty($ready))
            <p class="mt-6 text-white/70">Membership is being prepared. Please try again shortly.</p>
        @else
            <p class="text-white/70 text-sm mt-3">Members receive a 10% discount. The free offer, when it is open, lasts 90 days and has no registration fee. Paid plans are collected at the hotel when staff approve the application.</p>

            @if ($errors->any())
                <div class="mt-6 bg-red-950/40 border border-red-400/40 text-red-200 rounded-xl px-4 py-3 text-sm">
                    <ul class="list-disc pl-5 mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('membership.apply.store') }}" id="member-form" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="id_photo_data" id="id-photo-data">
                <input type="hidden" name="selfie_data" id="selfie-data">
                <input type="hidden" name="signature" id="member-signature" value="{{ old('signature') }}">

                <fieldset class="rounded-xl border border-white/10 p-4 space-y-2">
                    <legend class="px-1 text-sm font-semibold text-brand-gold">Plan</legend>
                    @if ($promo)
                        <label class="flex gap-3 items-start">
                            <input type="radio" name="plan_choice" value="promo" @if(old('plan_choice', 'promo') === 'promo') checked @endif required>
                            <span><strong>{{ $promo->name }}</strong><br><span class="text-sm text-white/60">{{ $promo->description }}</span></span>
                        </label>
                    @endif
                    @foreach ($plans as $plan)
                        <label class="flex gap-3 items-start">
                            <input type="radio" name="plan_choice" value="{{ $plan->id }}" @if(old('plan_choice') == $plan->id) checked @endif required>
                            <span>{{ $plan->name }} — {{ number_format((float) $plan->fee, 0) }} XAF for {{ $plan->duration_months }} {{ $plan->duration_months == 1 ? 'month' : 'months' }}</span>
                        </label>
                    @endforeach
                </fieldset>

                <input name="full_name" value="{{ old('full_name') }}" required placeholder="Full name" class="w-full rounded-md bg-black border border-white/15 px-3 py-2">
                <input name="email" type="email" value="{{ old('email') }}" placeholder="Email, optional" class="w-full rounded-md bg-black border border-white/15 px-3 py-2">
                <input name="phone" value="{{ old('phone') }}" required placeholder="WhatsApp number" class="w-full rounded-md bg-black border border-white/15 px-3 py-2">
                <select name="nationality" required class="w-full rounded-md bg-black border border-white/15 px-3 py-2">
                    <option value="">Nationality</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country }}" @if(old('nationality', 'Cameroon') === $country) selected @endif>{{ $country }}</option>
                    @endforeach
                </select>
                <div class="grid sm:grid-cols-2 gap-3">
                    <select name="id_type" required class="rounded-md bg-black border border-white/15 px-3 py-2">
                        <option value="national_id" @if(old('id_type') === 'national_id') selected @endif>National ID</option>
                        <option value="passport" @if(old('id_type') === 'passport') selected @endif>Passport</option>
                    </select>
                    <input name="id_number" value="{{ old('id_number') }}" required placeholder="ID number" class="rounded-md bg-black border border-white/15 px-3 py-2">
                </div>
                <label class="block text-sm text-white/70">ID expiry
                    <input type="date" name="id_expires_on" value="{{ old('id_expires_on') }}" required class="mt-1 w-full rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                </label>
                <label class="block text-sm text-white/70">Photo of the ID
                    <input type="file" id="id-photo-file" accept="image/*" capture="environment" required class="mt-1 block w-full text-sm">
                </label>
                <label class="block text-sm text-white/70">Selfie
                    <input type="file" id="selfie-file" accept="image/*" capture="user" required class="mt-1 block w-full text-sm">
                </label>

                @if ($agreement)
                    <div class="rounded-xl border border-white/10 p-4">
                        <h2 class="text-sm font-semibold text-brand-gold m-0">{{ $agreement->title }}</h2>
                        <pre class="whitespace-pre-wrap text-sm text-white/75 mt-3 font-sans">{{ $agreement->body }}</pre>
                        <label class="flex gap-2 mt-3 text-sm"><input type="checkbox" name="accept_agreement" value="1" required> I accept this agreement</label>
                    </div>
                @endif

                <div>
                    <button type="button" id="member-signature-open" class="inline-flex bg-white/10 border border-brand-gold/50 text-brand-gold font-semibold px-5 py-3 rounded-md">Sign the agreement</button>
                    <p id="member-signature-status" class="text-sm text-green-400 mt-2 mb-0 hidden">Signature added</p>
                </div>
                <button type="submit" class="w-full bg-brand-gold text-[#0c1830] font-bold py-3 rounded-full">Submit application</button>
            </form>
        @endif
    </div>
</div>

<div id="member-signature-modal" class="hidden fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80">
    <div class="bg-[#121212] border border-brand-gold/40 rounded-xl max-w-lg w-full p-5">
        <canvas id="member-signature-pad" class="w-full rounded-md bg-white" style="height:180px;touch-action:none;"></canvas>
        <div class="mt-3 flex justify-end gap-2">
            <button type="button" id="member-signature-clear" class="text-sm text-white/70 underline">Clear</button>
            <button type="button" id="member-signature-save" class="bg-brand-gold text-[#0c1830] font-semibold px-4 py-2 rounded-md text-sm">Save signature</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
(function () {
    function fileToData(input, hidden) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function () { hidden.value = reader.result; };
            reader.readAsDataURL(file);
        });
    }
    var idFile = document.getElementById('id-photo-file');
    var selfieFile = document.getElementById('selfie-file');
    if (idFile) fileToData(idFile, document.getElementById('id-photo-data'));
    if (selfieFile) fileToData(selfieFile, document.getElementById('selfie-data'));

    var canvas = document.getElementById('member-signature-pad');
    var modal = document.getElementById('member-signature-modal');
    var input = document.getElementById('member-signature');
    var form = document.getElementById('member-form');
    if (!canvas || typeof SignaturePad === 'undefined') return;
    var pad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'rgb(12,24,48)' });
    function resize() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = 180 * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
    }
    document.getElementById('member-signature-open').addEventListener('click', function () {
        modal.classList.remove('hidden');
        setTimeout(resize, 30);
    });
    document.getElementById('member-signature-clear').addEventListener('click', function () { pad.clear(); });
    document.getElementById('member-signature-save').addEventListener('click', function () {
        if (pad.isEmpty()) { alert('Please sign the agreement.'); return; }
        input.value = pad.toDataURL('image/png');
        document.getElementById('member-signature-status').classList.remove('hidden');
        modal.classList.add('hidden');
    });
    if (form) form.addEventListener('submit', function (e) {
        if (!document.getElementById('id-photo-data').value || !document.getElementById('selfie-data').value) {
            e.preventDefault();
            alert('Add a photo of your ID and a selfie.');
            return;
        }
        if (!input.value) {
            e.preventDefault();
            alert('Please sign the agreement.');
            modal.classList.remove('hidden');
            setTimeout(resize, 30);
        }
    });
})();
</script>
@endpush
