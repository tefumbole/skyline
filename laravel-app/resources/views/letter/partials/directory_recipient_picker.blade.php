{{-- Announcement-style To / CC picker for letters --}}
<style>
    .lt-pill {
        border:0; border-radius:999px; padding:6px 12px; font-size:12px; font-weight:600;
        background:#f1f5f9; color:#334155; cursor:pointer; margin:0 4px 6px 0;
    }
    .lt-pill.active { background:#0b3f90; color:#fff; }
    .lt-user-list { max-height:220px; overflow:auto; border:1px solid #e3e9f4; border-radius:10px; background:#fff; display:none; }
    .lt-user-list.open { display:block; }
    .lt-user-item { display:block; width:100%; text-align:left; padding:10px 12px; border:0; border-bottom:1px solid #f0f3f8; background:#fff; cursor:pointer; }
    .lt-user-item:hover, .lt-user-item.selected { background:#f0f6ff; }
    .lt-user-item .meta { color:#6b7280; font-size:12px; }
    .lt-chip {
        display:inline-flex; align-items:center; gap:6px; border:1px solid #0b3f90; color:#0b3f90;
        background:#eef4ff; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:600; margin:2px;
    }
    .lt-chip button { border:0; background:transparent; color:#0b3f90; font-weight:800; cursor:pointer; }
    .lt-field { width:100%; border:1px solid #d7deea; border-radius:8px; padding:9px 12px; font-size:14px; }
    .lt-btn-outline {
        border:1px solid #0b3f90; color:#0b3f90; background:#fff; border-radius:8px;
        padding:8px 12px; font-weight:600; font-size:13px; cursor:pointer;
    }
    .lt-plus {
        width:32px; height:32px; border-radius:8px; border:1px solid #0b3f90; background:#fff;
        color:#0b3f90; font-size:20px; font-weight:700; line-height:1; cursor:pointer;
    }
    .lt-phone-row { display:none; gap:8px; margin:0 0 8px; align-items:center; }
    .lt-phone-row.open { display:flex; }
</style>

<div class="letter-directory-picker letter-recipient-panel">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap:8px;">
        <div class="d-flex align-items-center" style="gap:8px;">
            <h5 class="mb-0" style="color:#0b3f90;font-weight:700;">Select Recipients *</h5>
            <button type="button" class="lt-plus" id="lt-rplus" title="Add a phone number">+</button>
        </div>
        <div>
            <small class="text-muted">Selected</small>
            <strong id="lt-count" style="color:#0b3f90;font-size:1.15rem;margin-left:6px;">0</strong>
        </div>
    </div>
    <p class="text-muted small mb-2">Search for a person, pick a WhatsApp group, or use + to add a MoMo number.</p>
    <div class="lt-phone-row lt-rphone">
        <input type="tel" class="lt-field lt-rphone-input" placeholder="Phone number" style="flex:1;min-width:160px;">
        <button type="button" class="lt-btn-outline lt-rphone-add">Add</button>
        <span class="lt-rphone-status text-muted small"></span>
    </div>
    <div class="mb-2">
        <button type="button" class="lt-pill active lt-rf" data-role="customers">Customers</button>
        <button type="button" class="lt-pill lt-rf" data-role="staff">System Users</button>
        <button type="button" class="lt-pill lt-rf" data-role="applicants">Applicants</button>
        <button type="button" class="lt-pill lt-rf" data-role="groups">WhatsApp group</button>
        <button type="button" class="lt-pill lt-rf" data-role="all">All</button>
    </div>
    <div class="d-flex mb-2 flex-wrap" style="gap:8px;">
        <input type="search" class="lt-field lt-rsearch" placeholder="Search name, email, phone, or group…" style="flex:1;min-width:180px;">
        <button type="button" class="lt-btn-outline lt-rselect-all" style="white-space:nowrap;">Select all</button>
        <button type="button" class="lt-btn-outline lt-rdeselect-all" style="white-space:nowrap;">Deselect all</button>
    </div>
    <div class="lt-user-list lt-rlist"></div>
    <div class="lt-rchips mt-2"></div>
    <div class="lt-rhiddens"></div>

    <hr>
    <div class="d-flex align-items-center mb-1" style="gap:8px;">
        <h6 class="mb-0" style="font-weight:700;">CC</h6>
        <button type="button" class="lt-plus" id="lt-cplus" title="Add a phone number to CC">+</button>
    </div>
    <p class="text-muted small">Copied recipients also receive the letter.</p>
    <div class="lt-phone-row lt-cphone">
        <input type="tel" class="lt-field lt-cphone-input" placeholder="Phone number" style="flex:1;min-width:160px;">
        <button type="button" class="lt-btn-outline lt-cphone-add">Add</button>
        <span class="lt-cphone-status text-muted small"></span>
    </div>
    <div class="mb-2">
        <button type="button" class="lt-pill active lt-cf" data-role="all">All</button>
        <button type="button" class="lt-pill lt-cf" data-role="staff">System Users</button>
        <button type="button" class="lt-pill lt-cf" data-role="customers">Customers</button>
        <button type="button" class="lt-pill lt-cf" data-role="applicants">Applicants</button>
        <button type="button" class="lt-pill lt-cf" data-role="groups">WhatsApp group</button>
    </div>
    <div class="d-flex mb-2 flex-wrap" style="gap:8px;">
        <input type="search" class="lt-field lt-csearch" placeholder="Search CC…" style="flex:1;min-width:180px;">
        <button type="button" class="lt-btn-outline lt-cselect-all" style="white-space:nowrap;">Select all</button>
        <button type="button" class="lt-btn-outline lt-cdeselect-all" style="white-space:nowrap;">Deselect all</button>
    </div>
    <div class="lt-user-list lt-clist" style="max-height:140px;"></div>
    <div class="lt-cchips mt-2"></div>
    <div class="lt-chiddens"></div>
</div>

<script>
window.LT_USERS = @json($directoryPeople ?? []);
window.LT_GROUPS = @json($directoryGroups ?? []);
window.LT_USERS_SEARCH = @json($directorySearchUrl ?? route('letter.people.search'));
window.LT_PHONE_URL = @json(route('letter.people.phone'));
window.LT_PRESELECT = @json([
    'recipients' => $cloneDirectoryToIds ?? [],
    'cc' => $cloneDirectoryCcIds ?? [],
]);
(function () {
    if (!document.querySelector('.letter-directory-picker')) return;

    var recipients = (window.LT_PRESELECT.recipients || []).slice();
    var ccs = (window.LT_PRESELECT.cc || []).slice();
    var rRole = 'customers', cRole = 'all';
    var searchTimers = {};

    function esc(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
        });
    }

    function mergeUsers(list) {
        var map = {};
        (window.LT_USERS || []).forEach(function (u) { map[u.id] = u; });
        (list || []).forEach(function (u) { map[u.id] = u; });
        window.LT_USERS = Object.keys(map).map(function (k) { return map[k]; });
    }

    function matchesRole(u, roleFilter) {
        var role = (u.role || '').toLowerCase();
        var source = (u.source || '').toLowerCase();
        if (roleFilter === 'groups') {
            return source === 'group' || role === 'whatsapp group' || String(u.id || '').indexOf('group:') === 0;
        }
        if (roleFilter === 'staff') {
            return source !== 'customer' && source !== 'applicant' && source !== 'group' && role !== 'customer' && role !== 'client' && role !== 'applicant';
        }
        if (roleFilter === 'customers') {
            return source === 'customer' || role === 'customer' || role === 'client';
        }
        if (roleFilter === 'applicants') {
            return source === 'applicant' || role === 'applicant';
        }
        return source !== 'group' && String(u.id || '').indexOf('group:') !== 0;
    }

    function groupRows(query) {
        var q = (query || '').toLowerCase();
        return (window.LT_GROUPS || []).filter(function (g) {
            if (!g || !g.jid) return false;
            if (!q) return true;
            return String(g.name || '').toLowerCase().indexOf(q) !== -1
                || String(g.jid || '').toLowerCase().indexOf(q) !== -1;
        }).map(function (g) {
            return { id: 'group:' + g.jid, name: g.name || 'WhatsApp group', email: '', phone: '', source: 'Group', role: 'WhatsApp group' };
        });
    }

    function searchUsers(query, roleFilter, done) {
        var q = (query || '').trim();
        if (roleFilter === 'groups') {
            var groups = groupRows(q);
            mergeUsers(groups);
            done(groups);
            return;
        }
        if (!q) {
            done([]);
            return;
        }
        if (q.length < 2 || !window.LT_USERS_SEARCH) {
            done((window.LT_USERS || []).filter(function (u) {
                if (!matchesRole(u, roleFilter)) return false;
                var blob = ((u.name || '') + ' ' + (u.email || '') + ' ' + (u.phone || '')).toLowerCase();
                return blob.indexOf(q.toLowerCase()) !== -1;
            }));
            return;
        }
        var filter = roleFilter === 'staff' ? 'staff'
            : (roleFilter === 'customers' ? 'customers'
            : (roleFilter === 'applicants' ? 'applicants' : 'all'));
        fetch(window.LT_USERS_SEARCH + '?q=' + encodeURIComponent(q) + '&filter=' + encodeURIComponent(filter), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (rows) {
            mergeUsers(rows);
            done((rows || []).filter(function (u) { return matchesRole(u, roleFilter); }));
        }).catch(function () {
            done([]);
        });
    }

    function renderList(el, users, selected, onToggle, emptyText) {
        if (!users.length && !emptyText) {
            el.classList.remove('open');
            el.innerHTML = '';
            return;
        }
        el.classList.add('open');
        el.innerHTML = users.map(function (u) {
            var sel = selected.indexOf(u.id) !== -1 ? ' selected' : '';
            return '<button type="button" class="lt-user-item'+sel+'" data-id="'+esc(u.id)+'">'
                + '<div class="font-weight-bold">'+esc(u.name||'Untitled')+' <span class="badge badge-light">'+esc(u.source||u.role||'')+'</span></div>'
                + '<div class="meta">'+esc(u.email||'')+(u.phone ? ' · '+esc(u.phone) : '')+'</div>'
                + '</button>';
        }).join('') || '<div class="p-3 text-muted small text-center">'+esc(emptyText || 'No people found.')+'</div>';
        el.querySelectorAll('.lt-user-item').forEach(function (item) {
            item.addEventListener('click', function () { onToggle(item.getAttribute('data-id')); });
        });
    }

    function renderChips(el, selected, onRemove, prefix) {
        var map = {};
        (window.LT_USERS || []).forEach(function (u) { map[u.id] = u; });
        el.innerHTML = selected.map(function (id) {
            var u = map[id] || { name: id };
            return '<span class="lt-chip" data-id="'+esc(id)+'">'+esc((prefix?prefix+' ':'')+(u.name||id))
                + ' <button type="button">×</button></span>';
        }).join('');
        el.querySelectorAll('.lt-chip button').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                onRemove(btn.parentNode.getAttribute('data-id'));
            });
        });
    }

    function syncHiddens(el, selected, name) {
        el.innerHTML = selected.map(function (id) {
            return '<input type="hidden" name="'+name+'[]" value="'+esc(id)+'">';
        }).join('');
        var countEl = document.getElementById('lt-count');
        if (countEl) countEl.textContent = String(recipients.length + ccs.length);
    }

    function refreshRecipients() {
        var q = document.querySelector('.lt-rsearch').value;
        var emptyText = (q || '').trim() || rRole === 'groups' ? 'No matches.' : '';
        searchUsers(q, rRole, function (users) {
            renderList(document.querySelector('.lt-rlist'), users, recipients, function (id) {
                var i = recipients.indexOf(id);
                if (i === -1) recipients.push(id); else recipients.splice(i, 1);
                refreshRecipients();
            }, rRole === 'groups' || (q || '').trim() ? emptyText : '');
            renderChips(document.querySelector('.lt-rchips'), recipients, function (id) {
                recipients = recipients.filter(function (x) { return x !== id; });
                refreshRecipients();
            });
            syncHiddens(document.querySelector('.lt-rhiddens'), recipients, 'recipient_ids');
        });
    }

    function refreshCc() {
        var q = document.querySelector('.lt-csearch').value;
        searchUsers(q, cRole, function (users) {
            renderList(document.querySelector('.lt-clist'), users, ccs, function (id) {
                var i = ccs.indexOf(id);
                if (i === -1) ccs.push(id); else ccs.splice(i, 1);
                refreshCc();
            }, cRole === 'groups' || (q || '').trim() ? 'No matches.' : '');
            renderChips(document.querySelector('.lt-cchips'), ccs, function (id) {
                ccs = ccs.filter(function (x) { return x !== id; });
                refreshCc();
            }, 'CC:');
            syncHiddens(document.querySelector('.lt-chiddens'), ccs, 'cc_ids');
        });
    }

    function addByPhone(input, statusEl, bucket, refresh) {
        var phone = (input.value || '').trim();
        if (phone.replace(/\D/g, '').length < 8) {
            statusEl.textContent = 'Enter a phone number.';
            return;
        }
        statusEl.textContent = 'Looking up the MoMo name…';
        var token = document.querySelector('meta[name="csrf-token"]');
        fetch(window.LT_PHONE_URL, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({ phone: phone })
        }).then(function (r) {
            return r.json().then(function (data) { return { ok: r.ok, data: data }; });
        }).then(function (res) {
            if (!res.ok || !res.data || !res.data.person) {
                statusEl.textContent = (res.data && res.data.message) ? res.data.message : 'No MoMo name for this number.';
                return;
            }
            mergeUsers([res.data.person]);
            if (bucket.indexOf(res.data.person.id) === -1) bucket.push(res.data.person.id);
            input.value = '';
            statusEl.textContent = 'Added ' + (res.data.person.name || '');
            refresh();
        }).catch(function () {
            statusEl.textContent = 'Could not look up that number.';
        });
    }

    document.querySelector('.lt-rsearch').addEventListener('input', function () {
        clearTimeout(searchTimers.r);
        searchTimers.r = setTimeout(refreshRecipients, 250);
    });
    document.querySelector('.lt-csearch').addEventListener('input', function () {
        clearTimeout(searchTimers.c);
        searchTimers.c = setTimeout(refreshCc, 250);
    });
    document.querySelectorAll('.lt-rf').forEach(function (btn) {
        btn.addEventListener('click', function () {
            rRole = btn.getAttribute('data-role');
            document.querySelectorAll('.lt-rf').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            refreshRecipients();
        });
    });
    document.querySelectorAll('.lt-cf').forEach(function (btn) {
        btn.addEventListener('click', function () {
            cRole = btn.getAttribute('data-role');
            document.querySelectorAll('.lt-cf').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            refreshCc();
        });
    });
    document.querySelector('.lt-rselect-all').addEventListener('click', function () {
        var q = (document.querySelector('.lt-rsearch').value || '').trim();
        if (!q && rRole !== 'groups') return;
        searchUsers(q, rRole, function (users) {
            users.forEach(function (u) {
                if (recipients.indexOf(u.id) === -1) recipients.push(u.id);
            });
            refreshRecipients();
        });
    });
    document.querySelector('.lt-rdeselect-all').addEventListener('click', function () {
        recipients = [];
        refreshRecipients();
    });
    document.querySelector('.lt-cselect-all').addEventListener('click', function () {
        var q = (document.querySelector('.lt-csearch').value || '').trim();
        if (!q && cRole !== 'groups') return;
        searchUsers(q, cRole, function (users) {
            users.forEach(function (u) {
                if (ccs.indexOf(u.id) === -1) ccs.push(u.id);
            });
            refreshCc();
        });
    });
    document.querySelector('.lt-cdeselect-all').addEventListener('click', function () {
        ccs = [];
        refreshCc();
    });
    document.getElementById('lt-rplus').addEventListener('click', function () {
        document.querySelector('.lt-rphone').classList.toggle('open');
        var input = document.querySelector('.lt-rphone-input');
        if (input) input.focus();
    });
    document.getElementById('lt-cplus').addEventListener('click', function () {
        document.querySelector('.lt-cphone').classList.toggle('open');
        var input = document.querySelector('.lt-cphone-input');
        if (input) input.focus();
    });
    document.querySelector('.lt-rphone-add').addEventListener('click', function () {
        addByPhone(document.querySelector('.lt-rphone-input'), document.querySelector('.lt-rphone-status'), recipients, refreshRecipients);
    });
    document.querySelector('.lt-cphone-add').addEventListener('click', function () {
        addByPhone(document.querySelector('.lt-cphone-input'), document.querySelector('.lt-cphone-status'), ccs, refreshCc);
    });

    var form = document.getElementById('product-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            var peopleType = (document.querySelector('select[name="people_type"]') || {}).value;
            if (peopleType === 'directory' && !recipients.length) {
                e.preventDefault();
                alert('Select at least one recipient (To).');
            }
        });
    }

    refreshRecipients();
    refreshCc();
})();
</script>
