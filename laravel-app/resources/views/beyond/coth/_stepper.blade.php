@php
    $current = (int) ($current ?? 1);
    $steps = [
        1 => 'Details',
        2 => 'Verify phone',
        3 => 'Payment',
    ];
@endphp
<style>
    .coth-step { display: inline-flex; align-items: center; gap: 10px; }
    .coth-step-num {
        display: inline-flex; width: 32px; height: 32px; border-radius: 9999px;
        align-items: center; justify-content: center; font-weight: 700; font-size: 14px;
        border: 1px solid rgba(255,255,255,.18); color: rgba(255,255,255,.55);
        background: rgba(255,255,255,.03);
    }
    .coth-step-label { font-size: 12px; letter-spacing: .14em; text-transform: uppercase; color: rgba(255,255,255,.55); }
    .coth-step.is-done .coth-step-num { color: #E8C878; border-color: #C5A059; background: rgba(197,160,89,.08); }
    .coth-step.is-done .coth-step-label { color: #E8C878; }
    .coth-step.is-current .coth-step-num { background: #C5A059; color: #0A0A0A; border-color: #C5A059; }
    .coth-step.is-current .coth-step-label { color: #ffffff; font-weight: 600; }
    .coth-step-line {
        flex: 1; height: 1px; min-width: 36px; margin: 0 10px;
        background: linear-gradient(90deg, rgba(197,160,89,.6), rgba(255,255,255,.12));
    }
    @media (max-width: 540px) {
        .coth-step-label { display: none; }
        .coth-step-line { min-width: 20px; }
    }
</style>
<nav aria-label="Reservation progress" class="w-full flex items-center justify-center">
    <ol class="flex items-center w-full max-w-xl" role="list">
        @foreach ($steps as $num => $label)
            @php
                $state = $num < $current ? 'is-done' : ($num === $current ? 'is-current' : '');
            @endphp
            <li class="coth-step {{ $state }}" @if($num === $current) aria-current="step" @endif>
                <span class="coth-step-num">
                    @if ($num < $current)
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12l5 5L20 6" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @else
                        {{ $num }}
                    @endif
                </span>
                <span class="coth-step-label">{{ $label }}</span>
            </li>
            @if ($num !== array_key_last($steps))
                <li class="coth-step-line" aria-hidden="true"></li>
            @endif
        @endforeach
    </ol>
</nav>
