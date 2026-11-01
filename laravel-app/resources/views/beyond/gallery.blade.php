@extends('beyond.layout')

@section('title', 'Gallery | Beyond Enterprise')
@section('meta_description', 'Explore events, projects, and highlights from Beyond Enterprise — photos and videos from our work across Kigali and beyond.')

@section('content')
@php
    $groups = [];
    $slides = [];
    foreach ($items as $item) {
        $key = $item->eventKey();
        if (! isset($groups[$key])) {
            $groups[$key] = ['label' => $item->eventHeading(), 'items' => []];
        }
        $groups[$key]['items'][] = $item;
        if ($item->type === 'image' && $item->fileUrl()) {
            $slides[] = [
                'src' => $item->fileUrl(),
                'alt' => $item->title ?: 'Gallery image',
            ];
        }
    }
@endphp

<section class="pt-3 pb-6 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (count($slides))
            <div class="flex justify-center mb-3">
                <button type="button" id="gallery-slideshow-open" class="gallery-show-open">
                    <svg viewBox="0 0 24 24" aria-hidden="true" width="18" height="18" fill="currentColor"><path d="M8 5.14v13.72a1 1 0 0 0 1.54.84l10.16-6.86a1 1 0 0 0 0-1.68L9.54 4.3A1 1 0 0 0 8 5.14z"/></svg>
                    Slideshow
                </button>
            </div>
        @endif
        @if ($items->isEmpty())
            <div class="text-center py-12 text-gray-500">
                <i data-lucide="image" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                <p class="text-base">Gallery items will appear here once added in the admin Gallery.</p>
            </div>
        @else
            @foreach ($groups as $group)
                <h2 class="font-serif text-2xl text-[#3f2a12] border-b-2 border-brand-gold pb-2 mb-4 {{ $loop->first ? '' : 'mt-8' }}">{{ $group['label'] }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($group['items'] as $item)
                        @include('beyond.partials.gallery_item', ['item' => $item])
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
</section>

<div id="gallery-lightbox" class="fixed inset-0 z-50 hidden bg-black/90 items-center justify-center p-4" onclick="closeGalleryLightbox(event)">
    <button type="button" class="absolute top-4 right-4 text-white text-3xl leading-none" onclick="closeGalleryLightbox(event)">&times;</button>
    <img id="gallery-lightbox-img" src="" alt="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl">
</div>

<div id="gallery-show" class="gallery-show" hidden>
    <div class="gallery-show-stage" id="gallery-show-stage">
        <img class="gallery-show-photo is-on" alt="" id="gallery-show-a">
        <img class="gallery-show-photo" alt="" id="gallery-show-b">
    </div>
    <button type="button" class="gallery-show-close" id="gallery-show-close" aria-label="Close slideshow">&times;</button>
    <button type="button" class="gallery-show-nav is-prev" id="gallery-show-prev" aria-label="Previous image">&#8249;</button>
    <button type="button" class="gallery-show-nav is-next" id="gallery-show-next" aria-label="Next image">&#8250;</button>
    <div class="gallery-show-bar">
        <button type="button" id="gallery-show-play" aria-label="Pause slideshow">Pause</button>
        <span id="gallery-show-count"></span>
    </div>
</div>

@endsection

@push('scripts')
<style>
    .gallery-show-open {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 48px;
        padding: 12px 26px;
        border: 1px solid #C5A059;
        border-radius: 999px;
        background: #0A0A0A;
        color: #fff6df;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        cursor: pointer;
    }
    .gallery-show-open:hover { background: #1a1a1a; color: #fff; }
    .gallery-show {
        position: fixed;
        inset: 0;
        z-index: 100000;
        background: #050505;
        color: #fff;
    }
    .gallery-show-stage { position: absolute; inset: 0; }
    .gallery-show-photo {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        max-width: none;
        object-fit: contain;
        opacity: 0;
        transition: opacity 2.6s ease, transform 2.6s ease;
        pointer-events: none;
    }
    .gallery-show-photo[data-motion="fade"] { transform: scale(1.08); }
    .gallery-show-photo[data-motion="right"] { transform: translate3d(12%, 0, 0) scale(1.02); }
    .gallery-show-photo[data-motion="left"] { transform: translate3d(-12%, 0, 0) scale(1.02); }
    .gallery-show-photo[data-motion="rise"] { transform: translate3d(0, 10%, 0); }
    .gallery-show-photo[data-motion="zoom"] { transform: scale(0.86); }
    .gallery-show-photo.is-on { opacity: 1; transform: none; }
    .gallery-show-close,
    .gallery-show-nav,
    .gallery-show-bar button {
        border: 0;
        background: rgba(10, 10, 10, 0.55);
        color: #fff6df;
        cursor: pointer;
    }
    .gallery-show-close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 48px;
        height: 48px;
        border-radius: 999px;
        font-size: 32px;
        line-height: 1;
    }
    .gallery-show-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 52px;
        height: 72px;
        border-radius: 12px;
        font-size: 42px;
        line-height: 1;
    }
    .gallery-show-nav.is-prev { left: 16px; }
    .gallery-show-nav.is-next { right: 16px; }
    .gallery-show-bar {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        font-size: 13px;
        letter-spacing: 0.08em;
    }
    .gallery-show-bar button {
        min-height: 36px;
        padding: 6px 14px;
        border-radius: 999px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        font-size: 12px;
    }
    @media (prefers-reduced-motion: reduce) {
        .gallery-show-photo { transition: none; transform: none; }
    }
