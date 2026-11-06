(function () {
    var cfg = window.SKY_CALL || {};
    var root = document.getElementById('sky-call-root');
    if (!cfg.base || !root) return;

    var stage = document.getElementById('sky-call-stage');
    var incoming = document.getElementById('sky-call-incoming');
    var remoteVideo = document.getElementById('sky-call-remote');
    var localVideo = document.getElementById('sky-call-local');
    var remoteAudio = document.getElementById('sky-call-remote-audio');
    var state = 'idle';
    var callId = 0;
    var mode = 'audio';
    var role = '';
    var otherName = '';
    var offerSdp = '';
    var pc = null;
    var localStream = null;
    var remoteReady = false;
    var answerApplied = false;
    var pendingLocal = [];
    var pendingRemote = [];
    var seenIce = {};
    var lastSignal = 0;
    var pollTimer = null;
    var heartTimer = null;
    var clockTimer = null;
    var connectTimer = null;
    var startedAt = 0;
    var audioCtx = null;
    var ringTimer = null;
    var ringing = false;
    var muted = false;
    var cameraOff = false;
    var pageTitle = document.title;

    function token() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function postJson(url, body, keepalive) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            keepalive: !!keepalive,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body || {})
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                data._status = res.status;
                return data;
            });
        });
    }

    function getJson(url) {
        return fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                data._status = res.status;
                return data;
            });
        });
    }

    function audioConstraints() {
        return {
            echoCancellation: { ideal: true },
            noiseSuppression: { ideal: true },
            autoGainControl: { ideal: true },
            channelCount: { ideal: 1 }
        };
    }

    function cleanSdp(sdp) {
        return String(sdp || '').replace(/a=extmap-allow-mixed\r?\n/g, '');
    }

    function videoConstraints(wide) {
        return {
            facingMode: 'user',
            width: wide ? { ideal: 1920, max: 1920 } : { ideal: 1280, max: 1280 },
            height: wide ? { ideal: 1080, max: 1080 } : { ideal: 720, max: 720 },
            frameRate: { ideal: 30, max: 30 }
        };
    }

    function preferCodecs(connection) {
        if (!window.RTCRtpSender || !RTCRtpSender.getCapabilities || !connection.getTransceivers) return;
        var list = connection.getTransceivers();
        for (var i = 0; i < list.length; i++) {
            var transceiver = list[i];
            if (!transceiver.setCodecPreferences || !transceiver.sender || !transceiver.sender.track) continue;
            var kind = transceiver.sender.track.kind;
            var caps = RTCRtpSender.getCapabilities(kind);
            if (!caps || !caps.codecs) continue;
            var preferred = kind === 'audio' ? ['audio/opus'] : ['video/H264', 'video/VP9', 'video/VP8'];
            var rank = {};
            for (var p = 0; p < preferred.length; p++) rank[preferred[p].toLowerCase()] = p;
            var codecs = caps.codecs.slice().sort(function (a, b) {
                var ra = rank[(a.mimeType || '').toLowerCase()];
                var rb = rank[(b.mimeType || '').toLowerCase()];
                if (ra === undefined) ra = 100;
                if (rb === undefined) rb = 100;
                return ra - rb;
            });
            try { transceiver.setCodecPreferences(codecs); } catch (e) {}
        }
    }

    function tuneSender(sender) {
        if (!sender || !sender.getParameters || !sender.track) return;
        var params = sender.getParameters();
        if (!params.encodings || !params.encodings.length) params.encodings = [{}];
        if (sender.track.kind === 'video') {
            params.encodings[0].maxBitrate = 3000000;
            params.encodings[0].maxFramerate = 30;
            params.degradationPreference = 'balanced';
        } else {
            params.encodings[0].maxBitrate = 96000;
        }
        sender.setParameters(params).catch(function () {});
    }

    function tuneReceivers() {
        if (!pc || !pc.getReceivers) return;
        var list = pc.getReceivers();
        for (var i = 0; i < list.length; i++) {
            if ('playoutDelayHint' in list[i]) list[i].playoutDelayHint = 0.02;
            if ('jitterBufferTarget' in list[i]) list[i].jitterBufferTarget = 30;
        }
    }

    function tuneAll() {
        if (!pc) return;
        var senders = pc.getSenders ? pc.getSenders() : [];
        for (var i = 0; i < senders.length; i++) tuneSender(senders[i]);
    }

    function iceConfig() {
        return {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ],
            iceTransportPolicy: 'all',
            bundlePolicy: 'max-bundle',
            rtcpMuxPolicy: 'require',
            iceCandidatePoolSize: 4
        };
    }

    function openMedia(kind) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.RTCPeerConnection) {
            return Promise.reject(new Error('browser'));
        }
        var audio = audioConstraints();
        var request = kind === 'video'
            ? navigator.mediaDevices.getUserMedia({ audio: audio, video: videoConstraints(false) })
            : navigator.mediaDevices.getUserMedia({ audio: audio, video: false });
        return request.then(function (stream) {
            var audioTrack = stream.getAudioTracks()[0];
            var videoTrack = stream.getVideoTracks()[0];
            if (audioTrack && 'contentHint' in audioTrack) audioTrack.contentHint = 'speech';
            if (videoTrack && 'contentHint' in videoTrack) videoTrack.contentHint = 'detail';
            return stream;
        });
    }

    function stopStream() {
        if (!localStream) return;
        var tracks = localStream.getTracks();
        for (var i = 0; i < tracks.length; i++) tracks[i].stop();
        localStream = null;
    }

    function mediaError(error, answering) {
        var name = error && (error.name || error.message);
        if (name === 'browser') return 'This browser cannot place a call. Open Skyline in Chrome or Safari.';
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || name === 'NotFoundError' || name === 'SecurityError' || name === 'NotReadableError') {
            if (mode === 'video') return answering ? 'Allow the camera and microphone to answer.' : 'Allow the camera and microphone to start a video call.';
            return answering ? 'Allow the microphone to answer.' : 'Allow the microphone to start a call.';
        }
        return 'The call could not start.';
    }

    var failTimer = null;
    var toastTimer = null;
    function setNote(text) {
        var note = document.getElementById('call-note');
        if (note) {
            note.hidden = !text;
            note.textContent = text || '';
        }
        var toast = document.getElementById('sky-call-toast');
        if (!toast) return;
        if (toastTimer) clearTimeout(toastTimer);
        if (!text) {
            toast.hidden = true;
            return;
        }
        toast.hidden = false;
        toast.textContent = text;
        toastTimer = setTimeout(function () { toast.hidden = true; }, 4200);
    }

    function showRoot(on) {
        root.hidden = !on;
    }

    function paintCall(label) {
        document.getElementById('sky-call-name').textContent = otherName || 'Call';
        document.getElementById('sky-call-voice-name').textContent = otherName || 'Call';
        document.getElementById('sky-call-voice-state').textContent = label || 'Calling';
        document.getElementById('sky-call-meta').textContent = label || '';
        stage.className = mode === 'video' ? 'is-video' : '';
        stage.hidden = false;
        incoming.hidden = true;
        document.getElementById('sky-call-camera').hidden = mode !== 'video';
        showRoot(true);
    }

    function showIncoming(data) {
        document.getElementById('sky-call-incoming-name').textContent = data.name || 'Staff';
        document.getElementById('sky-call-incoming-role').textContent = (data.role || 'Staff') + ' · ' + (data.mode === 'video' ? 'Video' : 'Audio');
        document.getElementById('sky-call-accept').textContent = data.mode === 'video' ? 'Accept video' : 'Accept';
        incoming.hidden = false;
        stage.hidden = true;
        showRoot(true);
        document.title = 'Call · ' + (data.name || 'Staff');
        ringStart();
    }

    function ringStart() {
        if (ringing) return;
        var Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        if (!audioCtx) audioCtx = new Ctx();
        if (audioCtx.state === 'suspended') audioCtx.resume();
        ringing = true;
        var pulse = function () {
            if (!ringing || !audioCtx) return;
            var now = audioCtx.currentTime;
            var first = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            first.type = 'sine';
            first.frequency.value = 440;
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.05, now + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28);
            first.connect(gain);
            gain.connect(audioCtx.destination);
            first.start(now);
            first.stop(now + 0.3);
            var second = audioCtx.createOscillator();
            var gain2 = audioCtx.createGain();
            second.type = 'sine';
            second.frequency.value = 554;
            gain2.gain.setValueAtTime(0.0001, now + 0.18);
            gain2.gain.exponentialRampToValueAtTime(0.05, now + 0.2);
            gain2.gain.exponentialRampToValueAtTime(0.0001, now + 0.5);
            second.connect(gain2);
            gain2.connect(audioCtx.destination);
            second.start(now + 0.18);
            second.stop(now + 0.52);
            ringTimer = setTimeout(pulse, 1700);
        };
        pulse();
    }

    function ringStop() {
        ringing = false;
        if (ringTimer) clearTimeout(ringTimer);
        ringTimer = null;
    }

    function sendIce(candidate) {
        if (!callId || !candidate) return;
        postJson(cfg.base + '/' + callId + '/ice', {
            candidate: candidate.candidate,
            sdpMid: candidate.sdpMid,
            sdpMLineIndex: candidate.sdpMLineIndex
        }).catch(function () {});
    }

    function flushLocal() {
        var queued = pendingLocal.slice();
        pendingLocal = [];
        for (var i = 0; i < queued.length; i++) sendIce(queued[i]);
    }

    function addRemoteIce(item) {
        if (!item || !item.candidate || seenIce[item.candidate]) return;
        seenIce[item.candidate] = true;
        if (!pc || !remoteReady) {
            pendingRemote.push(item);
            return;
        }
        pc.addIceCandidate({
            candidate: item.candidate,
            sdpMid: item.sdpMid || null,
            sdpMLineIndex: item.sdpMLineIndex
        }).catch(function () {});
    }

    function flushRemote() {
        var queued = pendingRemote.slice();
        pendingRemote = [];
        for (var i = 0; i < queued.length; i++) {
            seenIce[queued[i].candidate] = false;
            addRemoteIce(queued[i]);
        }
    }

    function attachRemote(stream) {
        var sink = mode === 'video' ? remoteVideo : remoteAudio;
        var other = mode === 'video' ? remoteAudio : remoteVideo;
        if (other) other.srcObject = null;
        if (!sink) return;
        sink.srcObject = stream;
        var play = sink.play();
        if (play && play.catch) play.catch(function () {});
    }

    function showLocalPreview(stream) {
        if (mode !== 'video' || !localVideo) return;
        localVideo.srcObject = stream;
        var localPlay = localVideo.play();
        if (localPlay && localPlay.catch) localPlay.catch(function () {});
    }

    function noteLink(stateName) {
        if (!pc) return;
        if (stateName === 'connected' || stateName === 'completed') {
            if (failTimer) clearTimeout(failTimer);
            failTimer = null;
            tuneAll();
            tuneReceivers();
            readPath();
            if (connectTimer) clearTimeout(connectTimer);
            return;
        }
        if (stateName !== 'failed' || failTimer) return;
        if (role === 'caller' && !answerApplied) return;
        if (role !== 'caller' && state !== 'live') return;
        failTimer = setTimeout(function () {
            failTimer = null;
            if (!pc) return;
            if (pc.iceConnectionState === 'failed' || pc.connectionState === 'failed') {
                finish('The call could not connect. Use the hotel WiFi if you are on different networks.', true, 'ice-failed');
            }
        }, 20000);
    }

    function setupPc() {
        pc = new RTCPeerConnection(iceConfig());
        pc.onicecandidate = function (event) {
            if (!event.candidate) return;
            if (!callId) pendingLocal.push(event.candidate);
            else sendIce(event.candidate);
        };
        pc.ontrack = function (event) {
            var streamIn = event.streams && event.streams[0];
            if (!streamIn && event.track) {
                streamIn = new MediaStream();
                streamIn.addTrack(event.track);
            }
            if (streamIn) attachRemote(streamIn);
        };
        pc.oniceconnectionstatechange = function () {
            if (pc) noteLink(pc.iceConnectionState);
        };
        pc.onconnectionstatechange = function () {
            if (pc) noteLink(pc.connectionState);
        };
    }

    function addCallerTracks(stream) {
        localStream = stream;
        showLocalPreview(stream);
        var tracks = stream.getTracks();
        for (var i = 0; i < tracks.length; i++) pc.addTrack(tracks[i], stream);
    }

    function attachAnswerTracks(stream) {
        localStream = stream;
        showLocalPreview(stream);
        var tracks = stream.getTracks();
        var transceivers = pc.getTransceivers ? pc.getTransceivers() : [];
        var used = {};
        var chain = Promise.resolve();
        for (var i = 0; i < tracks.length; i++) {
            (function (track) {
                chain = chain.then(function () {
                    for (var t = 0; t < transceivers.length; t++) {
                        if (used[t]) continue;
                        var receiverTrack = transceivers[t].receiver && transceivers[t].receiver.track;
                        if (!receiverTrack || receiverTrack.kind !== track.kind) continue;
                        if (!transceivers[t].sender || !transceivers[t].sender.replaceTrack) continue;
                        used[t] = true;
                        try { transceivers[t].direction = 'sendrecv'; } catch (e) {}
                        return transceivers[t].sender.replaceTrack(track);
                    }
                    pc.addTrack(track, stream);
                });
            })(tracks[i]);
        }
        return chain;
    }

    function readPath() {
        if (!pc || !pc.getStats) return;
        pc.getStats().then(function (stats) {
            var pair = null;
            stats.forEach(function (report) {
                if (report.type === 'candidate-pair' && (report.nominated || report.state === 'succeeded')) {
                    if (!pair || report.nominated) pair = report;
                }
            });
            if (!pair) return;
            var local = stats.get(pair.localCandidateId);
            var remote = stats.get(pair.remoteCandidateId);
            var localType = local && local.candidateType;
            var remoteType = remote && remote.candidateType;
            var label = (localType === 'host' && remoteType === 'host') ? 'Hotel network' : 'Internet';
            var meta = document.getElementById('sky-call-meta');
            meta.setAttribute('data-path', label);
            var clock = meta.getAttribute('data-clock') || '';
            meta.textContent = clock ? label + ' · ' + clock : label;
            document.getElementById('sky-call-voice-state').textContent = label;
        }).catch(function () {});
    }

    function startClock() {
        if (clockTimer) clearInterval(clockTimer);
        startedAt = Date.now();
        var tick = function () {
            var seconds = Math.floor((Date.now() - startedAt) / 1000);
            var text = Math.floor(seconds / 60) + ':' + ('0' + (seconds % 60)).slice(-2);
            var meta = document.getElementById('sky-call-meta');
            meta.setAttribute('data-clock', text);
            var path = meta.getAttribute('data-path') || '';
            meta.textContent = path ? path + ' · ' + text : text;
        };
        tick();
        clockTimer = setInterval(tick, 1000);
    }

    function startPoll() {
        if (pollTimer) clearInterval(pollTimer);
        var pull = function () {
            if (!callId) return;
            var pollUrl = cfg.base + '/' + callId + '?after=' + lastSignal;
            if (role === 'caller' && !answerApplied) pollUrl += '&need_answer=1';
            getJson(pollUrl).then(function (data) {
                if (!data || data._status === 404) return;
                (data.signals || []).forEach(function (signal) {
                    if (signal.id > lastSignal) lastSignal = signal.id;
                    addRemoteIce(signal);
                });
                if (data.status === 'declined') return finish('Call declined.', false);
                if (data.status === 'missed') return finish('No answer.', false);
                if (data.status === 'ended') return finish('Call ended.', false);
                if (role === 'caller' && data.answer && !answerApplied && pc) {
                    answerApplied = true;
                    pc.setRemoteDescription({ type: 'answer', sdp: cleanSdp(data.answer) }).then(function () {
                        remoteReady = true;
                        flushRemote();
                        tuneAll();
                        state = 'live';
                        paintCall('Connected');
                        startClock();
                        readPath();
                    }).catch(function (error) {
                        answerApplied = false;
                        var detail = error && error.name ? error.name : 'answer';
                        finish('The call could not connect.', true, detail);
                    });
                }
            }).catch(function () {});
        };
        pull();
        pollTimer = setInterval(pull, 700);
    }

    function armConnectTimer() {
        if (connectTimer) clearTimeout(connectTimer);
        connectTimer = setTimeout(function () {
            if (state === 'live' && pc && pc.iceConnectionState !== 'connected' && pc.iceConnectionState !== 'completed') {
                paintCall('Still connecting');
            }
        }, 12000);
    }

    function finish(message, notify, detail) {
        var id = callId;
        var shouldNotify = notify && id && state !== 'idle';
        ringStop();
        state = 'idle';
        callId = 0;
        role = '';
        offerSdp = '';
        remoteReady = false;
        answerApplied = false;
        pendingLocal = [];
        pendingRemote = [];
        seenIce = {};
        lastSignal = 0;
        muted = false;
        cameraOff = false;
        if (pollTimer) clearInterval(pollTimer);
        if (clockTimer) clearInterval(clockTimer);
        if (connectTimer) clearTimeout(connectTimer);
        if (failTimer) clearTimeout(failTimer);
        pollTimer = null;
        clockTimer = null;
        connectTimer = null;
        failTimer = null;
        if (pc) {
            try { pc.close(); } catch (e) {}
        }
        pc = null;
        stopStream();
        remoteVideo.srcObject = null;
        localVideo.srcObject = null;
        remoteAudio.srcObject = null;
        stage.hidden = true;
        incoming.hidden = true;
        showRoot(false);
        document.title = pageTitle;
        document.getElementById('sky-call-mute').textContent = 'Mute';
        document.getElementById('sky-call-camera').textContent = 'Camera';
        var meta = document.getElementById('sky-call-meta');
        if (meta) {
            meta.removeAttribute('data-path');
            meta.removeAttribute('data-clock');
        }
        if (shouldNotify) postJson(cfg.base + '/' + id + '/end', { reason: detail || message || '' }, true).catch(function () {});
        setNote(message || '');
    }

    function begin(stream) {
        setupPc();
        addCallerTracks(stream);
        return pc.createOffer().then(function (offer) {
            var sdp = cleanSdp(offer.sdp);
            return pc.setLocalDescription({ type: 'offer', sdp: sdp }).then(function () {
                var local = pc.localDescription;
                if (local && local.sdp) local = { type: 'offer', sdp: cleanSdp(local.sdp) };
                return local || { type: 'offer', sdp: sdp };
            });
        });
    }

    function startCall(userId, kind) {
        if (state !== 'idle') return;
        setNote('');
        mode = kind === 'video' ? 'video' : 'audio';
        role = 'caller';
        state = 'outgoing';
        otherName = 'Calling';
        paintCall('Calling');
        openMedia(mode).then(function (stream) {
            return begin(stream);
        }).then(function (offer) {
            return postJson(cfg.store, {
                callee_user_id: userId,
                mode: mode,
                offer: offer.sdp
            });
        }).then(function (data) {
            if (!data || !data.ok) {
                var reason = 'The call could not start.';
                if (data && data.error === 'busy') reason = 'That person is already on a call.';
                if (data && data.error === 'offline') reason = 'That person is not in the app right now.';
                finish(reason, false);
                return;
            }
            callId = data.id;
            otherName = data.name || 'Staff';
            paintCall('Ringing');
            flushLocal();
            tuneAll();
            startPoll();
            armConnectTimer();
        }).catch(function (error) {
            finish(mediaError(error, false), false);
        });
    }

    function acceptCall() {
        if (state !== 'incoming' || !callId || !offerSdp) return;
        ringStop();
        var id = callId;
        state = 'outgoing';
        paintCall('Connecting');
        openMedia(mode).then(function (stream) {
            setupPc();
            return pc.setRemoteDescription({ type: 'offer', sdp: cleanSdp(offerSdp) }).then(function () {
                return attachAnswerTracks(stream);
            });
        }).then(function () {
            remoteReady = true;
            flushRemote();
            return pc.createAnswer();
        }).then(function (answer) {
            var sdp = cleanSdp(answer.sdp);
            return pc.setLocalDescription({ type: 'answer', sdp: sdp }).then(function () {
                var local = pc.localDescription;
                if (local && local.sdp) return { type: 'answer', sdp: cleanSdp(local.sdp) };
                return { type: 'answer', sdp: sdp };
            });
        }).then(function (answer) {
            return postJson(cfg.base + '/' + id + '/answer', { answer: answer.sdp });
        }).then(function (data) {
            if (!data || !data.ok) {
                finish('The call is no longer ringing.', false, 'answer-rejected');
                return;
            }
            state = 'live';
            flushLocal();
            tuneAll();
            paintCall('Connected');
            startClock();
            startPoll();
            armConnectTimer();
        }).catch(function (error) {
            var detail = error && error.name ? error.name : 'accept';
            finish(mediaError(error, true), true, detail);
        });
    }

    function heartbeat() {
        postJson(cfg.heartbeat, {}).then(function (data) {
            if (!data) return;
            if (data.incoming && state === 'idle') {
                state = 'incoming';
                role = 'callee';
                callId = data.incoming.id;
                mode = data.incoming.mode === 'video' ? 'video' : 'audio';
                otherName = data.incoming.name || 'Staff';
                offerSdp = data.incoming.offer || '';
                showIncoming(data.incoming);
                startPoll();
                return;
            }
            if (state === 'incoming' && (!data.incoming || data.incoming.id !== callId)) {
                finish('Call cancelled.', false);
            }
        }).catch(function () {});
    }

    function loadDirectory() {
        var list = document.getElementById('call-directory');
        if (!list) return;
        getJson(cfg.online).then(function (data) {
            var people = data && data.people ? data.people : [];
            list.innerHTML = '';
            if (!people.length) {
                var empty = document.createElement('li');
                empty.className = 'call-empty';
                empty.textContent = 'No one else has Skyline open right now.';
                list.appendChild(empty);
                return;
            }
            for (var i = 0; i < people.length; i++) {
                var person = people[i];
                var item = document.createElement('li');
                item.className = 'call-person';
                item.setAttribute('data-sky-call', 'audio');
                item.setAttribute('data-sky-user', String(person.id));
                var avatar = document.createElement('span');
                avatar.className = 'call-avatar';
                avatar.textContent = (person.name || 'S').charAt(0).toUpperCase();
                var copy = document.createElement('div');
                var name = document.createElement('strong');
                name.textContent = person.name || 'Staff';
                var roleName = document.createElement('em');
                roleName.textContent = person.role || 'Staff';
                var net = document.createElement('span');
                net.className = 'call-net';
                net.textContent = person.same_network ? 'Same network' : 'Over the internet';
                copy.appendChild(name);
                copy.appendChild(roleName);
                copy.appendChild(document.createElement('br'));
                copy.appendChild(net);
                var actions = document.createElement('div');
                actions.className = 'call-actions';
                var audioBtn = document.createElement('button');
                audioBtn.type = 'button';
                audioBtn.setAttribute('data-sky-call', 'audio');
                audioBtn.setAttribute('data-sky-user', String(person.id));
                audioBtn.textContent = 'Call';
                var videoBtn = document.createElement('button');
                videoBtn.type = 'button';
                videoBtn.setAttribute('data-sky-call', 'video');
                videoBtn.setAttribute('data-sky-user', String(person.id));
                videoBtn.textContent = 'Video';
                actions.appendChild(audioBtn);
                actions.appendChild(videoBtn);
                item.appendChild(avatar);
                item.appendChild(copy);
                item.appendChild(actions);
                list.appendChild(item);
            }
        }).catch(function () {});
    }

    document.addEventListener('click', function (event) {
        var node = event.target;
        while (node && node !== document) {
            if (node.getAttribute && node.getAttribute('data-sky-call')) break;
            node = node.parentNode;
        }
        if (!node || !node.getAttribute) return;
        event.preventDefault();
        startCall(parseInt(node.getAttribute('data-sky-user'), 10), node.getAttribute('data-sky-call'));
    });

    document.getElementById('sky-call-accept').addEventListener('click', acceptCall);
    document.getElementById('sky-call-decline').addEventListener('click', function () {
        var id = callId;
        ringStop();
        if (id) postJson(cfg.base + '/' + id + '/decline', {}).catch(function () {});
        finish('Call declined.', false);
    });
    document.getElementById('sky-call-hangup').addEventListener('click', function () {
        finish('Call ended.', true);
    });
    document.getElementById('sky-call-mute').addEventListener('click', function () {
        if (!localStream) return;
        muted = !muted;
        var tracks = localStream.getAudioTracks();
        for (var i = 0; i < tracks.length; i++) tracks[i].enabled = !muted;
        this.textContent = muted ? 'Unmute' : 'Mute';
    });
    document.getElementById('sky-call-camera').addEventListener('click', function () {
        if (!localStream) return;
        cameraOff = !cameraOff;
        var tracks = localStream.getVideoTracks();
        for (var i = 0; i < tracks.length; i++) tracks[i].enabled = !cameraOff;
        this.textContent = cameraOff ? 'Camera on' : 'Camera';
    });

    window.addEventListener('beforeunload', function () {
        if (state === 'idle' || !callId) return;
        postJson(cfg.base + '/' + callId + '/end', {}, true).catch(function () {});
    });

    document.addEventListener('pointerdown', function () {
        var Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        if (!audioCtx) audioCtx = new Ctx();
        if (audioCtx.state === 'suspended') audioCtx.resume();
    });

    heartbeat();
    heartTimer = setInterval(heartbeat, 3000);
    loadDirectory();
    setInterval(loadDirectory, 4000);
})();
