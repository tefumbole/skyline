@extends('beyond.layout')

@section('title', $meta['label'].' · Skyline Hotel')

@section('content')
<div class="bg-[#0c1830] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0"><a href="{{ url('/services') }}" class="hover:underline">Services</a></p>
        <h1 class="font-serif text-3xl text-white mt-2">{{ $meta['label'] }}</h1>
        <p class="text-white/70 text-sm mt-2">{{ $meta['blurb'] }} Choose quantities and place one order.</p>

        <div class="flex flex-wrap gap-2 mt-6">
            <a href="{{ route('beyond.services.show', $service) }}" class="rounded-full border px-3 py-1.5 text-sm {{ $current ? 'border-white/20 text-white/70' : 'border-brand-gold text-brand-gold' }}">All</a>
            @foreach ($categories as $cat)
                <a href="{{ route('beyond.services.show', [$service, $cat->id]) }}" class="rounded-full border px-3 py-1.5 text-sm {{ $current && (int) $current->id === (int) $cat->id ? 'border-brand-gold text-brand-gold' : 'border-white/20 text-white/80' }}">{{ $cat->name }}</a>
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

        <form method="POST" action="{{ route('restaurant.checkout') }}" class="mt-6">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $token }}">
            <div class="space-y-3">
                @forelse ($items as $product)
                    <label class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3">
                        <input type="hidden" name="product_id[]" value="{{ $product->id }}">
                        <input type="number" name="qty[]" min="0" value="{{ old('qty.'.$loop->index, 0) }}" class="w-20 rounded-md bg-black border border-white/15 px-2 py-2 text-white">
                        <span class="flex-1">
                            <span class="block font-semibold">{{ $product->name }}</span>
                            <span class="text-sm text-white/60">
                                @if ((float) $product->price > 0)
                                    {{ number_format((float) $product->price, 0) }} XAF
                                @else
                                    Ask for the price
                                @endif
                            </span>
                        </span>
                    </label>
                @empty
                    <p class="text-white/60">Nothing in this list yet. <a class="text-brand-gold underline" href="{{ url('/menu') }}">Open the full menu</a>.</p>
                @endforelse
            </div>

            @if ($items->isNotEmpty())
                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    <input name="name" value="{{ old('name') }}" required placeholder="Your name" class="rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                    <input name="phone" value="{{ old('phone') }}" required placeholder="WhatsApp number" class="rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                    <select name="service_type" class="rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                        <option value="dine_in" @if(old('service_type', 'dine_in') === 'dine_in') selected @endif>Dine in</option>
                        <option value="take_away" @if(old('service_type') === 'take_away') selected @endif>Take away</option>
                    </select>
                    <select name="restaurant_table_id" class="rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                        <option value="">Table, if dining in</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" @if((string) old('restaurant_table_id') === (string) $table->id) selected @endif>{{ $table->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="customer_comment" rows="2" placeholder="Note for the order" class="md:col-span-2 rounded-md bg-black border border-white/15 px-3 py-2 text-white">{{ old('customer_comment') }}</textarea>
                </div>
                <button type="submit" class="mt-4 bg-brand-gold text-[#0c1830] font-bold px-6 py-3 rounded-full">Place order</button>
            @endif
        </form>
    </div>
</div>
@endsection
