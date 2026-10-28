@extends('beyond.layout')
@section('title', 'Reserve '.$table->code)
@section('body_class', 'bg-[#0A0A0A]')
@push('head')
<style>
    .coth-card {
        border: 1px solid rgba(197,160,89,.35);
        background: rgba(255,255,255,.03);
        box-shadow: 0 18px 40px rgba(0,0,0,.45);
    }
    .coth-art {
        position: relative;
        background:
            radial-gradient(240px 150px at 50% 38%, rgba(197,160,89,.12), rgba(10,10,10,0) 70%),
            #0c0c0c;
    }
    .coth-art::after {
        content: ''; position: absolute; inset: 0; pointer-events: none; z-index: 1;
        box-shadow: inset 0 0 34px 20px #0c0c0c;
    }
    .coth-art img { position: relative; z-index: 0; }
    .coth-pill {
        border: 1px solid rgba(197,160,89,.9); color: #E8C878;
        background: rgba(10,10,10,.6); letter-spacing: .18em;
        position: relative; z-index: 2;
    }
    .coth-field { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.1); transition: border-color .15s ease; }
    .coth-field:focus-within { border-color: #E8C878; }
    .coth-pay { border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.02); transition: border-color .15s ease, background .15s ease; }
    .coth-pay.is-active { border-color: #E8C878; background: rgba(197,160,89,.10); }
    .coth-form input[type=radio], .coth-form input[type=checkbox] { accent-color: #C5A059; }
    .coth-src { color: #34d399; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; }
    .coth-hidden { display: none; }
    .coth-spin { width: 14px; height: 14px; border: 2px solid rgba(10,10,10,.3); border-top-color: #0A0A0A; border-radius: 9999px; display: inline-block; animation: coth-spin 0.8s linear infinite; vertical-align: -2px; }
    @keyframes coth-spin { to { transform: rotate(360deg); } }
</style>
@endpush
@section('content')
@php
    $artFor = function ($table) {
        if ($table->isCouch()) { return asset('public/images/coth/tables/couch.png'); }
        $cap = (int) $table->capacity;
        $name = in_array($cap, [2, 3, 4, 6, 8, 10], true) ? $cap : 4;
        return asset('public/images/coth/tables/'.$name.'.png');
    };
@endphp
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('coth.tables', ['date' => $date, 'section' => $table->section->code]) }}" class="text-xs uppercase tracking-[0.18em] text-brand-gold">&larr; Back to tables</a>

        <div class="coth-card rounded-3xl overflow-hidden mt-3">
            <div class="coth-art px-4 pt-5 pb-3 flex items-center justify-center">
                <img src="{{ $artFor($table) }}" alt="{{ $table->capacity }} seat table" class="w-full max-w-[320px] h-44 object-contain" loading="lazy">
                <span class="coth-pill absolute bottom-3 left-1/2 -translate-x-1/2 text-[11px] font-semibold uppercase rounded-full px-4 py-1">
                    {{ $table->isCouch() ? 'Couch' : $table->capacity.' Seats' }}
                </span>
            </div>
            <div class="p-6">
                <p class="text-[11px] uppercase tracking-[0.22em] text-brand-gold m-0">Your table</p>
                <h1 class="font-serif text-3xl mt-1 mb-0">{{ $table->code }}</h1>
                <p class="text-white/70 mt-1">{{ $table->section->name }} · {{ $table->capacity }} seats{{ $table->isCouch() ? ' · lounge couch' : '' }}</p>
                <p class="text-lg font-semibold mt-1">{{ \Carbon\Carbon::parse($date)->format('l, j F Y') }} <span class="text-white/60 text-base font-normal">· one day</span></p>

                <div class="mt-5 space-y-1 text-sm">
                    @if ($quote['discounted'])
                        <div class="flex justify-between text-white/55"><span>Listed price</span><span class="line-through">{{ number_format($quote['list_price']) }} FCFA</span></div>
                        <div class="flex justify-between text-emerald-300"><span>Discount (20%)</span><span>−{{ number_format($quote['discount_amount']) }} FCFA</span></div>
                    @endif
                    <div class="flex justify-between font-semibold"><span>Final price</span><span class="text-brand-gold">{{ number_format($quote['final_price']) }} FCFA</span></div>
                    <div class="flex justify-between text-white/70"><span>Minimum deposit</span><span>{{ number_format($quote['deposit_due']) }} FCFA</span></div>
                </div>
                <p class="text-xs text-white/55 mt-3">The deposit is non-refundable and is 20% of the final price for this date. Paying the deposit leaves the rest as a balance.</p>
            </div>
        </div>

        @if (session('not_permitted'))
            <div class="mt-4 rounded-xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm">{{ session('not_permitted') }}</div>
        @endif

        <form id="coth-form" method="POST" action="{{ route('coth.hold') }}" class="coth-form coth-card rounded-3xl p-6 mt-5 space-y-5">
            @csrf
            <input type="hidden" name="table_id" value="{{ $table->id }}">
            <input type="hidden" name="event_date" value="{{ $date }}">

            {{-- Step 1: phone first --}}
            <div>
                <label class="block">
                    <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55 mb-2">WhatsApp number</span>
                    <div class="coth-field rounded-2xl flex items-center px-4 py-3 gap-3">
                        <span class="text-brand-gold font-semibold">+237</span>
                        <input id="coth-phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" placeholder="6 75 32 17 39" class="flex-1 bg-transparent outline-none text-white placeholder-white/30">
                        <button type="button" id="coth-resolve" class="shrink-0 text-xs font-bold uppercase tracking-[0.14em] bg-brand-gold text-[#0A0A0A] rounded-full px-4 py-2">Continue</button>
                    </div>
                </label>
                <p class="text-xs text-white/50 mt-2">Cameroonian numbers (+237) are checked with Campay to pre-fill your name.</p>
                <p id="coth-phone-err" class="text-xs text-red-300 mt-2 coth-hidden"></p>
            </div>

            {{-- Step 2: name (resolved via Campay when possible) + payment --}}
            <div id="coth-step-2" class="space-y-4 coth-hidden">
                <div>
                    <label class="block">
                        <span class="flex items-center justify-between mb-2">
                            <span class="text-[11px] uppercase tracking-[0.18em] text-white/55">Your name</span>
                            <span id="coth-name-source" class="coth-src coth-hidden">Resolved via Campay</span>
                        </span>
                        <input id="coth-name" name="name" value="{{ old('name') }}" required placeholder="Full name" class="w-full coth-field rounded-2xl px-4 py-3 text-white placeholder-white/30 outline-none">
                    </label>
                </div>

                <label class="block">
                    <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55 mb-2">Email <span class="text-white/35 normal-case tracking-normal">(optional)</span></span>
                    <input name="email" value="{{ old('email') }}" type="email" placeholder="you@example.com" class="w-full coth-field rounded-2xl px-4 py-3 text-white placeholder-white/30 outline-none">
                </label>

                <div>
                    <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55 mb-2">Pay now</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="coth-pay rounded-2xl px-4 py-3 cursor-pointer flex items-start gap-3" data-pay>
                            <input type="radio" name="pay_choice" value="deposit" @if(old('pay_choice', 'deposit')==='deposit') checked @endif class="mt-1">
                            <span>
                                <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55">Deposit</span>
                                <span class="block font-semibold">{{ number_format($quote['deposit_due']) }} FCFA</span>
                                <span class="text-xs text-white/55">Balance {{ number_format(max(0, $quote['final_price'] - $quote['deposit_due'])) }} FCFA on arrival.</span>
                            </span>
                        </label>
                        <label class="coth-pay rounded-2xl px-4 py-3 cursor-pointer flex items-start gap-3" data-pay>
                            <input type="radio" name="pay_choice" value="full" @if(old('pay_choice')==='full') checked @endif class="mt-1">
                            <span>
                                <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55">Full price</span>
                                <span class="block font-semibold">{{ number_format($quote['final_price']) }} FCFA</span>
                                <span class="text-xs text-white/55">Pay everything now.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <label class="flex items-start gap-2 text-sm text-white/80">
                    <input type="checkbox" name="terms" value="1" required class="mt-1">
                    <span>I accept that the deposit is non-refundable and that this booking is for the date shown only.</span>
                </label>

                <button class="w-full bg-brand-gold text-[#0A0A0A] font-bold uppercase tracking-[0.12em] rounded-full px-5 py-3" type="submit">Continue to verification</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var csrf = @json(csrf_token());
    var resolveUrl = @json(route('coth.resolve-phone'));
    var phone = document.getElementById('coth-phone');
    var btn = document.getElementById('coth-resolve');
    var step2 = document.getElementById('coth-step-2');
    var name = document.getElementById('coth-name');
    var srcLabel = document.getElementById('coth-name-source');
    var errBox = document.getElementById('coth-phone-err');
    var oldName = @json((string) old('name'));
    var oldPhone = @json((string) old('phone'));

    function showErr(msg) { errBox.textContent = msg; errBox.classList.remove('coth-hidden'); }
    function clearErr() { errBox.classList.add('coth-hidden'); errBox.textContent = ''; }

    function resetBtn(label) { btn.disabled = false; btn.innerHTML = label; }

    function resolve() {
        var val = phone.value.trim();
        if (!val) { showErr('Enter a WhatsApp number.'); return; }
        clearErr();
        btn.disabled = true; btn.innerHTML = '<span class="coth-spin"></span>';
        fetch(resolveUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({phone: val})
        })
        .then(function (r) { return r.json().then(function (j) { return {ok: r.ok, body: j}; }); })
        .then(function (res) {
            resetBtn('Change');
            if (!res.ok) { showErr(res.body && res.body.message ? res.body.message : 'Please enter a valid number.'); return; }
            var b = res.body;
            if (b.display_phone) { phone.value = b.display_phone; }
            if (b.name) {
                name.value = b.name;
                srcLabel.classList.remove('coth-hidden');
                srcLabel.textContent = b.source === 'campay' ? '✓ Resolved via Campay' : '✓ Found on this device';
            } else {
                if (!name.value) { name.value = oldName || ''; }
                srcLabel.classList.add('coth-hidden');
            }
            step2.classList.remove('coth-hidden');
            if (!name.value) { setTimeout(function () { name.focus(); }, 50); }
        })
        .catch(function () {
            resetBtn('Continue');
            showErr('Could not check that number. Try again.');
        });
    }

    btn.addEventListener('click', resolve);
    phone.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); resolve(); } });

    document.querySelectorAll('[data-pay]').forEach(function (lbl) {
        var sync = function () {
            document.querySelectorAll('[data-pay]').forEach(function (x) {
                x.classList.toggle('is-active', x.querySelector('input').checked);
            });
        };
        lbl.querySelector('input').addEventListener('change', sync);
        sync();
    });

    // Re-expand the form when the server returned validation errors with old inputs.
    if (oldPhone) { resolve(); }
})();
</script>
@endpush
