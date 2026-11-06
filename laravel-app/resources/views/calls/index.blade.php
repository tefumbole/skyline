@extends('layout.main')
@section('content')
<section class="call-board">
    <header class="call-board-head">
        <p>Call</p>
        <h1>People in the app</h1>
        <span>Audio or video stays in Skyline. On the same WiFi the call uses the hotel network.</span>
    </header>
    <p id="call-note" class="call-note" hidden></p>
    <ul id="call-directory" class="call-directory">
        <li class="call-empty">Looking for people with Skyline open…</li>
    </ul>
</section>
<style>
    .call-board { max-width: 880px; margin: 0 auto 28px; }
    .call-board-head {
        padding: 22px 22px 18px;
        border-radius: 22px;
        background: #0c1830;
        color: #f7f4ee;
    }
    .call-board-head p {
        margin: 0;
        color: #e1b368;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        font-size: 12px;
    }
    .call-board-head h1 { margin: 6px 0 8px; font-size: 32px; letter-spacing: -.03em; }
    .call-board-head span { display: block; max-width: 46rem; color: rgba(247,244,238,.78); font-weight: 600; }
    .call-note {
        margin: 14px 0 0;
        padding: 12px 14px;
        border-radius: 14px;
        background: #fff;
        color: #0c1830;
        font-weight: 800;
    }
    .call-directory { list-style: none; margin: 14px 0 0; padding: 0; display: grid; gap: 10px; }
    .call-person, .call-empty {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(12, 24, 48, .06);
    }
    .call-empty { color: #5c6b82; font-weight: 700; }
    .call-avatar {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: #0c1830;
        color: #e1b368;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        flex: 0 0 auto;
    }
    .call-person div { min-width: 0; flex: 1; }
    .call-person strong { display: block; color: #0c1830; font-size: 17px; }
    .call-person em { color: #5c6b82; font-style: normal; font-weight: 700; font-size: 13px; }
    .call-net {
        display: inline-block;
        margin-top: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        background: #f3f6fb;
        color: #0c1830;
        font-size: 12px;
        font-weight: 800;
    }
    .call-actions { display: flex; gap: 8px; flex: 0 0 auto; }
    .call-actions button {
        border: 0;
        border-radius: 999px;
        padding: 10px 14px;
        font-weight: 800;
        background: #0c1830;
        color: #e1b368;
    }
    .call-actions button[data-sky-call="video"] { background: #e1b368; color: #0c1830; }
    @media (max-width: 640px) {
        .call-person { flex-wrap: wrap; }
        .call-actions { width: 100%; }
        .call-actions button { flex: 1; }
    }
</style>
@endsection
