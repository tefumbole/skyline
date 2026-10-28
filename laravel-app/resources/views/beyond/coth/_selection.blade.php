@php
    /**
     * Shared "Your selection" summary card used across Details, Verify phone,
     * Payment and the confirmation page. Takes either $reservation (with a
     * fully computed quote) OR a loose preview via $table + $date + $quote.
     *
     * Supported inputs:
     *   $reservation (CothReservation)             preferred
     *   $table (CothTable), $date (string), $quote (array)   preview mode
     *   $expiresIn (int, seconds)                  optional countdown
     *   $compact (bool)                            tighter spacing
     */
    $visuals = app(\App\Services\Coth\CothVisualLibrary::class);
    $r = $reservation ?? null;
    $t = $r ? ($r->table ?: \App\CothTable::find($r->coth_table_id)) : ($table ?? null);
    $d = $r ? $r->event_date->toDateString() : ($date ?? null);
    $q = $quote ?? ($r ? [
        'list_price' => (int) $r->list_price,
        'discount_amount' => (int) $r->discount_amount,
        'final_price' => (int) $r->final_price,
        'discounted' => (int) $r->discount_amount > 0,
        'extra_chairs_qty' => (int) $r->extra_chairs_qty,
        'extra_chair_rate' => (int) ($r->extra_chair_rate ?: 0),
        'extra_chairs_amount' => (int) $r->extra_chairs_amount,
        'booking_total' => (int) ($r->booking_total ?: $r->final_price),
        'deposit_due' => (int) $r->deposit_due,
    ] : null);
    $section = $t && $t->section ? $t->section : null;
    $pill = $t ? $visuals->chipFor($t) : '';
    $asset = $t ? $visuals->assetFor($t) : null;
    $isCouch = $t && $t->seating_style === 'couch';
    $dueToday = $r ? (int) $r->amount_due : (int) ($q['deposit_due'] ?? 0);
    $dueLabel = 'Due today (20% deposit)';
    if ($r && $r->pay_choice === 'full') {
        $dueLabel = 'Due today (full)';
    } elseif ($r && $r->pay_choice === 'custom') {
        $dueLabel = 'Due today (custom)';
    }
    $balance = max(0, (int) ($q['booking_total'] ?? 0) - $dueToday);
    $totalGuests = ($t ? (int) $t->capacity : 0) + (int) ($q['extra_chairs_qty'] ?? 0);
