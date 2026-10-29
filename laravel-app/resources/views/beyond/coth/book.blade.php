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
    .coth-dial select {
        background: transparent; color: #fff; border: none; outline: none;
        padding-right: 18px; font-weight: 600; font-size: 14px; appearance: none;
        background-image: linear-gradient(45deg, transparent 50%, #E8C878 50%),
                          linear-gradient(135deg, #E8C878 50%, transparent 50%);
        background-position: calc(100% - 10px) calc(50% - 2px),
                             calc(100% - 6px) calc(50% - 2px);
        background-size: 4px 4px, 4px 4px;
        background-repeat: no-repeat;
    }
    .coth-dial select option { color: #0A0A0A; }
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
</style>
@endpush
@section('content')
@php
    $visuals = app(\App\Services\Coth\CothVisualLibrary::class);
    $countries = [
        ['dial' => '237', 'flag' => '🇨🇲', 'name' => 'Cameroon'],
        ['dial' => '234', 'flag' => '🇳🇬', 'name' => 'Nigeria'],
        ['dial' => '233', 'flag' => '🇬🇭', 'name' => 'Ghana'],
        ['dial' => '225', 'flag' => '🇨🇮', 'name' => 'Ivory Coast'],
        ['dial' => '221', 'flag' => '🇸🇳', 'name' => 'Senegal'],
        ['dial' => '250', 'flag' => '🇷🇼', 'name' => 'Rwanda'],
        ['dial' => '254', 'flag' => '🇰🇪', 'name' => 'Kenya'],
        ['dial' => '27',  'flag' => '🇿🇦', 'name' => 'South Africa'],
        ['dial' => '241', 'flag' => '🇬🇦', 'name' => 'Gabon'],
        ['dial' => '242', 'flag' => '🇨🇬', 'name' => 'Congo-Brazzaville'],
        ['dial' => '243', 'flag' => '🇨🇩', 'name' => 'DR Congo'],
        ['dial' => '236', 'flag' => '🇨🇫', 'name' => 'Central African Republic'],
        ['dial' => '240', 'flag' => '🇬🇶', 'name' => 'Equatorial Guinea'],
        ['dial' => '235', 'flag' => '🇹🇩', 'name' => 'Chad'],
        ['dial' => '44',  'flag' => '🇬🇧', 'name' => 'United Kingdom'],
        ['dial' => '1',   'flag' => '🇺🇸', 'name' => 'United States'],
        ['dial' => '33',  'flag' => '🇫🇷', 'name' => 'France'],
        ['dial' => '32',  'flag' => '🇧🇪', 'name' => 'Belgium'],
        ['dial' => '49',  'flag' => '🇩🇪', 'name' => 'Germany'],
        ['dial' => '971', 'flag' => '🇦🇪', 'name' => 'UAE'],
    ];
@endphp
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

                @if ($allowChairs)
                    <div class="coth-chairs rounded-2xl p-4">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div>
                                <h3 class="font-semibold text-base m-0">Add extra chairs</h3>
                                <p class="text-xs text-white/60 m-0">
                                    {{ (int) $table->capacity }} seats included · up to {{ (int) $maxChairs }} extra · {{ number_format((int) $extraRate) }} FCFA each.
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
                            <div class="coth-dial flex items-center gap-1 border-r border-white/15 pr-2">
                                <select id="coth-dial">
                                    @foreach ($countries as $c)
                                        <option value="{{ $c['dial'] }}" @if($c['dial'] === '237') selected @endif>{{ $c['flag'] }} +{{ $c['dial'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input id="coth-local" required inputmode="tel" autocomplete="tel-national" placeholder="WhatsApp number" class="flex-1 bg-transparent outline-none text-white placeholder-white/35">
                            <input type="hidden" name="phone" id="coth-phone-hidden" value="{{ old('phone') }}">
                            <button type="button" id="coth-resolve" class="shrink-0 text-xs font-bold uppercase tracking-[0.14em] bg-brand-gold text-[#24160F] rounded-full px-4 py-2">Next</button>
                        </div>
                    </label>
                    <p class="text-xs text-white/50 mt-2">Pick your country code. Cameroonian numbers are checked with Campay to pre-fill your name.</p>
                    <p id="coth-phone-err" class="text-xs text-red-300 mt-2 coth-hidden"></p>
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
                        <span>I accept that the deposit is non-refundable and that this booking is for the date shown only.</span>
                    </label>

                    <button class="w-full bg-brand-gold text-[#0A0A0A] font-bold uppercase tracking-[0.12em] rounded-full px-5 py-3 flex items-center justify-center gap-2" type="submit">
                        Continue to phone verification &rarr;
                    </button>
                    <p class="text-xs text-white/55 text-center -mt-2"><span class="opacity-70">&#128242;</span> We'll send a verification code to your WhatsApp number.</p>
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

    var dial = document.getElementById('coth-dial');
    var local = document.getElementById('coth-local');
    var hidden = document.getElementById('coth-phone-hidden');
    var btn = document.getElementById('coth-resolve');
    var step2 = document.getElementById('coth-step-2');
    var name = document.getElementById('coth-name');
    var nameNote = document.getElementById('coth-name-note');
    var srcLabel = document.getElementById('coth-name-source');
    var errBox = document.getElementById('coth-phone-err');
    var oldName = @json((string) old('name'));
    var oldPhone = @json((string) old('phone'));

    function showErr(msg) { errBox.textContent = msg; errBox.classList.remove('coth-hidden'); }
    function clearErr() { errBox.classList.add('coth-hidden'); errBox.textContent = ''; }
    function resetBtn(label) { btn.disabled = false; btn.innerHTML = label; }

    function nationalDigits(raw, dialCode) {
        var d = String(raw || '').replace(/\D/g, '');
        if (d.indexOf(dialCode) === 0 && d.length > dialCode.length + 6) d = d.substring(dialCode.length);
        if (d.charAt(0) === '0') d = d.substring(1);
        return d;
    }

    function resolve() {
        var dialCode = dial.value;
        var raw = nationalDigits(local.value, dialCode);
        if (!raw) { showErr('Enter a WhatsApp number.'); return; }
        local.value = raw;
        clearErr();
        btn.disabled = true; btn.innerHTML = '<span class="coth-spin"></span>';
        fetch(resolveUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({dial_code: dialCode, local: raw})
        })
        .then(function (r) { return r.json().then(function (j) { return {ok: r.ok, body: j}; }); })
        .then(function (res) {
            resetBtn('Change');
            if (!res.ok) { showErr(res.body && res.body.message ? res.body.message : 'Please enter a valid number.'); return; }
            var b = res.body;
            if (b.phone) { hidden.value = b.phone; }
            if (b.name) {
                name.value = b.name;
                srcLabel.classList.remove('coth-hidden');
                srcLabel.textContent = dialCode === '237' ? 'From this Cameroon number' : 'Found for this number';
                nameNote.classList.remove('coth-hidden');
                nameNote.textContent = 'You can edit this name.';
            } else {
                if (!name.value) { name.value = oldName || ''; }
                srcLabel.classList.add('coth-hidden');
                nameNote.classList.remove('coth-hidden');
                nameNote.textContent = dialCode === '237' ? 'No name was found for this number. Type it here.' : 'Type the name for this number.';
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
    local.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); resolve(); } });

    // Extra-chairs qty controls + live price breakdown.
    var qtyInput = document.getElementById('coth-qty');
    var qtyLabel = document.getElementById('coth-qty-label');
    var guestLine = document.getElementById('coth-guest-line');
    var plus = document.getElementById('coth-qty-plus');
    var minus = document.getElementById('coth-qty-minus');
    var maxChairs = {{ (int) ($allowChairs ? $maxChairs : 0) }};
    var baseCapacity = {{ (int) $table->capacity }};
    var listPrice = {{ (int) $quote['list_price'] }};
    var discountPct = {{ (int) $quote['discount_amount'] }};
    var finalTable = {{ (int) $quote['final_price'] }};
    var extraRate = {{ (int) ($allowChairs ? $extraRate : 0) }};
    var discountIncludesAddons = @json($discountIncludesAddons ?? false);
    var isDiscounted = {{ (int) $quote['discounted'] ? 1 : 0 }};
    var depositPct = 20;

    function fmt(n) { return (Math.max(0, Math.round(n))).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
    function sync() {
        var q = parseInt(qtyInput.value || '0', 10);
        if (isNaN(q) || q < 0) q = 0;
        if (q > maxChairs) q = maxChairs;
        qtyInput.value = String(q);
        if (qtyLabel) qtyLabel.textContent = String(q);
        if (plus) plus.disabled = (q >= maxChairs);
        if (minus) minus.disabled = (q <= 0);

        var chairsGross = extraRate * q;
        var chairsDiscount = (discountIncludesAddons && isDiscounted) ? Math.floor(chairsGross * discountPct / listPrice) : 0;
        // discount is tracked as absolute, but keep simple: percent same as table => recompute via rate
        if (isDiscounted && discountIncludesAddons && listPrice > 0) {
            chairsDiscount = Math.floor(chairsGross * 20 / 100);
        } else {
            chairsDiscount = 0;
        }
        var chairsAmount = chairsGross - chairsDiscount;
        var bookingTotal = finalTable + chairsAmount;
        var deposit = Math.ceil(bookingTotal * depositPct / 100);
        var balance = Math.max(0, bookingTotal - deposit);

        var chairRow = document.getElementById('coth-chair-row');
        var chairLabel = document.getElementById('coth-chair-label');
        var chairAmount = document.getElementById('coth-chair-amount');
        var totalAmount = document.getElementById('coth-total-amount');
        if (chairRow) chairRow.style.display = q > 0 ? '' : 'none';
        if (chairLabel) chairLabel.textContent = 'Extra chairs (' + q + ' × ' + fmt(extraRate) + ')';
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

    // Expand step 2 when the server returned validation errors with old inputs.
    sync();
    document.getElementById('coth-form').addEventListener('submit', function (e) {
        if (!hidden.value) {
            e.preventDefault();
            showErr('Tap Next after the WhatsApp number.');
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
    if (oldPhone) { hidden.value = oldPhone; step2.classList.remove('coth-hidden'); }
})();
</script>
@endpush
