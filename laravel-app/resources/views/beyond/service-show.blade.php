@extends('beyond.layout')

@section('title', $meta['label'].' · Skyline Hotel')

@push('head')
<style>
    .svc-cats { scrollbar-width: none; -webkit-overflow-scrolling: touch; }
    .svc-cats::-webkit-scrollbar { display: none; }
    .svc-qty input[type=number]::-webkit-outer-spin-button,
    .svc-qty input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .svc-qty input[type=number] { -moz-appearance: textfield; }
</style>
@endpush

@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0"><a href="{{ url('/services') }}" class="hover:underline">Services</a></p>
        <h1 class="font-serif text-3xl text-white mt-2">{{ $meta['label'] }}</h1>
        <p class="text-white/70 text-sm mt-2">{{ $meta['blurb'] }} Choose quantities and place one order.</p>

        <label class="block mt-6">
            <span class="sr-only">Search {{ $meta['label'] }}</span>
            <input id="svc-search" type="search" placeholder="Search {{ $meta['label'] }}" autocomplete="off"
                   class="w-full rounded-2xl bg-black/70 border border-brand-gold/40 px-4 py-3 text-white placeholder-white/40 outline-none focus:border-brand-gold">
        </label>

        <div class="flex md:grid md:grid-cols-4 lg:grid-cols-5 gap-3 mt-6 overflow-x-auto pb-1 svc-cats">
            <a href="{{ route('beyond.services.show', $service) }}"
               class="shrink-0 w-28 md:w-auto rounded-2xl border px-3 py-4 flex flex-col items-center text-center gap-2 {{ $current ? 'border-brand-gold/25 bg-white/[0.03] hover:border-brand-gold/70' : 'border-brand-gold bg-brand-gold/15' }}">
                <span class="w-12 h-12 rounded-full flex items-center justify-center {{ $current ? 'border border-brand-gold/50 text-brand-gold' : 'bg-brand-gold text-[#0A0A0A]' }}">
                    <i data-lucide="layout-grid" class="w-6 h-6"></i>
                </span>
                <span class="text-sm font-semibold leading-tight {{ $current ? 'text-white/90' : 'text-brand-gold' }}">All</span>
            </a>
            @foreach ($categories as $cat)
                @php $active = $current && (int) $current['id'] === (int) $cat['id']; @endphp
                <a href="{{ route('beyond.services.show', [$service, $cat['id']]) }}"
                   class="shrink-0 w-28 md:w-auto rounded-2xl border px-3 py-4 flex flex-col items-center text-center gap-2 transition-colors {{ $active ? 'border-brand-gold bg-brand-gold/15' : 'border-brand-gold/25 bg-white/[0.03] hover:border-brand-gold/70' }}">
                    <span class="w-16 h-16 rounded-full flex items-center justify-center overflow-hidden {{ $active ? 'ring-2 ring-brand-gold' : 'border border-brand-gold/50 text-brand-gold' }}">
                        @if ($cat['image'])
                            <img src="{{ $cat['image'] }}" alt="" class="w-full h-full object-cover">
                        @else
                            <i data-lucide="{{ $cat['icon'] }}" class="w-6 h-6"></i>
                        @endif
                    </span>
                    <span class="text-sm font-semibold leading-tight {{ $active ? 'text-brand-gold' : 'text-white/90' }}">{{ $cat['name'] }}</span>
                    <span class="text-[11px] text-white/45">{{ $cat['count'] }}</span>
                </a>
            @endforeach
        </div>

        @if (session('not_permitted'))
            <div class="mt-6 bg-red-950/40 border border-red-400/40 text-red-200 rounded-xl px-4 py-3 text-sm">{{ session('not_permitted') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 bg-red-950/40 border border-red-400/40 text-red-200 rounded-xl px-4 py-3 text-sm">
                <ul class="list-disc pl-5 mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('restaurant.checkout') }}" class="mt-6" id="svc-order">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $token }}">
            @if ($current)
                <h2 class="text-lg font-semibold text-white mb-3">{{ $current['name'] }}</h2>
            @endif
            <p id="svc-none" class="hidden text-white/60 mb-3">No matches for that search.</p>
            <div class="space-y-3" id="svc-items">
                @forelse ($items as $product)
                    @php
                        $img = \App\Support\ServiceCatalog::productImage($product);
                        $icon = \App\Support\ServiceCatalog::categoryIcon($product->name);
                    @endphp
                    <label class="svc-item flex items-center gap-3 sm:gap-4 rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3" data-name="{{ strtolower($product->name) }}">
                        <input type="hidden" name="product_id[]" value="{{ $product->id }}">
                        @if ($img)
                            <img src="{{ $img }}" alt="{{ $product->name }}" class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover bg-white/5 border border-brand-gold/20 shrink-0" loading="lazy">
                        @else
                            <span class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-white/5 flex items-center justify-center text-brand-gold/70 shrink-0">
                                <i data-lucide="{{ $icon }}" class="w-7 h-7"></i>
                            </span>
                        @endif
                        <span class="flex-1 min-w-0">
                            <span class="block font-semibold leading-snug">{{ $product->name }}</span>
                            <span class="text-sm text-brand-gold font-bold">
                                @if ((float) $product->price > 0)
                                    {{ number_format((float) $product->price, 0) }} XAF
                                @else
                                    Ask for the price
                                @endif
                            </span>
                        </span>
                        <span class="flex items-center gap-2 shrink-0 svc-qty">
                            <button type="button" class="w-9 h-9 rounded-full border border-brand-gold/60 text-brand-gold text-lg leading-none" data-step="-1" aria-label="Decrease">&minus;</button>
                            <input type="number" name="qty[]" min="0" value="{{ old('qty.'.$loop->index, 0) }}" class="w-14 text-center rounded-md bg-black border border-white/15 px-2 py-2 text-white tabular-nums">
                            <button type="button" class="w-9 h-9 rounded-full border border-brand-gold/60 text-brand-gold text-lg leading-none" data-step="1" aria-label="Increase">+</button>
                        </span>
                    </label>
                @empty
                    <p class="text-white/60">Nothing in this list yet.</p>
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
                        <label class="block">
                            <span class="block text-[11px] uppercase tracking-[0.16em] text-white/50 mb-2">Service</span>
                            <select name="service_type" class="w-full rounded-2xl bg-black/70 border border-white/10 px-4 py-3 text-white outline-none focus:border-brand-gold">
                                <option value="dine_in" @if(old('service_type', 'dine_in') === 'dine_in') selected @endif>Dine in</option>
                                <option value="take_away" @if(old('service_type') === 'take_away') selected @endif>Take away</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit" class="mt-6 w-full sm:w-auto bg-brand-gold text-[#0A0A0A] font-bold tracking-[0.12em] uppercase px-8 py-3.5 rounded-full">Place order</button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var search = document.getElementById('svc-search');
    var items = document.querySelectorAll('#svc-items .svc-item');
    var none = document.getElementById('svc-none');
    if (search) {
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            var shown = 0;
            items.forEach(function (row) {
                var match = q === '' || (row.getAttribute('data-name') || '').indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) shown++;
            });
            if (none) none.classList.toggle('hidden', q === '' || shown > 0);
        });
    }
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
