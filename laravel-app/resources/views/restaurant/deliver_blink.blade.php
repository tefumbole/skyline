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
    #sky-deliver-banner a, #sky-deliver-banner button {
        display: inline-block;
        margin: 8px 6px 0;
        padding: 8px 14px;
        border: 0;
        border-radius: 999px;
        background: #0c1830;
        color: #e1b368;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
    }
    body.sky-deliver-blink #ready-delivery-menu > a {
        animation: skyDeliverBlink .7s steps(1, end) infinite;
        background: #e1b368 !important;
        color: #0c1830 !important;
    }
</style>
<div id="sky-deliver-banner" hidden>
    <strong>Ready for delivery</strong>
    <p data-lines></p>
    <a href="{{ route('restaurant.ready') }}">Open</a>
    <button type="button" id="sky-deliver-dismiss">Dismiss</button>
</div>
<audio id="sky-deliver-beep" preload="auto" src="{{ url('public/beep/beep-07.mp3') }}"></audio>
<script>
    (function () {
        var url = @json(route('restaurant.alerts'));
        var banner = document.getElementById('sky-deliver-banner');
        var lines = banner ? banner.querySelector('[data-lines]') : null;
        var userId = @json((int) auth()->id());
        var seenKey = 'skyline-order-seen-u' + userId;
        var known = {};
        var primed = false;
        var latest = [];
        var baseTitle = document.title.replace(/^READY · /, '');
        function seenMap() { try { return JSON.parse(localStorage.getItem(seenKey) || '{}'); } catch (e) { return {}; } }
        function saveSeen(seen) { try { localStorage.setItem(seenKey, JSON.stringify(seen)); } catch (e) {} }
        function rememberReady() {
            var seen = seenMap();
            latest.forEach(function (item) {
                if (item.status === 'ready' && item.mine) seen[String(item.id)] = Date.now();
            });
            saveSeen(seen);
            paint(latest);
        }
        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch];
            });
        }
        function paint(items) {
            latest = items || [];
            var seen = seenMap();
            var mine = latest.filter(function (item) {
                return item.status === 'ready' && item.mine && !seen[String(item.id)];
            });
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
            document.body.classList.remove('sky-kitchen-blink');
            document.title = mine.length ? ('READY · ' + baseTitle) : baseTitle.replace(/^WAITING · /, '');
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
        document.addEventListener('click', function (event) {
            var target = event.target && event.target.closest ? event.target : null;
            if (!target) return;
            if (target.closest('#sky-deliver-dismiss')) {
                event.preventDefault();
                rememberReady();
                return;
            }
            if (target.closest('#sky-deliver-banner a')) {
                rememberReady();
                return;
            }
            if (target.closest('#sky-order-pop .go, #sky-order-pop [data-dismiss-id], #sky-order-pop-close')) {
                setTimeout(function () { paint(latest); }, 0);
            }
        });
        poll();
        setInterval(poll, 5000);
    })();
</script>
