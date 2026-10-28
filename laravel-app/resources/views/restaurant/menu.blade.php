@extends('beyond.layout')
@section('title', 'Menu · Skyline Hotel')
@section('body_class', 'bg-[#070707]')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
    body { background: #070707 !important; }
    .menu-book { background: #070707; padding-bottom: 6rem; }
    .menu-cat-nav { -webkit-overflow-scrolling: touch; scrollbar-width: none; }
    .menu-cat-nav::-webkit-scrollbar { display: none; }
    .menu-chip {
        scroll-snap-align: start; min-height: 2.65rem; border-radius: 999px;
        padding: .55rem 1rem; font-size: .72rem; font-weight: 700; letter-spacing: .12em;
        text-transform: uppercase; white-space: nowrap; border: 1px solid rgba(197,160,89,.4);
        background: transparent; color: #C5A059; cursor: pointer;
    }
    .menu-chip.is-on { background: #C5A059; color: #0A0A0A; border-color: #C5A059; }
    .menu-sheet {
        max-width: 1180px; margin: .75rem auto; background: #0c0c0c;
        border: 1px solid rgba(197,160,89,.35); border-radius: 1.25rem; overflow: hidden;
    }
    .menu-hero {
        position: relative; min-height: 7.5rem;
        background: linear-gradient(160deg, #1a1408 0%, #070707 70%);
    }
    .menu-hero-copy { padding: 1.4rem 1.15rem 1.1rem; }
    .menu-list { padding: .35rem .7rem 1rem; }
    .menu-item-btn {
        width: 100%; text-align: left; min-height: 3.1rem; padding: .7rem .65rem;
        border: 0; background: transparent; border-radius: .85rem; cursor: pointer; display: block;
    }
    .menu-item-btn:hover, .menu-item-btn:active { background: rgba(197,160,89,.1); }
    .menu-item-line { display: flex; align-items: baseline; gap: .75rem; width: 100%; }
    .menu-item-name {
        font-family: Lora, "Times New Roman", serif; font-size: 1.12rem; font-weight: 500;
        color: #F7F1E8; line-height: 1.3; flex: 1 1 auto; min-width: 0; text-align: left;
    }
    .menu-dots { display: none; }
    .menu-item-price {
        font-family: Lora, serif; font-size: 1.12rem; font-weight: 600; color: #C5A059;
        white-space: nowrap; font-variant-numeric: tabular-nums;
    }
    .menu-item-note { font-family: Lora, serif; font-style: italic; color: rgba(247,241,232,.55); font-size: .92rem; margin: 0 .65rem .2rem; }
    .menu-qty { display: flex; align-items: center; gap: .55rem; padding: .15rem .65rem .85rem; }
    .menu-qty-btn {
        width: 2.6rem; height: 2.6rem; border-radius: 999px; border: 1px solid rgba(197,160,89,.75);
        color: #C5A059; background: transparent; font-size: 1.35rem; cursor: pointer;
    }
    .menu-qty-num { min-width: 1.6rem; text-align: center; font-family: Lora, serif; font-size: 1.2rem; color: #F7F1E8; }
    .menu-details {
        max-width: 1180px; margin: 1rem auto 0; background: #111; border: 1px solid rgba(197,160,89,.35);
        border-radius: 1.25rem; padding: 1.1rem;
    }
    .menu-details input, .menu-details select, .menu-details textarea {
        width: 100%; border-radius: .7rem; border: 1px solid rgba(197,160,89,.35);
        background: #0A0A0A; color: #F7F1E8; padding: .7rem .8rem;
    }
    .menu-order {
        border: 0; border-radius: 999px; background: #C5A059; color: #0A0A0A;
        font-weight: 700; letter-spacing: .12em; text-transform: uppercase; padding: .85rem 1.4rem; cursor: pointer;
    }
    .menu-cart-bar {
        position: fixed; left: .75rem; right: .75rem; bottom: .85rem; z-index: 45;
        display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        background: #C5A059; color: #0A0A0A; border-radius: 999px; padding: .7rem 1.15rem;
        font-weight: 700; box-shadow: 0 10px 30px rgba(0,0,0,.45); text-decoration: none;
    }
    @media (min-width: 1024px) {
        .menu-dots { display: block; flex: 1 0 1.5rem; border-bottom: 1px dotted rgba(197,160,89,.5); transform: translateY(-0.35em); }
        .menu-item-name, .menu-item-price { font-size: 1.35rem; }
        .menu-cart-bar { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $tabs = [];
    foreach ($sections as $section) {
        foreach ($section['blocks'] as $block) {
            if ($block['products']->isEmpty()) {
                continue;
            }
            $tabs[] = $block;
        }
    }
    $first = count($tabs) ? \Illuminate\Support\Str::slug($tabs[0]['title']).'-0' : '';
@endphp
<form method="post" action="{{ route('restaurant.checkout') }}" class="menu-book text-white" id="sky-menu">
    @csrf
    <input type="hidden" name="checkout_token" value="{{ $token }}">
    <input type="hidden" name="country" value="{{ old('country', 'Cameroon') }}">

    @if(isset($photos) && $photos->isNotEmpty())
        <div class="max-w-[1180px] mx-auto px-4 pt-6 grid grid-cols-2 md:grid-cols-3 gap-3">
            @foreach($photos as $photo)
                <figure class="m-0 rounded-2xl overflow-hidden border border-brand-gold/35 bg-black">
                    <img src="{{ $photo->thumbUrl() }}" alt="{{ $photo->title }}" class="w-full h-52 object-cover" loading="lazy" decoding="async">
                </figure>
            @endforeach
        </div>
    @endif

    <div class="max-w-[1180px] mx-auto px-4 pt-8 pb-2 text-center">
        <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl text-[#F7F1E8]">Cafe &amp; <span class="text-brand-gold">Restaurant</span></h1>
        <p class="mt-2 text-sm text-white/70 max-w-2xl mx-auto">Tap a drink or a dish to add it. One order is one bill.</p>
    </div>

    @if ($errors->any() || session('not_permitted'))
        <div class="max-w-[1180px] mx-auto px-4 mt-4">
            <div class="rounded-xl border border-red-400/40 bg-red-950/40 text-red-100 px-4 py-3 text-sm">
                {{ session('not_permitted') }}
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        </div>
    @endif

    @if (count($tabs))
        <div class="menu-cat-nav sticky top-0 z-30 bg-black/95 border-b border-brand-gold/20">
            <div class="max-w-[1180px] mx-auto px-3 py-2 flex gap-2 overflow-x-auto">
                @foreach ($tabs as $index => $tab)
                    @php $tabId = \Illuminate\Support\Str::slug($tab['title']).'-'.$index; @endphp
                    <button type="button" class="menu-chip {{ $index === 0 ? 'is-on' : '' }}" data-chip="{{ $tabId }}" onclick="skyMenuTab('{{ $tabId }}')">{{ $tab['title'] }}</button>
                @endforeach
            </div>
        </div>
        @foreach ($tabs as $index => $tab)
            @php $tabId = \Illuminate\Support\Str::slug($tab['title']).'-'.$index; @endphp
            <section class="px-3 sm:px-4" data-tab="{{ $tabId }}" @if($index !== 0) hidden @endif>
                <article class="menu-sheet">
                    <div class="menu-hero">
                        <div class="menu-hero-copy">
                            <p class="text-[10px] tracking-[0.22em] uppercase text-brand-gold m-0">Skyline Hotel</p>
                            <h2 class="font-serif text-[1.65rem] leading-tight text-[#F7F1E8] m-0 mt-1">{{ $tab['title'] }}</h2>
                        </div>
                    </div>
                    <div class="menu-list">
                        @foreach ($tab['products'] as $product)
                            <div class="menu-item">
                                <button type="button" class="menu-item-btn" onclick="skyMenuBump(this, 1)">
                                    <span class="menu-item-line">
                                        <span class="menu-item-name">{{ $product->name }}</span>
                                        <span class="menu-dots"></span>
                                        <span class="menu-item-price">
                                            @if((float) $product->price > 0)
                                                {{ number_format((float) $product->price, 0, '.', ',') }}
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </span>
                                </button>
                                @if($product->product_details)
                                    <p class="menu-item-note">{{ $product->product_details }}</p>
                                @endif
                                <div class="menu-qty">
                                    <button type="button" class="menu-qty-btn" onclick="skyMenuBump(this, -1)" aria-label="Less">−</button>
                                    <span class="menu-qty-num">0</span>
                                    <button type="button" class="menu-qty-btn" onclick="skyMenuBump(this, 1)" aria-label="More">+</button>
                                    <input type="hidden" name="product_id[]" value="{{ $product->id }}">
                                    <input type="hidden" name="qty[]" value="0">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </section>
        @endforeach
    @else
        <p class="text-center text-white/60 py-16">The menu is not loaded yet.</p>
    @endif

    <div class="menu-details mx-3 sm:mx-4" id="menu-details">
        <h2 class="font-serif text-2xl text-[#F7F1E8] m-0">Your details</h2>
        <div class="grid gap-3 md:grid-cols-2 mt-4">
            <input name="name" required value="{{ old('name') }}" placeholder="Name">
            <input name="phone" required value="{{ old('phone') }}" placeholder="WhatsApp number">
            <select name="service_type" required>
                <option value="dine_in" @if(old('service_type', 'dine_in') === 'dine_in') selected @endif>Dine in</option>
                <option value="take_away" @if(old('service_type') === 'take_away') selected @endif>Take away</option>
            </select>
            <select name="restaurant_table_id">
                <option value="">Table, if dining in</option>
                @foreach($tables as $table)
                    <option value="{{ $table->id }}" @if((string) old('restaurant_table_id') === (string) $table->id) selected @endif>{{ $table->name }}@if($table->capacity) · {{ $table->capacity }}@endif</option>
                @endforeach
            </select>
        </div>
        <p class="mt-4 mb-0"><button type="submit" class="menu-order">Place order</button></p>
    </div>
    <a class="menu-cart-bar" href="#menu-details"><span id="menu-count">0 items</span><span>Place order</span></a>
</form>
@endsection

@push('scripts')
<script>
function skyMenuItem(node) { return node.closest('.menu-item'); }
function skyMenuBump(node, dir) {
    var item = skyMenuItem(node);
    if (!item) return;
    var input = item.querySelector('input[name="qty[]"]');
    var label = item.querySelector('.menu-qty-num');
    var next = Math.max(0, (parseInt(input.value, 10) || 0) + dir);
    input.value = next;
    label.textContent = next;
    skyMenuTally();
}
function skyMenuTally() {
    var total = 0;
    document.querySelectorAll('#sky-menu input[name="qty[]"]').forEach(function (input) {
        total += parseInt(input.value, 10) || 0;
    });
    var bar = document.getElementById('menu-count');
    if (bar) bar.textContent = total + (total === 1 ? ' item' : ' items');
}
function skyMenuTab(id) {
    document.querySelectorAll('#sky-menu [data-tab]').forEach(function (section) {
        section.hidden = section.getAttribute('data-tab') !== id;
    });
    document.querySelectorAll('#sky-menu [data-chip]').forEach(function (chip) {
        chip.classList.toggle('is-on', chip.getAttribute('data-chip') === id);
    });
}
</script>
@endpush
