<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    html {
        scrollbar-gutter: stable;
        overflow-x: clip;
    }
    .sky-bar {
        display: flex;
        align-items: center;
        gap: 16px;
        background: #0A0A0A;
        color: #f6f1e6;
        padding: 10px max(22px, env(safe-area-inset-right)) 10px max(22px, env(safe-area-inset-left));
        position: sticky;
        top: 0;
        z-index: 40;
        flex-shrink: 0;
        font-family: Inter, ui-sans-serif, system-ui, sans-serif;
    }
    .sky-brand, .sky-actions, .sky-contact { display: flex; align-items: center; }
    .sky-brand { gap: 14px; min-width: 0; flex: 0 0 auto; }
    .sky-actions { gap: 16px; flex: 0 0 auto; }
    .sky-logo-mark {
        position: relative;
        width: 54px;
        height: 54px;
        flex-shrink: 0;
        display: grid;
        place-items: center;
        overflow: hidden;
    }
    .sky-logo-mark::before {
        content: "";
        position: absolute;
        inset: 2px;
        border-radius: 50%;
        background: conic-gradient(from 0deg, transparent 0 62%, #C5A059 84%, #fff6df 92%, transparent 100%);
        animation: sky-logo-spin 2.8s linear infinite;
        -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 2.5px), #000 calc(100% - 2px));
        mask: radial-gradient(farthest-side, transparent calc(100% - 2.5px), #000 calc(100% - 2px));
    }
    .sky-logo {
        width: 42px;
        height: 42px;
        object-fit: contain;
        display: block;
        position: relative;
        z-index: 1;
    }
    @keyframes sky-logo-spin { to { transform: rotate(360deg); } }
    .sky-rule { width: 1px; height: 28px; background: rgba(230, 211, 161, 0.55); flex-shrink: 0; }
    .sky-event-wrap { display: flex; align-items: center; gap: 14px; min-width: 0; }
    .sky-event {
        margin: 0;
        font-family: Cinzel, Georgia, serif;
        font-weight: 600;
        font-size: clamp(14px, 1.7vw, 20px);
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: #C5A059;
        white-space: nowrap;
    }
    .sky-count { display: flex; align-items: flex-end; gap: 10px; flex-shrink: 0; }
    .sky-count b {
        display: block;
        font-family: Cinzel, Georgia, serif;
        font-weight: 700;
        font-size: 18px;
        line-height: 1;
        color: #fff6df;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }
    .sky-count small {
        display: block;
        margin-top: 2px;
        color: #C5A059;
        font-size: 8px;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        text-align: center;
    }
    .sky-count-done {
        color: #fff6df;
        font-family: Cinzel, Georgia, serif;
        font-size: 13px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }
    .sky-hotel {
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #f4efe4;
        white-space: nowrap;
    }
    .sky-login {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 8px 14px;
        border: 1px solid rgba(255, 255, 255, 0.82);
        border-radius: 8px;
        color: #fff;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        background: transparent;
    }
    .sky-login:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
    .sky-menus {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px 10px;
        flex: 1 1 auto;
        min-width: 0;
        overflow-x: auto;
        padding: 0;
        margin: 0;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    .sky-menus::-webkit-scrollbar { display: none; }
    .sky-menus a {
        color: #C5A059;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        text-decoration: none;
        white-space: nowrap;
        font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        padding: 10px 16px;
        border: 1px solid transparent;
        border-radius: 10px;
        line-height: 1;
        transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }
    .sky-menus a:hover, .sky-menus a.on {
        color: #fff6df;
        background: rgba(197, 160, 89, 0.14);
        border-color: rgba(197, 160, 89, 0.5);
    }
    .sky-foot {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        column-gap: 16px;
        background: #0A0A0A;
        padding: 10px max(118px, env(safe-area-inset-right)) max(10px, env(safe-area-inset-bottom)) max(22px, env(safe-area-inset-left));
        font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        flex-shrink: 0;
    }
    a.sky-logo-mark { text-decoration: none; }
    .sky-credit {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        margin: 0;
        color: #C5A059;
        font-size: 11px;
        line-height: 1.3;
        white-space: nowrap;
    }
    .sky-credit a { color: inherit; text-decoration: none; white-space: nowrap; }
    .sky-credit a:hover { color: #f0cb86; }
    .sky-version {
        margin: 0;
        justify-self: center;
        text-align: center;
        color: #C5A059;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.08em;
        white-space: nowrap;
    }
    .sky-foot .sky-brand { margin-left: 0; justify-self: end; flex: 0 1 auto; }
    .sky-foot-links { display: flex; align-items: center; justify-content: flex-start; gap: 28px; }
    .sky-contact { gap: 8px; color: #C5A059; font-size: 14px; text-decoration: none; white-space: nowrap; }
    .sky-contact svg { flex-shrink: 0; color: #C5A059; }
    .sky-contact:hover { color: #f0cb86; }
    @media (max-width: 1360px) {
        .sky-hotel, .sky-actions .sky-rule { display: none; }
    }
    @media (max-width: 1180px) {
        .sky-foot { display: flex; flex-wrap: wrap; row-gap: 6px; justify-content: space-between; }
        .sky-version { order: 2; flex: 1 1 100%; text-align: center; }
        .sky-foot .sky-brand { order: 3; flex: 1 1 auto; min-width: 0; margin-left: auto; justify-content: flex-end; }
        .sky-credit { white-space: normal; flex-wrap: wrap; justify-content: flex-end; line-height: 1.35; row-gap: 2px; }
        .sky-foot-links { order: 1; flex: 1 1 auto; margin-left: 0; justify-content: flex-start; flex-wrap: wrap; gap: 4px 22px; }
        .sky-contact { min-height: 44px; }
    }
    @media (max-width: 860px) {
        .sky-bar {
            flex-wrap: wrap;
            row-gap: 8px;
            gap: 10px;
            padding: 10px max(14px, env(safe-area-inset-right)) 8px max(14px, env(safe-area-inset-left));
        }
        .sky-bar .sky-brand { gap: 10px; overflow: visible; flex: 1 1 auto; }
        .sky-menus {
            order: 3;
            flex: 1 1 100%;
            gap: 6px;
            padding-bottom: 4px;
            scroll-snap-type: x mandatory;
        }
        .sky-menus a {
            font-size: 13px;
            letter-spacing: 0.05em;
            padding: 0 14px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            scroll-snap-align: start;
        }
        .sky-login { min-height: 44px; }
        input, select, textarea { font-size: 16px; }
        .sky-event-wrap { flex-wrap: wrap; gap: 4px 10px; }
        .sky-event { font-size: clamp(10px, 2.7vw, 15px); letter-spacing: 0.04em; overflow: hidden; text-overflow: ellipsis; }
        .sky-count { gap: 8px; }
        .sky-count b { font-size: 14px; }
    }
    @media (max-width: 640px) {
        .sky-foot { align-items: stretch; padding: 12px max(16px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left)); }
        .sky-foot .sky-brand { align-items: flex-end; }
        .sky-credit { display: block; font-size: 11px; text-align: right; }
        .sky-foot-links { flex-direction: column; align-items: stretch; gap: 0; }
        .sky-contact { min-height: 48px; font-size: 15px; padding: 6px 2px; border-top: 1px solid rgba(225, 179, 104, 0.28); }
    }
    @media (max-width: 420px) {
        .sky-event { font-size: 11px; letter-spacing: 0.06em; }
        .sky-count b { font-size: 12px; }
        .sky-count small { font-size: 7px; letter-spacing: 0.06em; }
        .sky-logo-mark { width: 42px; height: 42px; }
        .sky-logo { width: 32px; height: 32px; }
        .sky-logo-mark::before { inset: 1px; }
        .sky-login { min-height: 36px; padding: 6px 10px; font-size: 12px; }
        .sky-credit { font-size: 10px; gap: 4px; }
    }
    @media (max-width: 340px) {
        .sky-event { font-size: 10px; letter-spacing: 0.03em; }
        .sky-bar { gap: 8px; }
        .sky-brand { gap: 8px; }
        .sky-logo-mark { width: 38px; height: 38px; }
        .sky-logo { width: 28px; height: 28px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sky-logo-mark::before { animation: none; }
    }
</style>
