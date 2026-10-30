@extends('beyond.layout')

@section('title', $meta['label'].' · Skyline Hotel')

@push('head')
<style>
    .svc-cats { scrollbar-width: none; -webkit-overflow-scrolling: touch; }
    .svc-cats::-webkit-scrollbar { display: none; }
    .svc-qty input[type=number]::-webkit-outer-spin-button,
    .svc-qty input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .svc-qty input[type=number] { -moz-appearance: textfield; }
    .svc-zoom { padding: 0; border: 0; background: transparent; cursor: zoom-in; line-height: 0; }
    #svc-zoom-view { position: fixed; inset: 0; z-index: 80; background: rgba(0,0,0,.72); display: flex; align-items: center; justify-content: center; padding: 1.25rem; }
    #svc-zoom-view[hidden] { display: none; }
    #svc-zoom-view figure { margin: 0; text-align: center; }
    #svc-zoom-view img { width: min(280px, 78vw); height: min(280px, 58vh); object-fit: contain; background: #f5f7fb; border-radius: 16px; }
    #svc-zoom-view figcaption { margin-top: .6rem; color: #F7F1E8; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0"><a href="{{ url('/services') }}" class="hover:underline">Services</a></p>
        @if ($current)
            <a href="{{ route('beyond.services.show', $service) }}" class="mt-4 inline-flex items-center gap-2 rounded-full border border-brand-gold/60 text-brand-gold px-4 py-2 text-sm font-semibold hover:bg-brand-gold hover:text-[#0A0A0A]">
                <span aria-hidden="true">&larr;</span> Back to {{ $meta['label'] }}
            </a>
            <h1 class="font-serif text-3xl text-white mt-4">{{ $current['name'] }}</h1>
            <p class="text-white/70 text-sm mt-2">{{ $current['count'] }} {{ $current['count'] == 1 ? 'item' : 'items' }} in {{ $meta['label'] }}. Choose quantities and place one order.</p>
        @else
            <h1 class="font-serif text-3xl text-white mt-2">{{ $meta['label'] }}</h1>
            <p class="text-white/70 text-sm mt-2">{{ $meta['blurb'] }} Choose a category to see its items.</p>
        @endif

        <label class="block mt-6">
            <span class="sr-only">Search {{ $current ? $current['name'] : $meta['label'] }}</span>
            <input id="svc-search" type="search" placeholder="Search {{ $current ? $current['name'] : $meta['label'] }}" autocomplete="off"
                   class="w-full rounded-2xl bg-black/70 border border-brand-gold/40 px-4 py-3 text-white placeholder-white/40 outline-none focus:border-brand-gold">
        </label>

        @if (! $current)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mt-6 svc-cats" id="svc-cats">
            <a href="{{ route('beyond.services.show', $service) }}"
               class="rounded-2xl border overflow-hidden flex flex-col text-center {{ $current ? 'border-brand-gold/25 bg-white/[0.03] hover:border-brand-gold/70' : 'border-brand-gold bg-brand-gold/15' }}">
                <span class="aspect-square w-full flex items-center justify-center {{ $current ? 'text-brand-gold' : 'bg-brand-gold text-[#0A0A0A]' }}">
                    <i data-lucide="layout-grid" class="w-8 h-8"></i>
                </span>
                <span class="px-2 py-3 text-sm font-semibold leading-tight {{ $current ? 'text-white/90' : 'text-brand-gold' }}">All</span>
            </a>
            @foreach ($categories as $cat)
                @php $active = $current && (int) $current['id'] === (int) $cat['id']; @endphp
                <a href="{{ route('beyond.services.show', [$service, $cat['id']]) }}"
                   data-name="{{ strtolower($cat['name']) }}"
                   class="svc-cat rounded-2xl border overflow-hidden flex flex-col text-center transition-colors {{ $active ? 'border-brand-gold bg-brand-gold/15' : 'border-brand-gold/25 bg-white/[0.03] hover:border-brand-gold/70' }}">
                    <span class="aspect-square w-full flex items-center justify-center overflow-hidden bg-[#14110e] {{ $active ? 'ring-2 ring-inset ring-brand-gold' : '' }}">
                        @if ($cat['image'])
                            <img src="{{ $cat['image'] }}" alt="{{ $cat['name'] }}" class="w-full h-full object-cover">
                        @else
                            <i data-lucide="{{ $cat['icon'] }}" class="w-8 h-8 text-brand-gold"></i>
                        @endif
                    </span>
                    <span class="px-2 pt-2 text-sm font-semibold leading-tight {{ $active ? 'text-brand-gold' : 'text-white/90' }}">{{ $cat['name'] }}</span>
                    <span class="px-2 pb-3 text-[11px] text-white/45">{{ $cat['count'] }}</span>
                </a>
            @endforeach
        </div>
        <p id="svc-none" class="hidden text-white/60 mt-4">No categories match that search.</p>
        @endif

        @if (session('not_permitted'))
            <div class="mt-6 bg-red-950/40 border border-red-400/40 text-red-200 rounded-xl px-4 py-3 text-sm">{{ session('not_permitted') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 bg-red-950/40 border border-red-400/40 text-red-200 rounded-xl px-4 py-3 text-sm">
                <ul class="list-disc pl-5 mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($current)
        <form method="POST" action="{{ route('restaurant.checkout') }}" class="mt-6" id="svc-order">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $token }}">
            <p id="svc-none" class="hidden text-white/60 mb-3">No matches for that search.</p>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-2" id="svc-items">
                @forelse ($items as $product)
                    @php
                        $img = \App\Support\ServiceCatalog::productImage($product);
                        $icon = \App\Support\ServiceCatalog::categoryIcon($product->name);
                    @endphp
                    <div class="svc-item flex items-center gap-2 rounded-xl border border-brand-gold/25 bg-white/[0.03] px-2 py-1.5" data-name="{{ strtolower($product->name) }}">
                        <input type="hidden" name="product_id[]" value="{{ $product->id }}">
                        @if ($img)
                            <button type="button" class="svc-zoom shrink-0" data-src="{{ $img }}" data-title="{{ $product->name }}" aria-label="See {{ $product->name }}">
                                <img src="{{ $img }}" alt="" class="h-24 w-24 object-cover bg-[#14110e] rounded-md" width="96" height="96" loading="lazy">
                            </button>
                        @else
                            <span class="h-24 w-24 shrink-0 flex items-center justify-center text-brand-gold/70 bg-[#14110e] rounded-md">
                                <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
                            </span>
                        @endif
                        <span class="min-w-0 flex-1 leading-tight">
                            <span class="block text-sm font-semibold truncate">{{ $product->name }}</span>
                            <span class="block text-xs text-brand-gold font-bold">
                                @if ((float) $product->price > 0)
                                    {{ number_format((float) $product->price, 0) }}
                                @else
                                    —
                                @endif
                            </span>
                        </span>
                        <span class="flex items-center gap-1 svc-qty shrink-0">
                            <button type="button" class="w-7 h-7 rounded-full border border-brand-gold/60 text-brand-gold text-base leading-none" data-step="-1" aria-label="Decrease">&minus;</button>
                            <input type="number" name="qty[]" min="0" value="{{ old('qty.'.$loop->index, 0) }}" class="w-8 text-center rounded-md bg-black border border-white/15 px-0 py-1 text-sm text-white tabular-nums">
                            <button type="button" class="w-7 h-7 rounded-full border border-brand-gold/60 text-brand-gold text-base leading-none" data-step="1" aria-label="Increase">+</button>
                        </span>
                    </div>
                @empty
                    <p class="text-white/60 col-span-full">Nothing in this list yet.</p>
                @endforelse
            </div>

            @if ($items->isNotEmpty())
                <div class="mt-8 rounded-3xl border border-brand-gold/35 bg-gradient-to-b from-[#1a140b] to-black/40 p-5 sm:p-6 shadow-[0_16px_40px_rgba(0,0,0,.35)]">
                    <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0">Checkout</p>
                    <h2 class="text-xl font-semibold text-white mt-1 mb-1">Your details</h2>
                    <p class="text-sm text-white/55 mb-5">One bill for everything you added above.</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="block text-[11px] uppercase tracking-[0.16em] text-white/50 mb-2">Name</span>
                            <input name="name" value="{{ old('name') }}" required placeholder="Your name" class="w-full rounded-2xl bg-black/70 border border-white/10 px-4 py-3 text-white placeholder-white/30 outline-none focus:border-brand-gold">
                        </label>
                        <label class="block">
                            <span class="block text-[11px] uppercase tracking-[0.16em] text-white/50 mb-2">WhatsApp</span>
                            <input name="phone" value="{{ old('phone') }}" required placeholder="6XX XXX XXX" class="w-full rounded-2xl bg-black/70 border border-white/10 px-4 py-3 text-white placeholder-white/30 outline-none focus:border-brand-gold">
                        </label>
                        <div class="block md:col-span-2">
                            <span class="block text-[11px] uppercase tracking-[0.16em] text-white/50 mb-2">Service</span>
                            <input type="hidden" name="service_type" id="svc-service" value="{{ old('service_type', 'dine_in') }}">
                            <div class="grid grid-cols-2 gap-2" id="svc-service-picks">
                                <button type="button" data-service="dine_in" class="rounded-2xl border px-4 py-3 font-semibold {{ old('service_type', 'dine_in') === 'dine_in' ? 'border-brand-gold bg-brand-gold text-[#0A0A0A]' : 'border-white/15 text-white' }}">Dine in</button>
                                <button type="button" data-service="take_away" class="rounded-2xl border px-4 py-3 font-semibold {{ old('service_type') === 'take_away' ? 'border-brand-gold bg-brand-gold text-[#0A0A0A]' : 'border-white/15 text-white' }}">Take away</button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="mt-6 w-full sm:w-auto bg-brand-gold text-[#0A0A0A] font-bold tracking-[0.12em] uppercase px-8 py-3.5 rounded-full">Place order</button>
                </div>
            @endif
        </form>
        @endif
    </div>
