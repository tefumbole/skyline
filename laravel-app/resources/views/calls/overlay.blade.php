<div id="sky-call-root" hidden>
    <section id="sky-call-stage" hidden>
        <video id="sky-call-remote" autoplay playsinline></video>
        <video id="sky-call-local" autoplay muted playsinline></video>
        <div id="sky-call-voice">
            <strong id="sky-call-voice-name"></strong>
            <span id="sky-call-voice-state">Calling</span>
        </div>
        <div id="sky-call-bar">
            <div>
                <strong id="sky-call-name"></strong>
                <span id="sky-call-meta"></span>
            </div>
            <div class="sky-call-controls">
                <button type="button" id="sky-call-mute">Mute</button>
                <button type="button" id="sky-call-camera" hidden>Camera</button>
                <button type="button" id="sky-call-hangup">Hang up</button>
            </div>
        </div>
    </section>
    <section id="sky-call-incoming" hidden>
        <p id="sky-call-incoming-kicker">Incoming call</p>
        <strong id="sky-call-incoming-name"></strong>
        <span id="sky-call-incoming-role"></span>
        <div>
            <button type="button" id="sky-call-decline">Decline</button>
            <button type="button" id="sky-call-accept">Accept</button>
        </div>
    </section>
    <audio id="sky-call-remote-audio" autoplay playsinline></audio>
</div>
<p id="sky-call-toast" hidden></p>
<style>
    #sky-call-stage, #sky-call-incoming { position: fixed; z-index: 14000; }
    #sky-call-stage {
        inset: 0;
        background: #0c1830;
        color: #f7f4ee;
    }
    #sky-call-remote, #sky-call-local {
        background: #07101f;
    }
    #sky-call-remote {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    #sky-call-local {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 128px;
        height: 170px;
        object-fit: cover;
        border-radius: 18px;
        border: 2px solid #e1b368;
    }
    #sky-call-voice {
        position: absolute;
        inset: 0 0 108px 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 24px;
    }
    #sky-call-voice strong { font-size: 36px; letter-spacing: -.03em; }
    #sky-call-voice span { margin-top: 8px; color: #e1b368; font-weight: 800; }
    #sky-call-stage.is-video #sky-call-voice { display: none; }
    #sky-call-stage:not(.is-video) #sky-call-remote,
    #sky-call-stage:not(.is-video) #sky-call-local { display: none; }
    #sky-call-bar {
        position: absolute;
        left: 12px;
        right: 12px;
        bottom: 12px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        padding: 12px;
        border-radius: 18px;
        background: rgba(7, 16, 31, .88);
    }
    #sky-call-bar strong { display: block; }
    #sky-call-meta { color: #e1b368; font-weight: 700; font-size: 13px; }
    .sky-call-controls { display: flex; gap: 8px; }
    .sky-call-controls button, #sky-call-incoming button {
        border: 0;
        border-radius: 999px;
        padding: 10px 14px;
        font-weight: 800;
    }
    #sky-call-mute, #sky-call-camera { background: #f7f4ee; color: #0c1830; }
    #sky-call-hangup, #sky-call-decline { background: #9d3040; color: #fff; }
    #sky-call-incoming {
        left: 50%;
        top: 18px;
        transform: translateX(-50%);
        width: min(440px, calc(100vw - 20px));
        padding: 16px;
        border-radius: 20px;
        background: #e1b368;
        color: #0c1830;
        text-align: center;
        box-shadow: 0 18px 44px rgba(12, 24, 48, .28);
    }
    #sky-call-incoming p { margin: 0; font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    #sky-call-incoming strong { display: block; margin-top: 4px; font-size: 24px; }
    #sky-call-incoming span { display: block; font-weight: 700; }
    #sky-call-incoming div { display: flex; gap: 8px; margin-top: 12px; }
    #sky-call-incoming button { flex: 1; }
    #sky-call-accept { background: #0c1830; color: #e1b368; }
    #sky-call-remote-audio { display: none; }
    #sky-call-toast {
        position: fixed;
        left: 50%;
        bottom: 18px;
        transform: translateX(-50%);
        z-index: 14001;
        width: min(440px, calc(100vw - 24px));
        margin: 0;
        padding: 12px 14px;
        border-radius: 14px;
        background: #0c1830;
        color: #f7f4ee;
        text-align: center;
        font-weight: 800;
    }
</style>
<script>
window.SKY_CALL = {
    online: @json(route('calls.online')),
    heartbeat: @json(route('calls.heartbeat')),
    store: @json(route('calls.store')),
    base: @json(url('/calls'))
};
</script>
<script src="{{ asset('public/js/staff-call.js') }}?v=2"></script>
