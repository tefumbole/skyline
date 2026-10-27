<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
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
        width: 46px;
        height: 46px;
        flex-shrink: 0;
        display: grid;
        place-items: center;
    }
    .sky-logo-mark::before {
        content: "";
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        background: conic-gradient(from 0deg, transparent 0 62%, #C5A059 84%, #fff6df 92%, transparent 100%);
        animation: sky-logo-spin 2.8s linear infinite;
        -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 2.5px), #000 calc(100% - 2px));
        mask: radial-gradient(farthest-side, transparent calc(100% - 2.5px), #000 calc(100% - 2px));
    }
    .sky-logo {
        width: 46px;
        height: 46px;
        object-fit: contain;
        display: block;
        position: relative;
        z-index: 1;
        animation: sky-logo-glow 2.6s ease-in-out infinite;
    }
    @keyframes sky-logo-spin { to { transform: rotate(360deg); } }
    @keyframes sky-logo-glow {
        0%, 100% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(225, 179, 104, 0)); }
        50% { transform: scale(1.08); filter: drop-shadow(0 0 8px rgba(225, 179, 104, 0.9)); }
    }
    .sky-rule { width: 1px; height: 28px; background: rgba(230, 211, 161, 0.55); flex-shrink: 0; }
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
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        background: #0A0A0A;
        padding: 14px max(22px, env(safe-area-inset-right)) max(14px, env(safe-area-inset-bottom)) max(22px, env(safe-area-inset-left));
        font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        flex-shrink: 0;
    }
    a.sky-logo-mark { text-decoration: none; }
    .sky-credit {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #C5A059;
        font-size: 14px;
        white-space: nowrap;
    }
    .sky-credit a { color: inherit; text-decoration: none; white-space: nowrap; }
    .sky-credit a:hover { color: #f0cb86; }
    .sky-brand-text { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; min-width: 0; }
    .sky-version { margin: 0; color: #C5A059; font-size: 12px; font-weight: 600; letter-spacing: 0.08em; white-space: nowrap; }
    .sky-foot .sky-brand { margin-left: auto; flex: 0 1 auto; }
    .sky-foot-links { display: flex; align-items: center; justify-content: flex-start; gap: 28px; }
    .sky-contact { gap: 8px; color: #C5A059; font-size: 14px; text-decoration: none; white-space: nowrap; }
    .sky-contact svg { flex-shrink: 0; color: #C5A059; }
    .sky-contact:hover { color: #f0cb86; }
    @media (max-width: 1360px) {
        .sky-hotel, .sky-actions .sky-rule { display: none; }
    }
    @media (max-width: 1180px) {
        .sky-foot { flex-wrap: wrap; row-gap: 8px; }
        .sky-foot .sky-brand { flex: 1 1 100%; min-width: 0; margin-left: 0; justify-content: flex-end; }
        .sky-credit { white-space: normal; flex-wrap: wrap; line-height: 1.45; row-gap: 2px; }
        .sky-foot-links { flex: 1 1 100%; margin-left: 0; justify-content: flex-start; flex-wrap: wrap; gap: 4px 22px; }
        .sky-contact { min-height: 44px; }
    }
    @media (max-width: 860px) {
        .sky-bar { gap: 10px; padding: 10px max(14px, env(safe-area-inset-right)) 10px max(14px, env(safe-area-inset-left)); }
        .sky-bar .sky-brand { gap: 10px; overflow: hidden; }
        .sky-menus a { font-size: 13px; letter-spacing: 0.05em; padding: 8px 12px; }
        .sky-event { font-size: clamp(10px, 2.7vw, 15px); letter-spacing: 0.04em; overflow: hidden; text-overflow: ellipsis; }
    }
    @media (max-width: 640px) {
        .sky-foot { align-items: stretch; padding: 12px max(16px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left)); }
        .sky-foot .sky-brand { align-items: flex-start; }
        .sky-foot .sky-rule { height: 22px; margin-top: 4px; }
        .sky-credit { display: block; font-size: 14px; text-align: right; }
        .sky-foot-links { flex-direction: column; align-items: stretch; gap: 0; }
        .sky-contact { min-height: 48px; font-size: 15px; padding: 6px 2px; border-top: 1px solid rgba(225, 179, 104, 0.28); }
    }
    @media (max-width: 420px) {
        .sky-event { font-size: 11px; letter-spacing: 0.06em; }
        .sky-logo-mark, .sky-logo { width: 36px; height: 36px; }
        .sky-logo-mark::before { inset: -3px; }
        .sky-login { min-height: 36px; padding: 6px 10px; font-size: 12px; }
        .sky-credit { font-size: 13px; gap: 6px; }
    }
    @media (max-width: 340px) {
        .sky-event { font-size: 10px; letter-spacing: 0.03em; }
        .sky-bar { gap: 8px; }
        .sky-brand { gap: 8px; }
        .sky-logo-mark, .sky-logo { width: 32px; height: 32px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sky-logo, .sky-logo-mark::before { animation: none; }
    }
</style>