</div>
<div id="svc-zoom-view" hidden>
    <figure>
        <img alt="">
        <figcaption></figcaption>
    </figure>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var search = document.getElementById('svc-search');
    var items = document.querySelectorAll('#svc-items .svc-item');
    var cats = document.querySelectorAll('#svc-cats .svc-cat');
    var none = document.getElementById('svc-none');
    if (search) {
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            var shown = 0;
            var rows = items.length ? items : cats;
            rows.forEach(function (row) {
                var match = q === '' || (row.getAttribute('data-name') || '').indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) shown++;
            });
            if (none) none.classList.toggle('hidden', q === '' || shown > 0);
        });
    }
    var picks = document.getElementById('svc-service-picks');
    var service = document.getElementById('svc-service');
    if (picks && service) {
        picks.addEventListener('click', function (e) {
            var pick = e.target.closest('[data-service]');
            if (!pick) return;
            service.value = pick.getAttribute('data-service');
            picks.querySelectorAll('[data-service]').forEach(function (el) {
                var on = el === pick;
                el.classList.toggle('border-brand-gold', on);
                el.classList.toggle('bg-brand-gold', on);
                el.classList.toggle('text-[#0A0A0A]', on);
                el.classList.toggle('border-white/15', !on);
                el.classList.toggle('text-white', !on);
            });
        });
    }
    var zoom = document.getElementById('svc-zoom-view');
    var zoomImg = zoom ? zoom.querySelector('img') : null;
    var zoomTitle = zoom ? zoom.querySelector('figcaption') : null;
    document.addEventListener('click', function (e) {
        var photo = e.target.closest('.svc-zoom');
        if (photo && zoom && zoomImg) {
            e.preventDefault();
            zoomImg.src = photo.getAttribute('data-src');
            zoomImg.alt = photo.getAttribute('data-title') || '';
            if (zoomTitle) zoomTitle.textContent = photo.getAttribute('data-title') || '';
            zoom.hidden = false;
            return;
        }
        if (zoom && !zoom.hidden && e.target === zoom) {
            zoom.hidden = true;
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && zoom && !zoom.hidden) zoom.hidden = true;
    });
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.svc-qty button[data-step]');
        if (!btn) return;
        e.preventDefault();
        var input = btn.parentElement.querySelector('input[type=number]');
        if (!input) return;
        var current = parseInt(input.value, 10);
        if (isNaN(current)) current = 0;
        var next = current + parseInt(btn.getAttribute('data-step'), 10);
        if (next < 0) next = 0;
        input.value = next;
    });
})();
</script>
@endpush