</style>
<script async src="https://www.tiktok.com/embed.js"></script>
<script async src="//www.instagram.com/embed.js"></script>
<script>
document.querySelectorAll('.gallery-lightbox-trigger').forEach(function (img) {
    img.addEventListener('click', function () {
        var lb = document.getElementById('gallery-lightbox');
        var full = document.getElementById('gallery-lightbox-img');
        full.src = img.getAttribute('data-full');
        full.alt = img.alt;
        lb.classList.remove('hidden');
        lb.classList.add('flex');
    });
});
function closeGalleryLightbox(e) {
    if (e.target.id === 'gallery-lightbox' || e.target.tagName === 'BUTTON') {
        var lb = document.getElementById('gallery-lightbox');
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        document.getElementById('gallery-lightbox-img').src = '';
    }
}

(function () {
    var slides = @json($slides);
    var openBtn = document.getElementById('gallery-slideshow-open');
    var root = document.getElementById('gallery-show');
    if (!openBtn || !root || !slides.length) return;
    var photoA = document.getElementById('gallery-show-a');
    var photoB = document.getElementById('gallery-show-b');
    var count = document.getElementById('gallery-show-count');
    var playBtn = document.getElementById('gallery-show-play');
    var motions = ['fade', 'right', 'left', 'rise', 'zoom'];
    var index = 0;
    var useA = true;
    var playing = true;
    var timer = null;

    function reveal(incoming, outgoing) {
        incoming.classList.remove('is-on');
        void incoming.offsetWidth;
        incoming.classList.add('is-on');
        outgoing.classList.remove('is-on');
    }
    function show(next) {
        index = (next + slides.length) % slides.length;
        var incoming = useA ? photoB : photoA;
        var outgoing = useA ? photoA : photoB;
        var motion = motions[index % motions.length];
        incoming.setAttribute('data-motion', motion);
        incoming.alt = slides[index].alt;
        incoming.classList.remove('is-on');
        function go() { reveal(incoming, outgoing); }
        if (incoming.getAttribute('src') === slides[index].src && incoming.complete) {
            go();
        } else {
            incoming.onload = go;
            incoming.src = slides[index].src;
        }
        useA = !useA;
        count.textContent = (index + 1) + ' / ' + slides.length;
    }
    function arm() {
        clearTimeout(timer);
        if (!playing || slides.length < 2) return;
        timer = setTimeout(function () { show(index + 1); arm(); }, 8000);
    }
    function openShow() {
        root.hidden = false;
        document.body.style.overflow = 'hidden';
        useA = true;
        photoA.setAttribute('data-motion', 'fade');
        photoA.classList.add('is-on');
        photoB.classList.remove('is-on');
        photoA.src = slides[0].src;
        photoA.alt = slides[0].alt;
        index = 0;
        count.textContent = '1 / ' + slides.length;
        playing = true;
        playBtn.textContent = 'Pause';
        arm();
    }
    function closeShow() {
        root.hidden = true;
        document.body.style.overflow = '';
        clearTimeout(timer);
    }
    openBtn.addEventListener('click', openShow);
    document.getElementById('gallery-show-close').addEventListener('click', closeShow);
    document.getElementById('gallery-show-prev').addEventListener('click', function () { show(index - 1); arm(); });
    document.getElementById('gallery-show-next').addEventListener('click', function () { show(index + 1); arm(); });
    playBtn.addEventListener('click', function () {
        playing = !playing;
        playBtn.textContent = playing ? 'Pause' : 'Play';
        playBtn.setAttribute('aria-label', playing ? 'Pause slideshow' : 'Play slideshow');
        arm();
    });
    document.addEventListener('keydown', function (e) {
        if (root.hidden) return;
        if (e.key === 'Escape') closeShow();
        if (e.key === 'ArrowRight') { show(index + 1); arm(); }
        if (e.key === 'ArrowLeft') { show(index - 1); arm(); }
    });
})();
</script>
@endpush