@endphp
<aside class="coth-select-card rounded-3xl border border-brand-gold/35 overflow-hidden" style="background:#3C2A1E;color:#F6EFE4;">
    <style>
        .coth-select-art {
            position: relative; background:
                radial-gradient(240px 150px at 50% 38%, rgba(198,161,91,.18), rgba(36,22,15,0) 70%),
                #24160F;
        }
        .coth-select-art::after {
            content: ''; position: absolute; inset: 0; pointer-events: none; z-index: 1;
            box-shadow: inset 0 0 34px 20px #24160F;
        }
        .coth-select-pill {
            border: 1px solid rgba(197,160,89,.9); color: #E8C878;
            background: rgba(10,10,10,.6); letter-spacing: .18em;
            position: relative; z-index: 2;
        }
        .coth-select-due { background: rgba(197,160,89,.08); border: 1px solid rgba(197,160,89,.4); }
        .coth-select-chip {
            display: inline-flex; align-items: center; gap: 6px;
            border: 1px solid rgba(197,160,89,.5); color: #E8C878;
            padding: 2px 10px; border-radius: 9999px; font-size: 11px;
            letter-spacing: .14em; text-transform: uppercase;
        }
    </style>

    <div class="px-5 pt-5 pb-2 text-[11px] uppercase tracking-[0.22em] text-brand-gold">Your selection</div>

    @if ($asset)
        <div class="coth-select-art px-4 py-3 flex items-center justify-center relative">
            <img src="{{ $asset }}" alt="{{ $t->code }} table" class="w-full max-w-[300px] h-44 object-contain" loading="lazy">
            @if ($t)
                <span class="coth-select-pill absolute bottom-2 left-1/2 -translate-x-1/2 text-[11px] font-semibold uppercase rounded-full px-4 py-1">
                    {{ $pill === 'City View · High Table' ? 'City View · High Table' : ((int) $t->capacity).' Seats' }}
                </span>
            @endif
        </div>
    @endif

    <div class="px-5 pt-4 pb-5 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="font-serif text-2xl text-white m-0">{{ $t ? $t->code : '—' }}</h3>
                <p class="m-0 text-sm text-white/70">
                    <span class="opacity-60">&#128205;</span> {{ $section ? $section->name : '' }}
                    &nbsp;·&nbsp;
                    <span class="opacity-60">&#128101;</span> {{ $t ? $t->capacity.' seats' : '' }}
                    @if ($pill)
                        <br><span class="coth-select-chip mt-1">{{ $pill }}</span>
                    @endif
                </p>
            </div>
            @if ($section && in_array($section->code, ['yard', 'outer'], true))
                <span class="coth-select-chip">Premium</span>
            @endif
        </div>

        <div class="rounded-xl border border-white/10 bg-white/[.02] px-3 py-2 text-sm">
            <div class="flex items-center gap-2">
                <span class="opacity-70">&#128197;</span>
                <div>
                    <div class="font-semibold">{{ $d ? \Carbon\Carbon::parse($d)->format('l, j F Y') : '' }}</div>
                    <div class="text-xs text-white/55">One-day reservation</div>
                </div>
            </div>
        </div>

        @if ($q)
            <div class="rounded-xl border border-white/10 overflow-hidden text-sm">
                <div class="px-3 py-2 bg-white/[.03] text-[11px] uppercase tracking-[0.18em] text-white/55">Price summary</div>
                <div class="px-3 py-3 space-y-1">
                    <div class="flex justify-between"><span class="text-white/70">Listed price</span><span>{{ number_format((int) $q['list_price']) }} FCFA</span></div>
                    @if (! empty($q['discounted']) && (int) $q['discount_amount'] > 0)
                        <div class="flex justify-between text-emerald-300"><span>Discount</span><span>−{{ number_format((int) $q['discount_amount']) }} FCFA</span></div>
                    @else
                        <div class="flex justify-between text-white/55"><span>Discount</span><span>0 FCFA</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-white/70">Final table</span><span>{{ number_format((int) $q['final_price']) }} FCFA</span></div>
                    @if ((int) ($q['extra_chairs_qty'] ?? 0) > 0)
                        <div class="flex justify-between"><span class="text-white/70">Extra chairs ({{ (int) $q['extra_chairs_qty'] }} × {{ number_format((int) $q['extra_chair_rate']) }})</span><span>{{ number_format((int) $q['extra_chairs_amount']) }} FCFA</span></div>
                    @endif
                    <div class="flex justify-between font-semibold border-t border-white/10 pt-2 mt-2"><span>Total</span><span>{{ number_format((int) $q['booking_total']) }} FCFA</span></div>
                </div>
                <div class="coth-select-due px-3 py-3 space-y-1">
                    <div class="flex justify-between font-semibold text-brand-gold"><span id="coth-due-label">{{ $dueLabel }}</span><span id="coth-due-amount">{{ number_format($dueToday) }} FCFA</span></div>
                    <div class="flex justify-between text-sm text-white/70"><span>Balance</span><span id="coth-side-balance">{{ number_format($balance) }} FCFA</span></div>
                </div>
            </div>
            <p class="text-xs text-white/50 mt-2">&#9432; The 20% deposit is non-refundable.</p>

            @if ((int) ($q['extra_chairs_qty'] ?? 0) > 0 && $t)
                <p class="text-xs text-white/70 mt-1">
                    {{ (int) $t->capacity }} included seats + {{ (int) $q['extra_chairs_qty'] }} extra chair(s) = {{ $totalGuests }} guests
                </p>
            @endif
        @endif

        <div>
            <div class="text-[11px] uppercase tracking-[0.18em] text-white/50 mb-2">Payment methods</div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center h-8 px-3 rounded-md border border-white/15 bg-white text-[11px] font-bold text-blue-900 tracking-[.14em]">VISA</span>
                <span class="h-6 w-px bg-white/15"></span>
                <span class="inline-flex items-center gap-2 text-sm text-white/85">
                    <span class="inline-block w-5 h-5 rounded bg-yellow-400 text-[10px] font-bold text-black flex items-center justify-center">$</span>
                    Mobile Money
                </span>
            </div>
        </div>
    </div>
</aside>
