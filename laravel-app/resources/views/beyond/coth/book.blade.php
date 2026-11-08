@extends('beyond.layout')
@section('title', 'Reserve '.$table->code)
@section('body_class', 'bg-[#24160F]')
@push('head')
<style>
    .coth-page { background: #24160F; }
    .coth-shell { background: #24160F; color: #F6EFE4; }
    .coth-card {
        border: 1px solid rgba(198,161,91,.4);
        background: #3C2A1E;
        box-shadow: 0 18px 40px rgba(0,0,0,.35);
    }
    .coth-field { background: #2A1C14; border: 1px solid rgba(198,161,91,.35); transition: border-color .15s ease; }
    .coth-field:focus-within { border-color: #E8C878; }
    .coth-country { position: relative; }
    .coth-country-btn {
        background: transparent; color: #fff; border: 0; font-weight: 600; font-size: 14px;
        display: flex; align-items: center; gap: 6px; max-width: 148px; text-align: left; cursor: pointer;
    }
    .coth-country-menu {
        position: absolute; z-index: 40; left: 0; top: calc(100% + 8px); width: min(320px, calc(100vw - 48px));
        background: #2A1C14; border: 1px solid rgba(232,200,120,.55); border-radius: 16px;
        box-shadow: 0 16px 40px rgba(0,0,0,.45); padding: 8px;
    }
    .coth-country-menu input {
        width: 100%; background: #24160F; color: #fff; border: 1px solid rgba(198,161,91,.35);
        border-radius: 12px; padding: 10px 12px; font-size: 16px; outline: none;
    }
    .coth-country-menu ul { list-style: none; margin: 8px 0 0; padding: 0; max-height: 240px; overflow: auto; }
    .coth-country-menu button {
        width: 100%; text-align: left; background: transparent; color: #F6EFE4; border: 0;
        border-radius: 10px; padding: 10px 8px; font-size: 14px; cursor: pointer;
    }
    .coth-country-menu button:hover, .coth-country-menu button.is-active { background: rgba(197,160,89,.16); }
    .coth-otp-input {
        width: 100%; background: transparent; color: #fff; border: 0; outline: none;
        font-size: 22px; letter-spacing: .28em; font-weight: 700;
    }
    .coth-pay-opt { border: 1px solid rgba(198,161,91,.28); background: #2A1C14; transition: border-color .15s ease, background .15s ease; }
    .coth-pay-opt.is-active { border-color: #E8C878; background: rgba(197,160,89,.1); }
    .coth-src { color: #34d399; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; }
    .coth-hidden { display: none; }
    .coth-spin { width: 14px; height: 14px; border: 2px solid rgba(10,10,10,.3); border-top-color: #0A0A0A; border-radius: 9999px; display: inline-block; animation: coth-spin 0.8s linear infinite; vertical-align: -2px; }
    @keyframes coth-spin { to { transform: rotate(360deg); } }
    .coth-form input[type=radio], .coth-form input[type=checkbox] { accent-color: #C5A059; }
    .coth-chairs { background: rgba(255,255,255,.03); border: 1px dashed rgba(197,160,89,.5); }
    .coth-qty-btn {
        width: 36px; height: 36px; border-radius: 9999px; border: 1px solid rgba(197,160,89,.6);
        background: rgba(10,10,10,.6); color: #E8C878; font-weight: 700; line-height: 1;
    }
    .coth-qty-btn[disabled] { opacity: .35; cursor: not-allowed; }
    .coth-day {
        border: 1px solid rgba(198,161,91,.35); background: #2A1C14; color: #F6EFE4;
        border-radius: 14px; padding: 8px 10px; min-width: 72px; text-align: left; cursor: pointer;
    }
    .coth-day.is-on { border-color: #E8C878; background: rgba(197,160,89,.16); }
    .coth-day small { display: block; color: #E8C878; font-size: 11px; }
</style>
@endpush
@section('content')
<div class="coth-shell text-white min-h-[80vh] py-6 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <p class="text-brand-gold font-serif text-xl tracking-[0.25em] uppercase m-0">CHILL ON THE HILL</p>
            <a href="{{ route('coth.tables', ['date' => $date, 'section' => $table->section->code]) }}" class="text-sm text-brand-gold">&larr; Back to tables</a>
        </div>

        <div class="mt-4 mb-6">
            @include('beyond.coth._stepper', ['current' => 1])
        </div>

        @if (session('not_permitted'))
            <div class="rounded-xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm mb-4">{{ session('not_permitted') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            {{-- Left: form --}}
            <form id="coth-form" method="POST" action="{{ route('coth.hold') }}" class="coth-form coth-card rounded-3xl p-6 lg:p-8 lg:col-span-3 space-y-6">
                @csrf
                <input type="hidden" name="table_id" value="{{ $table->id }}">
                <input type="hidden" name="event_date" value="{{ $date }}">
                <input type="hidden" name="nights" id="coth-nights" value="{{ ! empty($planMode) ? count($planLines) : (int) old('nights', 1) }}">
                @if (! empty($planMode))
                    <input type="hidden" name="booking_plan" value="1">
                @endif

                @if (! empty($planMode))
                    <div>
                        <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55 mb-2">Your days</span>
                        <div class="space-y-2">
                            @foreach ($planLines as $line)
                                <div class="rounded-2xl border border-white/10 px-3 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-semibold">{{ $line['label'] }}</div>
                                            <div class="text-sm text-white/70">{{ $line['table'] }} · {{ $line['section'] }} · {{ number_format((int) $line['price']) }} FCFA</div>
                                        </div>
                                        <a class="text-xs text-brand-gold underline" href="{{ $line['change'] }}">Change</a>
                                    </div>
                                    @if ((int) $line['max'] > 0)
                                        <label class="mt-2 flex items-center justify-between gap-3 text-sm text-white/70">
                                            <span>Extra chairs · {{ number_format((int) $line['rate']) }} FCFA each</span>
                                            <input class="coth-plan-chair w-16 bg-transparent border-b border-brand-gold/50 text-white text-right" type="number" min="0" max="{{ (int) $line['max'] }}" name="chairs[{{ $line['date'] }}]" data-plan-chair="{{ $line['date'] }}" value="{{ (int) old('chairs.'.$line['date'], 0) }}">
                                        </label>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-white/55 mt-2 m-0" id="coth-stay-line">These days are paid together. 2 days is 5% off, then 5% more for each extra day, up to 8 days at 35% off.</p>
                    </div>
                @else
                <div>
                    <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55 mb-2">How many days</span>
                    <div class="flex flex-wrap gap-2" id="coth-days">
                        @foreach ($stays as $stay)
                            <button type="button" class="coth-day {{ (int) old('nights', 1) === (int) $stay['nights'] ? 'is-on' : '' }}" data-stay="{{ (int) $stay['nights'] }}">
                                {{ (int) $stay['nights'] }} {{ (int) $stay['nights'] === 1 ? 'day' : 'days' }}
                                @if ((int) $stay['percent'] > 0)
                                    <small>{{ (int) $stay['percent'] }}% off</small>
                                @else
                                    <small>Standard</small>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    <p class="text-xs text-white/55 mt-2 m-0" id="coth-stay-line">The same table is held for each open day you choose. Closed days are skipped. 2 days is 5% off, then 5% more for each extra day, up to 8 days at 35% off.</p>
                </div>
                @endif

                @if ($allowChairs)
                    <div class="coth-chairs rounded-2xl p-4">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div>
                                <h3 class="font-semibold text-base m-0">Add extra chairs</h3>
                                <p class="text-xs text-white/60 m-0">
                                    {{ (int) $table->capacity }} seats included · up to {{ (int) $maxChairs }} extra · {{ number_format((int) $extraRate) }} FCFA each per day.
                                    The 20% deposit includes these chairs.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="coth-qty-btn" id="coth-qty-minus" aria-label="Remove a chair">−</button>
                                <input type="hidden" name="extra_chairs" id="coth-qty" value="{{ (int) old('extra_chairs', 0) }}">
                                <span class="font-semibold text-lg w-6 text-center" id="coth-qty-label">{{ (int) old('extra_chairs', 0) }}</span>
                                <button type="button" class="coth-qty-btn" id="coth-qty-plus" aria-label="Add a chair">+</button>
                            </div>
                        </div>
                        <p class="text-xs text-brand-gold/90 mt-3 m-0" id="coth-guest-line">
                            {{ (int) $table->capacity }} included seats
                        </p>
                        <p class="text-xs text-brand-gold/90 mt-1 m-0" id="coth-chair-limit" hidden></p>
                    </div>
                @else
                    <input type="hidden" name="extra_chairs" id="coth-qty" value="0">
                @endif

                <div>
                    <h1 class="font-serif text-3xl m-0">Reserve your table</h1>
                    <h2 class="text-lg mt-4 mb-1">Your details</h2>
                    <p class="text-sm text-white/60 m-0">We'll use this information to confirm your reservation.</p>
                </div>

                {{-- Step 1 (within Stage 1): WhatsApp number first --}}
                <div>
                    <label class="block">
                        <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55 mb-2">WhatsApp number</span>
                        <div class="coth-field rounded-2xl flex items-center px-3 py-2 gap-2">
                            <span class="opacity-80">&#128222;</span>
                            <div class="coth-country border-r border-white/15 pr-2" id="coth-country">
                                <button type="button" class="coth-country-btn" id="coth-country-btn" aria-haspopup="listbox" aria-expanded="false">
                                    <span id="coth-country-label">Cameroon +237</span>
                                </button>
                                <div class="coth-country-menu" id="coth-country-menu" hidden>
                                    <input id="coth-country-search" type="search" placeholder="Search country" autocomplete="off" aria-label="Search country">
                                    <ul id="coth-country-list" role="listbox"></ul>
                                </div>
                                <input type="hidden" id="coth-dial" value="237">
                            </div>
                            <input id="coth-local" data-wa-phone="off" required inputmode="tel" autocomplete="tel-national" placeholder="WhatsApp number" class="flex-1 bg-transparent outline-none text-white placeholder-white/35">
                            <input type="hidden" name="phone" id="coth-phone-hidden" value="{{ old('phone') }}">
                            <button type="button" id="coth-resolve" class="shrink-0 text-xs font-bold uppercase tracking-[0.14em] bg-brand-gold text-[#24160F] rounded-full px-4 py-2">Next</button>
                        </div>
                    </label>
                    <p class="text-xs text-white/50 mt-2">Search for your country, then enter the number. A WhatsApp code is required before the booking is saved.</p>
                    <p id="coth-phone-err" class="text-xs text-red-300 mt-2 coth-hidden"></p>
                </div>

                <div id="coth-otp-box" class="space-y-3 coth-hidden">
                    <label class="block">
                        <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55 mb-2">WhatsApp code</span>
                        <div class="coth-field rounded-2xl flex items-center px-4 py-3 gap-3">
                            <input id="coth-otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="6-digit code" class="coth-otp-input">
                            <button type="button" id="coth-otp-go" class="shrink-0 text-xs font-bold uppercase tracking-[0.14em] bg-brand-gold text-[#24160F] rounded-full px-4 py-2">Verify</button>
                        </div>
                    </label>
                    <div class="flex items-center justify-between gap-3">
                        <p id="coth-otp-err" class="text-xs text-red-300 m-0 coth-hidden"></p>
                        <button type="button" id="coth-otp-resend" class="text-sm text-brand-gold underline bg-transparent border-0 p-0">Resend code</button>
                    </div>
                </div>

                {{-- Step 2 (within Stage 1): name, email, extras, pay choice, terms --}}
                <div id="coth-step-2" class="space-y-5 coth-hidden">
                    <div>
                        <label class="block">
                            <span class="flex items-center justify-between mb-2">
                                <span class="text-[11px] uppercase tracking-[0.18em] text-white/55">Full name</span>
                                <span id="coth-name-source" class="coth-src coth-hidden">Resolved via Campay</span>
                            </span>
                            <div class="coth-field rounded-2xl flex items-center px-4 py-3 gap-3">
                                <span class="opacity-70">&#128100;</span>
                                <input id="coth-name" name="name" value="{{ old('name') }}" required placeholder="Your name" class="flex-1 bg-transparent outline-none text-white placeholder-white/35">
                            </div>
                            <p id="coth-name-note" class="text-xs text-white/55 mt-2 coth-hidden">You can edit this name.</p>
                        </label>
                    </div>

                    <label class="block">
                        <span class="block text-[11px] uppercase tracking-[0.18em] text-white/55 mb-2">Email <span class="text-white/35 normal-case tracking-normal">(optional)</span></span>
                        <div class="coth-field rounded-2xl flex items-center px-4 py-3 gap-3">
                            <span class="opacity-70">&#9993;</span>
                            <input name="email" value="{{ old('email') }}" type="email" placeholder="Email, optional" class="flex-1 bg-transparent outline-none text-white placeholder-white/35">
                        </div>
                    </label>

                    <div>
                        <h3 class="font-semibold text-base m-0">Choose payment amount</h3>
                        <p class="text-xs text-white/60 m-0 mb-3">Pay the 20% deposit, the full amount, or any amount in between.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="coth-pay-opt rounded-2xl px-4 py-3 cursor-pointer block" data-pay>
                                <input type="radio" name="pay_choice" value="deposit" @if(old('pay_choice', 'deposit')==='deposit') checked @endif class="mr-2 align-middle">
                                <span class="text-[11px] uppercase tracking-[0.18em] text-white/55">Pay deposit</span>
                                <span class="block font-semibold mt-1" data-sum-deposit>{{ number_format((int) $quote['deposit_due']) }} FCFA</span>
                                <span class="text-xs text-white/55">20% today</span>
                            </label>
                            <label class="coth-pay-opt rounded-2xl px-4 py-3 cursor-pointer block" data-pay>
                                <input type="radio" name="pay_choice" value="full" @if(old('pay_choice')==='full') checked @endif class="mr-2 align-middle">
                                <span class="text-[11px] uppercase tracking-[0.18em] text-white/55">Pay in full</span>
                                <span class="block font-semibold mt-1" data-sum-total>{{ number_format((int) $quote['booking_total']) }} FCFA</span>
                                <span class="text-xs text-white/55">Full amount today</span>
                            </label>
                            <label class="coth-pay-opt rounded-2xl px-4 py-3 cursor-pointer block sm:col-span-2" data-pay>
                                <input type="radio" name="pay_choice" value="custom" id="coth-pay-custom" @if(old('pay_choice')==='custom') checked @endif class="mr-2 align-middle">
                                <span class="text-[11px] uppercase tracking-[0.18em] text-white/55">Custom amount</span>
                                <span class="mt-2 flex items-center gap-2">
                                    <input id="coth-custom" name="custom_amount" inputmode="numeric" type="number" min="{{ (int) $quote['deposit_due'] }}" max="{{ (int) $quote['booking_total'] }}" value="{{ old('custom_amount', (int) $quote['deposit_due']) }}" class="w-40 bg-transparent outline-none text-white font-semibold border-b border-brand-gold/50">
                                    <span class="text-sm text-white/70">FCFA</span>
                                </span>
                                <span class="text-xs text-white/55" id="coth-custom-min">At least {{ number_format((int) $quote['deposit_due']) }} FCFA (20%)</span>
                                <p id="coth-custom-err" class="text-xs text-red-300 mt-1 coth-hidden"></p>
                            </label>
                            <label class="coth-pay-opt rounded-2xl px-4 py-3 cursor-pointer block sm:col-span-2" data-pay>
                                <input type="radio" name="pay_choice" value="later" id="coth-pay-later" @if(old('pay_choice')==='later') checked @endif class="mr-2 align-middle">
                                <span class="text-[11px] uppercase tracking-[0.18em] text-white/55">Pay later</span>
                                <span class="block font-semibold mt-1">0 FCFA today</span>
                                <span class="text-xs text-white/55">The table is reserved. The full amount stays due.</span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-white/10 bg-white/[.02] px-3 py-2 text-sm">
                        <span class="opacity-70">&#128181;</span>
                        <span class="text-white/70">Remaining balance:</span>
                        <span class="text-brand-gold font-semibold" data-sum-balance>{{ number_format((int) $quote['booking_total'] - (int) $quote['deposit_due']) }} FCFA</span>
                        <span class="text-white/55 text-xs">· Paying the deposit leaves the rest as a balance.</span>
                    </div>

                    <label class="flex items-start gap-2 text-sm text-white/80">
                        <input type="checkbox" name="terms" value="1" required class="mt-1">
                        <span id="coth-terms-copy">I accept that the deposit is non-refundable and that this booking is for the date shown only.</span>
                    </label>

                    <button id="coth-submit" class="w-full bg-brand-gold text-[#0A0A0A] font-bold uppercase tracking-[0.12em] rounded-full px-5 py-3 flex items-center justify-center gap-2" type="submit">
                        Continue to payment &rarr;
                    </button>
                    <p class="text-xs text-white/55 text-center -mt-2">The WhatsApp code must be accepted before this booking is saved.</p>
                </div>
            </form>

            {{-- Right: selection --}}
            <div class="lg:col-span-2">
                @include('beyond.coth._selection', [
                    'table' => $table,
                    'date' => $date,
                    'quote' => $quote,
                ])
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var csrf = @json(csrf_token());
    var resolveUrl = @json(route('coth.resolve-phone'));
    var codeUrl = @json(route('coth.phone-code'));
    var checkUrl = @json(route('coth.phone-check'));
    var countries = @json($countries ?? []);

    var dial = document.getElementById('coth-dial');
    var local = document.getElementById('coth-local');
    var hidden = document.getElementById('coth-phone-hidden');
    var btn = document.getElementById('coth-resolve');
    var step2 = document.getElementById('coth-step-2');
    var name = document.getElementById('coth-name');
    var nameNote = document.getElementById('coth-name-note');
    var srcLabel = document.getElementById('coth-name-source');
    var errBox = document.getElementById('coth-phone-err');
    var otpBox = document.getElementById('coth-otp-box');
    var otpInput = document.getElementById('coth-otp');
    var otpGo = document.getElementById('coth-otp-go');
    var otpResend = document.getElementById('coth-otp-resend');
    var otpErr = document.getElementById('coth-otp-err');
    var countryBtn = document.getElementById('coth-country-btn');
    var countryMenu = document.getElementById('coth-country-menu');
    var countrySearch = document.getElementById('coth-country-search');
    var countryList = document.getElementById('coth-country-list');
    var countryLabel = document.getElementById('coth-country-label');
    var oldName = @json((string) old('name'));
    var oldPhone = @json((string) old('phone'));
    var verifiedPhone = @json((string) session('coth.verified_phone', ''));
    var phoneReady = false;

    function showErr(msg) { errBox.textContent = msg; errBox.classList.remove('coth-hidden'); }
    function clearErr() { errBox.classList.add('coth-hidden'); errBox.textContent = ''; }
    function showOtpErr(msg) { otpErr.textContent = msg; otpErr.classList.remove('coth-hidden'); }
    function clearOtpErr() { otpErr.classList.add('coth-hidden'); otpErr.textContent = ''; }
    function resetBtn(label) { btn.disabled = false; btn.innerHTML = label; }
    function selectedCountry() {
        var dialCode = dial.value;
        for (var i = 0; i < countries.length; i++) {
            if (countries[i].dial === dialCode) return countries[i];
        }
        return {dial: dialCode, name: 'Country', flag: ''};
    }
    function paintCountry() {
        var country = selectedCountry();
        countryLabel.textContent = (country.flag ? country.flag + ' ' : '') + country.name + ' +' + country.dial;
    }
    function renderCountries(query) {
        var q = String(query || '').toLowerCase();
        countryList.innerHTML = '';
        countries.forEach(function (country) {
            var hay = (country.name + ' ' + country.dial + ' +' + country.dial).toLowerCase();
            if (q && hay.indexOf(q) === -1) return;
            var item = document.createElement('li');
            var button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('role', 'option');
            button.textContent = (country.flag ? country.flag + ' ' : '') + country.name + ' +' + country.dial;
            if (country.dial === dial.value) button.className = 'is-active';
            button.addEventListener('click', function () {
                if (dial.value !== country.dial) forgetVerification();
                dial.value = country.dial;
                paintCountry();
                closeCountries();
            });
            item.appendChild(button);
            countryList.appendChild(item);
        });
        if (!countryList.children.length) {
            var empty = document.createElement('li');
            empty.className = 'px-2 py-2 text-sm text-white/50';
            empty.textContent = 'No country matches that search.';
            countryList.appendChild(empty);
        }
    }
    function openCountries() {
        countryMenu.hidden = false;
        countryBtn.setAttribute('aria-expanded', 'true');
        countrySearch.value = '';
        renderCountries('');
        setTimeout(function () { countrySearch.focus(); }, 20);
    }
    function closeCountries() {
        countryMenu.hidden = true;
        countryBtn.setAttribute('aria-expanded', 'false');
    }
    function forgetVerification() {
        phoneReady = false;
        hidden.value = '';
        step2.classList.add('coth-hidden');
        otpBox.classList.add('coth-hidden');
        if (otpInput) otpInput.value = '';
    }
    function nationalDigits(raw, dialCode) {
        var d = String(raw || '').replace(/\D/g, '');
        dialCode = String(dialCode || '');
        if (dialCode && d.indexOf(dialCode) === 0 && d.length > dialCode.length + 6) d = d.substring(dialCode.length);
        // The page formatter used to put 237 in front of every number. Drop that
        // only when another country is selected and the rest is still a full number.
        if (dialCode !== '237' && d.indexOf('237') === 0 && d.length >= 11) {
            var rest = d.substring(3);
            if (rest.length >= 7 && rest.length <= 12) d = rest;
        }
        if (d.charAt(0) === '0') d = d.substring(1);
        return d;
    }
    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(body)
        }).then(function (r) { return r.json().then(function (j) { return {ok: r.ok, body: j}; }); });
    }
    function applyName(b, dialCode) {
        if (b.name) {
            name.value = b.name;
            srcLabel.classList.remove('coth-hidden');
            srcLabel.textContent = dialCode === '237' ? 'From this Cameroon number' : 'Found for this number';
            nameNote.classList.remove('coth-hidden');
            nameNote.textContent = 'You can edit this name.';
        } else {
            if (!name.value) name.value = oldName || '';
            srcLabel.classList.add('coth-hidden');
            nameNote.classList.remove('coth-hidden');
            nameNote.textContent = dialCode === '237' ? 'No name was found for this number. Type it here.' : 'Type the name for this number.';
        }
    }
    function sendCode() {
        var dialCode = dial.value;
        var raw = nationalDigits(local.value, dialCode);
        clearOtpErr();
        return postJson(codeUrl, {dial_code: dialCode, local: raw}).then(function (res) {
            if (!res.ok) {
                showOtpErr(res.body && res.body.message ? res.body.message : 'The code could not be sent.');
                return false;
            }
            return true;
        });
    }
    function resolve() {
        var dialCode = dial.value;
        var raw = nationalDigits(local.value, dialCode);
        if (!raw) { showErr('Enter a WhatsApp number.'); return; }
        local.value = raw;
        clearErr();
        forgetVerification();
        btn.disabled = true; btn.innerHTML = '<span class="coth-spin"></span>';
        postJson(resolveUrl, {dial_code: dialCode, local: raw})
        .then(function (res) {
            if (!res.ok) {
                resetBtn('Next');
                showErr(res.body && res.body.message ? res.body.message : 'Please enter a valid number.');
                return 'stop';
            }
            applyName(res.body || {}, dial.value);
            if (res.body && res.body.phone) hidden.value = res.body.phone;
            return sendCode();
        })
        .then(function (sent) {
            if (sent === 'stop') return;
            resetBtn('Change');
            if (!sent) return;
            otpBox.classList.remove('coth-hidden');
            if (otpInput) otpInput.focus();
        })
        .catch(function () {
            resetBtn('Next');
            showErr('Could not check that number. Try again.');
        });
    }
    function verifyCode() {
        var code = String(otpInput.value || '').replace(/\D/g, '');
        if (code.length !== 6) { showOtpErr('Enter the 6-digit code.'); return; }
        clearOtpErr();
        otpGo.disabled = true;
        postJson(checkUrl, {phone: hidden.value, otp: code})
        .then(function (res) {
            otpGo.disabled = false;
            if (!res.ok) { showOtpErr(res.body && res.body.message ? res.body.message : 'That code is not valid.'); return; }
            phoneReady = true;
            if (res.body && res.body.phone) hidden.value = res.body.phone;
            step2.classList.remove('coth-hidden');
            if (!name.value) setTimeout(function () { name.focus(); }, 50);
        })
        .catch(function () {
            otpGo.disabled = false;
            showOtpErr('Could not check that code. Try again.');
        });
    }

    paintCountry();
    renderCountries('');
    countryBtn.addEventListener('click', function () {
        if (countryMenu.hidden) openCountries();
        else closeCountries();
    });
    countrySearch.addEventListener('input', function () { renderCountries(countrySearch.value); });
    countrySearch.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeCountries(); });
    document.addEventListener('click', function (e) {
        if (!e.target.closest || !e.target.closest('#coth-country')) closeCountries();
    });
    local.addEventListener('input', forgetVerification);
    btn.addEventListener('click', resolve);
    local.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); resolve(); } });
    otpGo.addEventListener('click', verifyCode);
    otpInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); verifyCode(); } });
    otpResend.addEventListener('click', function () {
        otpResend.disabled = true;
        sendCode().then(function () { otpResend.disabled = false; });
    });

    // Extra-chairs qty controls + live price breakdown.
    var qtyInput = document.getElementById('coth-qty');
    var qtyLabel = document.getElementById('coth-qty-label');
    var guestLine = document.getElementById('coth-guest-line');
    var plus = document.getElementById('coth-qty-plus');
    var minus = document.getElementById('coth-qty-minus');
    var maxChairs = {{ (int) ($allowChairs ? $maxChairs : 0) }};
    var baseCapacity = {{ (int) $table->capacity }};
    var listPrice = {{ (int) $quote['list_price'] }};
    var oneDayDiscount = {{ (int) $quote['discount_amount'] }};
    var extraRate = {{ (int) ($allowChairs ? $extraRate : 0) }};
    var discountIncludesAddons = @json($discountIncludesAddons ?? false);
    var isDiscounted = {{ (int) $quote['discounted'] ? 1 : 0 }};
    var depositPct = 20;
    var stays = @json($stays ?? []);
    var planLines = @json($planLines ?? []);
    var nightsInput = document.getElementById('coth-nights');
    var stayPercents = {1: 0, 2: 5, 3: 10, 4: 15, 5: 20, 6: 25, 7: 30, 8: 35};

    function fmt(n) { return (Math.max(0, Math.round(n))).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
    function sync() {
        var planListTotal = 0;
        var planChairGross = 0;
        if (planLines.length) {
            var planNames = [];
            planLines.forEach(function (line) {
                planListTotal += line.price;
                planNames.push(line.label + ' · ' + line.table);
                var input = document.querySelector('[data-plan-chair="' + line.date + '"]');
                var cq = input ? parseInt(input.value || '0', 10) : 0;
                if (isNaN(cq) || cq < 0) cq = 0;
                if (cq > line.max) cq = line.max;
                if (input) input.value = String(cq);
                planChairGross += line.rate * cq;
            });
            if (nightsInput) nightsInput.value = String(planLines.length);
            stays = [{nights: planLines.length, percent: stayPercents[planLines.length] || 0, label: planNames.join('; '), closed: [], chairs: null}];
        }
        var nights = nightsInput ? parseInt(nightsInput.value || '1', 10) : 1;
        if (isNaN(nights) || nights < 1) nights = 1;
        if (nights > 8) nights = 8;
        var stay = null;
        stays.forEach(function (row) { if (row.nights === nights) stay = row; });
        var chairCap = maxChairs;
        if (stay && stay.chairs !== null && stay.chairs !== undefined && !isNaN(parseInt(stay.chairs, 10))) {
            chairCap = Math.min(maxChairs, parseInt(stay.chairs, 10));
        }
        var q = parseInt(qtyInput.value || '0', 10);
        if (isNaN(q) || q < 0) q = 0;
        if (q > chairCap) q = chairCap;
        qtyInput.value = String(q);
        if (qtyLabel) qtyLabel.textContent = String(q);
        if (plus) plus.disabled = (q >= chairCap);
        if (minus) minus.disabled = (q <= 0);
        var chairLimit = document.getElementById('coth-chair-limit');
        if (chairLimit) {
            if (chairCap < maxChairs) {
                chairLimit.hidden = false;
                chairLimit.textContent = chairCap < 1
                    ? 'No extra chairs are left on every day of this stay.'
                    : ('Only ' + chairCap + ' extra chair(s) are free on every day of this stay.');
            } else {
                chairLimit.hidden = true;
            }
        }

        var stayPct = stayPercents[nights] || 0;
        var tableList = listPrice * nights;
        var tableDiscount = nights > 1 ? Math.floor(tableList * stayPct / 100) : oneDayDiscount;
        var tableFinal = tableList - tableDiscount;
        var chairsGross = extraRate * q * nights;
        var chairsDiscount = 0;
        if (nights > 1) {
            chairsDiscount = Math.floor(chairsGross * stayPct / 100);
        } else if (isDiscounted && discountIncludesAddons && listPrice > 0) {
            chairsDiscount = Math.floor(chairsGross * 20 / 100);
        }
        var chairsAmount = chairsGross - chairsDiscount;
        var bookingTotal = tableFinal + chairsAmount;
        if (planLines.length) {
            nights = planLines.length;
            stayPct = stayPercents[nights] || 0;
            tableList = planListTotal;
            tableDiscount = nights > 1 ? Math.floor(tableList * stayPct / 100) : oneDayDiscount;
            tableFinal = tableList - tableDiscount;
            chairsGross = planChairGross;
            chairsDiscount = nights > 1 ? Math.floor(chairsGross * stayPct / 100) : 0;
            chairsAmount = chairsGross - chairsDiscount;
            bookingTotal = tableFinal + chairsAmount;
            stay = stays[0];
        }
        var listEl = document.getElementById('coth-list-amount');
        var discountEl = document.getElementById('coth-discount-amount');
        var finalEl = document.getElementById('coth-final-amount');
        var dateLabel = document.getElementById('coth-date-label');
        var dateNote = document.getElementById('coth-date-note');
        if (listEl) listEl.textContent = fmt(tableList) + ' FCFA';
        if (discountEl) {
            discountEl.textContent = (tableDiscount > 0 ? '−' : '') + fmt(tableDiscount) + ' FCFA';
            if (discountEl.parentElement) discountEl.parentElement.style.color = tableDiscount > 0 ? '#6ee7b7' : '';
        }
        if (finalEl) finalEl.textContent = fmt(tableFinal) + ' FCFA';
        if (dateLabel && stay) dateLabel.textContent = stay.label;
        var selectTitle = document.getElementById('coth-select-title');
        if (selectTitle && planLines.length > 1) selectTitle.textContent = planLines.length + ' days';
        if (dateNote) {
            var note = nights > 1 ? (nights + ' open days · ' + stayPct + '% off') : 'One-day reservation';
            if (stay && stay.closed && stay.closed.length) note += ' · ' + stay.closed.join(', ') + ' closed, not charged';
            dateNote.textContent = note;
        }
        var stayLine = document.getElementById('coth-stay-line');
        if (stayLine && stay) {
            var line = nights > 1
                ? (planLines.length
                    ? ('These days are paid together (' + stay.label + '), ' + stayPct + '% off.')
                    : ('The table is held for ' + nights + ' open days (' + stay.label + '), ' + stayPct + '% off.'))
                : 'One day at the standard price. Add more days for a discount, from 5% for 2 days up to 35% for 8.';
            if (stay.closed && stay.closed.length) {
                line += ' ' + stay.closed.join(' and ') + (stay.closed.length > 1 ? ' are' : ' is') + ' closed and not charged.';
            }
            stayLine.textContent = line;
        }
        var deposit = Math.ceil(bookingTotal * depositPct / 100);
        var balance = Math.max(0, bookingTotal - deposit);

        var chairRow = document.getElementById('coth-chair-row');
        var chairLabel = document.getElementById('coth-chair-label');
        var chairAmount = document.getElementById('coth-chair-amount');
        var totalAmount = document.getElementById('coth-total-amount');
        if (chairRow) chairRow.style.display = (planLines.length ? planChairGross > 0 : q > 0) ? '' : 'none';
        if (chairLabel) {
            chairLabel.textContent = planLines.length
                ? ('Extra chairs' + (chairsDiscount > 0 ? ' (after ' + stayPct + '% off)' : ''))
                : ('Extra chairs (' + q + ' × ' + fmt(extraRate) + (nights > 1 ? ' × ' + nights + ' days' : '') + (chairsDiscount > 0 ? ', after ' + stayPct + '% off' : '') + ')');
        }
        if (chairAmount) chairAmount.textContent = fmt(chairsAmount) + ' FCFA';
        if (totalAmount) totalAmount.textContent = fmt(bookingTotal) + ' FCFA';

        var depEl = document.querySelector('[data-sum-deposit]');
        var totEl = document.querySelector('[data-sum-total]');
        var balEl = document.querySelector('[data-sum-balance]');
        if (depEl) depEl.textContent = fmt(deposit) + ' FCFA';
        if (totEl) totEl.textContent = fmt(bookingTotal) + ' FCFA';
        var customInput = document.getElementById('coth-custom');
        var customRadio = document.getElementById('coth-pay-custom');
        var customErr = document.getElementById('coth-custom-err');
        if (customInput) {
            customInput.min = String(deposit);
            customInput.max = String(bookingTotal);
            var minNote = document.getElementById('coth-custom-min');
            if (minNote) minNote.textContent = 'At least ' + fmt(deposit) + ' FCFA (20%)';
        }
        var due = deposit;
        var dueLabel = 'Due today (20% deposit)';
        if (customRadio && customRadio.checked) {
            var typed = parseInt(customInput && customInput.value ? customInput.value : '0', 10);
            if (isNaN(typed)) typed = deposit;
            if (typed < deposit) {
                if (customErr) { customErr.textContent = 'Minimum is ' + fmt(deposit) + ' FCFA.'; customErr.classList.remove('coth-hidden'); }
            } else if (typed > bookingTotal) {
                if (customErr) { customErr.textContent = 'Maximum is ' + fmt(bookingTotal) + ' FCFA.'; customErr.classList.remove('coth-hidden'); }
            } else if (customErr) {
                customErr.classList.add('coth-hidden');
            }
            due = Math.min(bookingTotal, Math.max(deposit, isNaN(typed) ? deposit : typed));
            dueLabel = 'Due today (custom)';
            balance = Math.max(0, bookingTotal - due);
        } else if (document.querySelector('input[name="pay_choice"][value="full"]') && document.querySelector('input[name="pay_choice"][value="full"]').checked) {
            due = bookingTotal;
            dueLabel = 'Due today (full)';
            balance = 0;
            if (customErr) customErr.classList.add('coth-hidden');
        } else if (document.getElementById('coth-pay-later') && document.getElementById('coth-pay-later').checked) {
            due = 0;
            dueLabel = 'Due today';
            balance = bookingTotal;
            if (customErr) customErr.classList.add('coth-hidden');
        } else if (customErr) {
            customErr.classList.add('coth-hidden');
        }
        if (balEl) balEl.textContent = fmt(balance) + ' FCFA';
        var sideDue = document.getElementById('coth-due-amount');
        var sideLabel = document.getElementById('coth-due-label');
        var sideBal = document.getElementById('coth-side-balance');
        if (sideDue) sideDue.textContent = fmt(due) + ' FCFA';
        if (sideLabel) sideLabel.textContent = dueLabel;
        if (sideBal) sideBal.textContent = fmt(bookingTotal - due) + ' FCFA';
        var terms = document.getElementById('coth-terms-copy');
        var submit = document.getElementById('coth-submit');
        var later = document.getElementById('coth-pay-later') && document.getElementById('coth-pay-later').checked;
        if (terms) {
            var cover = nights > 1 ? ('this booking covers ' + nights + ' open days' + (stay ? ' (' + stay.label + ')' : '') + '.') : 'this booking is for the date shown only.';
            if (stay && stay.closed && stay.closed.length) cover += ' ' + stay.closed.join(' and ') + (stay.closed.length > 1 ? ' are' : ' is') + ' closed and not charged.';
            terms.textContent = later
                ? 'I accept that the full amount stays due and that ' + cover
                : 'I accept that the deposit is non-refundable and that ' + cover;
        }
        if (submit) submit.textContent = later ? 'Reserve and pay later' : 'Continue to payment';

        if (guestLine) {
            if (q > 0) {
                guestLine.textContent = baseCapacity + ' included seats + ' + q + ' extra chair(s) = ' + (baseCapacity + q) + ' guests';
            } else {
                guestLine.textContent = baseCapacity + ' included seats';
            }
        }
    }

    if (plus && minus && qtyInput) {
        plus.addEventListener('click', function () { qtyInput.value = String(parseInt(qtyInput.value || '0', 10) + 1); sync(); });
        minus.addEventListener('click', function () { qtyInput.value = String(Math.max(0, parseInt(qtyInput.value || '0', 10) - 1)); sync(); });
        sync();
    }

    document.querySelectorAll('[data-pay]').forEach(function (lbl) {
        var syncRadio = function () {
            document.querySelectorAll('[data-pay]').forEach(function (x) {
                x.classList.toggle('is-active', x.querySelector('input').checked);
            });
        };
        lbl.querySelector('input[type="radio"]').addEventListener('change', function () { syncRadio(); sync(); });
        syncRadio();
    });
    var customAmount = document.getElementById('coth-custom');
    if (customAmount) {
        customAmount.addEventListener('input', function () {
            var radio = document.getElementById('coth-pay-custom');
            if (radio) radio.checked = true;
            document.querySelectorAll('[data-pay]').forEach(function (x) {
                var r = x.querySelector('input[type="radio"]');
                x.classList.toggle('is-active', r && r.checked);
            });
            sync();
        });
    }

    document.querySelectorAll('[data-plan-chair]').forEach(function (input) {
        input.addEventListener('input', sync);
    });

    document.querySelectorAll('[data-stay]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (nightsInput) nightsInput.value = btn.getAttribute('data-stay');
            document.querySelectorAll('[data-stay]').forEach(function (other) {
                other.classList.toggle('is-on', other === btn);
            });
            sync();
        });
    });

    // Expand step 2 when the server returned validation errors with old inputs.
    sync();
    document.getElementById('coth-form').addEventListener('submit', function (e) {
        if (!hidden.value || !phoneReady) {
            e.preventDefault();
            showErr('Verify the WhatsApp code before the booking can continue.');
            return;
        }
        var customRadio = document.getElementById('coth-pay-custom');
        var customInput = document.getElementById('coth-custom');
        if (customRadio && customRadio.checked && customInput) {
            var typed = parseInt(customInput.value || '0', 10);
            var min = parseInt(customInput.min || '0', 10);
            var max = parseInt(customInput.max || '0', 10);
            if (isNaN(typed) || typed < min || typed > max) {
                e.preventDefault();
                sync();
            }
        }
    });
    if (oldPhone && verifiedPhone && String(oldPhone).replace(/\D/g, '') === String(verifiedPhone).replace(/\D/g, '')) {
        hidden.value = oldPhone;
        phoneReady = true;
        step2.classList.remove('coth-hidden');
    }
})();
</script>
@endpush
