<style>
    .sky-board { margin-top: 4px; }
    .sky-board .table-responsive { overflow-x: auto; overflow-y: hidden; border-radius: 18px; background: #fff; box-shadow: 0 12px 32px rgba(12,24,48,.06); }
    .sky-board table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; background: #fff; }
    .sky-board table thead th,
    .sky-board table.dataTable thead th,
    .sky-board table.dataTable thead td {
        position: relative; z-index: 5; padding: 13px 14px; background-color: #0c1830 !important; color: #e1b368 !important;
        border: 0 !important; box-shadow: inset 0 -3px 0 #e1b368; font-size: 11px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase; white-space: nowrap;
    }
    .sky-board table thead th:first-child { border-top-left-radius: 18px; }
    .sky-board table thead th:last-child { border-top-right-radius: 18px; }
    .sky-board table tbody td { padding: 10px 14px; vertical-align: middle; border-top: 1px solid #eef1f6; color: #1c2434; background: #fff; }
    .sky-board table tbody tr:nth-child(odd) td,
    .sky-board table.dataTable tbody tr.odd td { background: #f3f6fb !important; }
    .sky-board table tbody tr:nth-child(even) td,
    .sky-board table.dataTable tbody tr.even td { background: #fff !important; }
    .sky-board table tbody tr:hover td { background: #e7eef8 !important; }
    .sky-board > .container-fluid .btn-info,
    .sky-board > .container-fluid .btn-primary {
        background: #0c1830 !important; background-color: #0c1830 !important; color: #e1b368 !important; border: 0; border-radius: 999px; font-weight: 700; min-height: 40px;
    }
    .sky-form .card { border: 0; border-radius: 18px; box-shadow: 0 12px 32px rgba(12,24,48,.06); overflow: hidden; }
    .sky-form .card-header { background: #0c1830; color: #e1b368; border: 0; box-shadow: inset 0 -3px 0 #e1b368; }
    .sky-form .card-header h4, .sky-form .card-header h5 { color: #e1b368; margin: 0; }
    .sky-form .form-control { border-radius: 12px; border-color: #d7deea; min-height: 42px; }
    .sky-form .btn-primary, .sky-form input[type="submit"], .sky-form button[type="submit"] {
        background: #e1b368 !important; background-color: #e1b368 !important; color: #0c1830 !important; border-color: #e1b368 !important; border-radius: 999px; font-weight: 800; min-height: 44px; padding: 8px 22px;
    }
    .sky-choice { border: 0; border-radius: 18px; overflow: hidden; }
    .sky-choice .modal-header { background: #0c1830; color: #e1b368; border: 0; box-shadow: inset 0 -3px 0 #e1b368; }
    .sky-choice .modal-title { color: #e1b368; font-weight: 800; }
    .sky-choice .close { color: #e1b368; opacity: 1; text-shadow: none; }
    .sky-choice .choice-row { display: flex; align-items: center; gap: 10px; margin: 0; padding: 10px 12px; border-radius: 12px; background: #f3f6fb; color: #0c1830; font-weight: 700; cursor: pointer; }
    .sky-choice .choice-row + .choice-row { margin-top: 8px; }
    .sky-choice .choice-row.is-on { background: #e1b368; }
    .sky-choice .modal-footer { border: 0; }
    .sky-choice .go { background: #e1b368 !important; background-color: #e1b368 !important; color: #0c1830 !important; border: 0; border-radius: 999px; font-weight: 800; min-height: 44px; padding: 0 18px; }
    .sky-choice .go:disabled { opacity: .45; }
    .sky-choice .warn { color: #8a3b2a; font-weight: 700; }
</style>
<div class="modal fade" id="sky-backup-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form class="modal-content sky-choice" method="post" action="{{ route('setting.backup.run') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Backup Database</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Choose the groups to include. Nothing is copied until you download.</p>
                <label class="choice-row"><input type="checkbox" class="sky-choice-all"> All of these</label>
                @foreach(\App\Support\DatabaseChoice::backupGroups() as $key => $group)
                    <label class="choice-row"><input type="checkbox" name="groups[]" value="{{ $key }}"> {{ $group[0] }}</label>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="go" disabled>Download backup</button>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="sky-empty-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form class="modal-content sky-choice" method="post" action="{{ route('setting.empty.run') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Empty Database</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="warn">This clears only the groups you tick. Staff logins, roles, warehouses, and settings stay. This cannot be undone.</p>
                <label class="choice-row"><input type="checkbox" class="sky-choice-all"> All of these</label>
                @foreach(\App\Support\DatabaseChoice::emptyGroups() as $key => $group)
                    <label class="choice-row"><input type="checkbox" name="groups[]" value="{{ $key }}"> {{ $group[0] }}</label>
                @endforeach
                <div class="form-group" style="margin-top:14px">
                    <label>Type EMPTY to confirm</label>
                    <input type="text" name="confirm" class="form-control sky-empty-confirm" autocomplete="off" placeholder="EMPTY">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="go" disabled>Clear the groups I chose</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function () {
        function paint(form) {
            form.querySelectorAll('input[name="groups[]"]').forEach(function (box) {
                var row = box.closest('.choice-row');
                if (row) row.classList.toggle('is-on', box.checked);
            });
            var groups = form.querySelectorAll('input[name="groups[]"]');
            var on = 0;
            groups.forEach(function (box) { if (box.checked) on++; });
            var all = form.querySelector('.sky-choice-all');
            if (all) {
                all.checked = groups.length > 0 && on === groups.length;
                var row = all.closest('.choice-row');
                if (row) row.classList.toggle('is-on', all.checked);
            }
            var go = form.querySelector('.go');
            var confirm = form.querySelector('.sky-empty-confirm');
            var ready = on > 0 && (!confirm || confirm.value === 'EMPTY');
            if (go) go.disabled = !ready;
        }
        document.querySelectorAll('#sky-backup-modal form, #sky-empty-modal form').forEach(function (form) {
            form.addEventListener('change', function (event) {
                if (event.target.classList.contains('sky-choice-all')) {
                    var mark = event.target.checked;
                    form.querySelectorAll('input[name="groups[]"]').forEach(function (box) { box.checked = mark; });
                }
                paint(form);
            });
            form.addEventListener('input', function () { paint(form); });
            paint(form);
        });
        function pin() {
            var bar = window.matchMedia('(max-width: 1199.98px)').matches ? 63 : 0;
            document.querySelectorAll('.sky-board table').forEach(function (table) {
                if (!table.tHead || !table.tHead.rows[0]) return;
                var cells = table.tHead.rows[0].cells;
                if (!cells.length) return;
                var rect = table.getBoundingClientRect();
                var headH = cells[0].offsetHeight || 0;
                var shift = rect.top < bar ? Math.min(bar - rect.top, Math.max(0, rect.height - headH)) : 0;
                var transform = shift ? 'translateY(' + shift + 'px)' : '';
                for (var i = 0; i < cells.length; i++) cells[i].style.transform = transform;
            });
        }
        var ticking = false;
        function requestPin() {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(function () { ticking = false; pin(); });
        }
        window.addEventListener('scroll', requestPin, { passive: true });
        window.addEventListener('resize', requestPin);
        pin();
    })();
</script>
