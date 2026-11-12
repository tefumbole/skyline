<style>
    @keyframes skyDeliverBlink {
        0%, 49% { opacity: 1; }
        50%, 100% { opacity: 0; }
    }
    body.sky-deliver-blink::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: 12000;
        pointer-events: none;
        border: 12px solid #e1b368;
        box-shadow: inset 0 0 0 6px #0c1830;
        animation: skyDeliverBlink .7s steps(1, end) infinite;
    }
    #sky-deliver-banner {
        position: fixed;
        top: 14px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 12001;
        width: min(560px, calc(100vw - 24px));
        padding: 14px 16px;
        border-radius: 16px;
        background: #e1b368;
        color: #0c1830;
        text-align: center;
        box-shadow: 0 16px 40px rgba(12, 24, 48, .28);
        animation: skyDeliverBlink .7s steps(1, end) infinite;
    }
    #sky-deliver-banner strong { display: block; font-size: 18px; letter-spacing: -.02em; }
    #sky-deliver-banner p { margin: 6px 0 0; font-weight: 700; }
    #sky-deliver-banner a {
        display: inline-block;
        margin-top: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: #0c1830;
        color: #e1b368;
        font-weight: 800;
        text-decoration: none;
    }
    body.sky-deliver-blink #ready-delivery-menu > a,
    body.sky-kitchen-blink #order-lane-restaurant > a {
        animation: skyDeliverBlink .7s steps(1, end) infinite;
        background: #e1b368 !important;
        color: #0c1830 !important;
    }
    body.sky-kitchen-blink::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: 12000;
        pointer-events: none;
        border: 12px solid #e1b368;
        box-shadow: inset 0 0 0 6px #0c1830;
        animation: skyDeliverBlink .7s steps(1, end) infinite;
    }
    #sky-kitchen-banner {
        position: fixed;
        top: 14px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 12001;
        width: min(560px, calc(100vw - 24px));
        padding: 14px 16px;
        border-radius: 16px;
        background: #e1b368;
        color: #0c1830;
        text-align: center;
        box-shadow: 0 16px 40px rgba(12, 24, 48, .28);
        animation: skyDeliverBlink .7s steps(1, end) infinite;
    }
    #sky-kitchen-banner strong { display: block; font-size: 18px; letter-spacing: -.02em; }
    #sky-kitchen-banner p { margin: 6px 0 0; font-weight: 700; }
    #sky-kitchen-banner a {
        display: inline-block;
        margin-top: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: #0c1830;
        color: #e1b368;
        font-weight: 800;
        text-decoration: none;
    }
</style>
<div id="sky-deliver-banner" hidden>
    <strong>Ready for delivery</strong>
    <p data-lines></p>
    <a href="{{ route('restaurant.ready') }}">Open and deliver</a>
</div>
@php
    $blinkKitchen = false;
    try {
        $blinkUser = auth()->user();
        $blinkRole = strtolower((string) ($blinkUser ? $blinkUser->roleName() : ''));
        $blinkKitchen = $blinkUser && (
            in_array($blinkRole, ['kitchen', 'restaurant'], true)
            || \App\Services\Restaurant\RestaurantAccess::can($blinkUser, 'kitchen-orders')
            || \App\Services\Restaurant\RestaurantAccess::can($blinkUser, 'orders-restaurant-add')
        );
    } catch (\Throwable $e) {
        $blinkKitchen = false;
    }
@endphp
<div id="sky-kitchen-banner" hidden>
    <strong>Order waiting</strong>
    <p data-lines></p>
    <a href="{{ route('restaurant.orders', ['lane' => 'restaurant', 'tab' => 'awaiting']) }}">Open and accept</a>
</div>
<audio id="sky-deliver-beep" preload="auto" src="{{ url('public/beep/beep-07.mp3') }}"></audio>
<script>
    (function () {
        var url = @json(route('restaurant.alerts'));
        var banner = document.getElementById('sky-deliver-banner');
        var lines = banner ? banner.querySelector('[data-lines]') : null;
        var kitchenOn = @json($blinkKitchen);
        var kitchenBanner = document.getElementById('sky-kitchen-banner');
        var kitchenLines = kitchenBanner ? kitchenBanner.querySelector('[data-lines]') : null;
        var known = {};
        var primed = false;
        var baseTitle = document.title.replace(/^READY · /, '');
        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch];
            });
        }
        function paint(items) {
            var mine = (items || []).filter(function (item) { return item.status === 'ready' && item.mine; });
            var fresh = false;
            var next = {};
            mine.forEach(function (item) {
                next[String(item.id)] = true;
                if (primed && !known[String(item.id)]) fresh = true;
            });
            if (!primed && mine.length) fresh = true;
            known = next;
            primed = true;
            document.body.classList.toggle('sky-deliver-blink', mine.length > 0);
            var kitchen = kitchenOn ? (items || []).filter(function (item) {
                return item.status === 'awaiting' && item.lane === 'restaurant';
            }) : [];
            var onKitchenAwaiting = /[?&]lane=restaurant(?:&|$)/.test(location.search) && /[?&]tab=awaiting(?:&|$)/.test(location.search);
            document.body.classList.toggle('sky-kitchen-blink', kitchen.length > 0 && !onKitchenAwaiting);
            document.title = mine.length ? ('READY · ' + baseTitle) : (kitchen.length && !onKitchenAwaiting ? ('WAITING · ' + baseTitle) : baseTitle);
            if (kitchenBanner) kitchenBanner.hidden = kitchen.length === 0 || onKitchenAwaiting;
            if (kitchenLines) {
                kitchenLines.innerHTML = kitchen.slice(0, 4).map(function (item) {
                    return esc(item.name) + ' × ' + esc(item.qty);
                }).join('<br>');
            }
            if (kitchenBanner && banner && !banner.hidden && !kitchenBanner.hidden) {
                kitchenBanner.style.top = '150px';
            } else if (kitchenBanner) {
                kitchenBanner.style.top = '14px';
            }
            if (banner) banner.hidden = mine.length === 0;
            if (lines) {
                lines.innerHTML = mine.slice(0, 4).map(function (item) {
                    var where = item.destination || 'Ready';
                    return esc(where) + ' · ' + esc(item.name) + ' × ' + esc(item.qty);
                }).join('<br>');
            }
            if (fresh) {
                var audio = document.getElementById('sky-deliver-beep');
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(function () {});
                }
            }
        }
        function poll() {
            fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin', cache:'no-store'})
                .then(function (res) { return res.ok ? res.json() : {items:[]}; })
                .then(function (data) { paint(data.items || []); })
                .catch(function () {});
        }
        poll();
        setInterval(poll, 5000);
    })();
</script>
