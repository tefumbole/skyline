@extends('layout.main')
@section('content')
@include('payout.partials.style')
<section class="container-fluid pay-app">
    <script>
        $("ul#payments").addClass("show").siblings("a").attr("aria-expanded", "true");
        $("ul#payments #payout-request-menu").addClass("active");
    </script>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif

    <style>
        .pay-choice { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 16px; border: 1px solid #ead9b4; border-radius: 16px; background: #fffaf3; margin-bottom: 12px; }
        .pay-choice h3 { margin: 0 0 4px; font-size: 16px; color: #1a1208; }
        .pay-choice .pay-help { margin: 0; }
    </style>
    <div class="pay-card">
        <div class="pay-card-head"><h2>Request for Payment</h2></div>
        <div class="pay-card-body">
            <div class="pay-choice">
                <div>
                    <h3>Deposit Request</h3>
                    <p class="pay-help">Ask an MTN or Orange number to pay into the wallet. The name comes from Campay.</p>
                </div>
                <button class="pay-plus" type="button" id="openDepositRequest" title="Request a deposit">+</button>
            </div>
            <form method="POST" action="{{ route('payout.request.deposit') }}" id="depositRequestForm" style="display:none;margin-bottom:18px">
                @csrf
                <div class="form-group" style="max-width:420px">
                    <label>MTN or Orange number</label>
                    <input type="tel" name="phone" id="depositRequestPhone" class="form-control" placeholder="6xxxxxxxx" autocomplete="off" required>
                    <div id="depositRequestName" class="pay-help" style="margin-top:6px"></div>
                </div>
                <div class="form-group" style="max-width:220px">
                    <label>Amount (XAF)</label>
                    <input type="number" name="amount" class="form-control" min="100" max="1000000" step="1" required>
                </div>
                <div class="form-group" style="max-width:520px">
                    <label>Note</label>
                    <input type="text" name="note" class="form-control" maxlength="180" placeholder="What this deposit is for">
                </div>
                <button class="pay-go" type="submit">Send deposit request</button>
            </form>
            @if(isset($depositRequests) && count($depositRequests))
                <div class="table-responsive" style="margin-bottom:18px">
                    <table class="pay-table">
                        <thead><tr><th>When</th><th>Name</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($depositRequests as $row)
                                @php
                                    $tone = $row->status === 'paid' ? 'ok' : ($row->status === 'failed' ? 'no' : 'wait');
                                    $statusLabel = $row->status === 'paid' ? 'Paid' : ($row->status === 'failed' ? 'Not paid' : 'Pending');
                                @endphp
                                <tr>
                                    <td>{{ $row->created_at ? $row->created_at->format('M j H:i') : '' }}</td>
                                    <td>{{ $row->person_name }}</td>
                                    <td>{{ number_format($row->amount, 0, '.', ' ') }} XAF</td>
                                    <td><span class="pay-pill pay-pill-{{ $tone }}">{{ $statusLabel }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="pay-choice">
                <div>
                    <h3>Payout Request</h3>
                    <p class="pay-help">Ask people to send the MTN or Orange number you should pay. Campay supplies the name.</p>
                </div>
                <button class="pay-plus" type="button" id="openPayoutRequest" title="Request a payout">+</button>
            </div>
            <div id="payoutRequestBox" style="display:none">
            <form method="POST" action="{{ route('payout.request.invite') }}" id="inviteForm">
                @csrf
                <div class="pay-search">
                    <label>People</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="search" id="inviteSearch" class="form-control" placeholder="Search by name or phone number" autocomplete="off">
                        <button class="pay-plus" type="button" id="inviteAddPhone" title="Add a number">+</button>
                    </div>
                    <div id="inviteHits" class="list-group" style="position:absolute;z-index:5;width:100%;max-height:240px;overflow:auto"></div>
                    <div id="invitePhoneRow" style="display:none;margin-top:8px">
                        <input type="tel" id="invitePhone" class="form-control" placeholder="Phone number, 6xxxxxxxx" autocomplete="off">
                    </div>
                </div>
                <div class="table-responsive" style="margin-top:14px">
                    <table class="pay-table">
                        <thead><tr><th>Name</th><th>Number</th><th></th></tr></thead>
                        <tbody id="inviteBody">
                            <tr id="inviteEmpty"><td colspan="3" class="pay-muted">No one selected yet.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="form-group" style="max-width:520px;margin-top:14px">
                    <label>Reason</label>
                    <input type="text" name="reason" class="form-control" maxlength="180" placeholder="Why this payment is being requested">
                </div>
                <button class="pay-go" type="submit">Send request</button>
            </form>
            <div class="pay-linkbox" style="margin-top:18px">
                <input type="text" id="requestUrl" class="form-control" readonly value="{{ $url }}">
                <button class="pay-go" type="button" id="copyLink">Copy link</button>
            </div>
            </div>
        </div>
    </div>

    @if($review)
        <div class="pay-card">
            <div class="pay-card-head">
                <h2>{{ $review->requester_name }} @if($review->note)<span class="pay-muted">· {{ $review->note }}</span>@endif</h2>
                <span>
                    @if($review->status !== 'pending' && $reviewLines->whereIn('status', ['pending', 'failed'])->count() > 0)
                        <form method="POST" action="{{ route('payout.mass', $review->id) }}" style="display:inline">
                            @csrf
                            <button class="pay-open" type="submit" style="border:0;cursor:pointer" onclick="return confirm('Pay everyone still waiting? People already paid are left out.')">Pay</button>
                        </form>
                    @endif
                    <a href="{{ route('payout.request') }}" style="margin-left:12px">Close</a>
                </span>
            </div>
            <div class="pay-card-body">
                @if($review->status === 'pending')
                    <p class="pay-help">Change an amount, or select names and delete them. Approve and Pay sends every person still waiting. One person is paid directly. Several people are sent together. A number already paid is not sent again.</p>
                    <form method="POST" action="{{ route('payout.request.revise') }}" id="reviewForm">
                        @csrf
                        <input type="hidden" name="id" value="{{ $review->id }}">
                        <div class="table-responsive">
                            <table class="pay-table">
                                <thead>
                                    <tr>
                                        <th style="width:36px"><input type="checkbox" id="reviewAll" title="Select all"></th>
                                        <th>Name</th>
                                        <th>Number</th>
                                        <th>Amount (XAF)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reviewLines as $line)
                                        @php
                                            $tone = $line->status === 'paid' ? 'ok' : ($line->status === 'pending' ? 'wait' : 'no');
                                            $statusLabel = $line->status === 'paid' ? 'Paid' : ($line->status === 'pending' ? 'Waiting' : ($line->status === 'failed' ? 'Not paid' : 'Rejected'));
                                        @endphp
                                        <tr>
                                            <td>
                                                @if(in_array($line->status, ['pending', 'failed'], true))
                                                    <input type="checkbox" class="review-line" name="remove_ids[]" value="{{ $line->id }}">
                                                @endif
                                            </td>
                                            <td>{{ $line->person_name }}</td>
                                            <td>{{ $line->phone }}</td>
                                            <td>
                                                @if($line->status === 'pending')
                                                    <input type="number" class="form-control form-control-sm" name="amounts[{{ $line->id }}]" min="100" max="1000000" step="1" value="{{ $line->amount }}" style="max-width:140px">
                                                @else
                                                    {{ number_format($line->amount, 0, '.', ' ') }}
                                                @endif
                                            </td>
                                            <td>
                                                <span class="pay-pill pay-pill-{{ $tone }}">{{ $statusLabel }}</span>
                                                @if($line->error)<div class="pay-note">{{ $line->error }}</div>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="rejectBox" style="display:none;margin:12px 0 0;max-width:520px">
                            <label for="rejectReason">Reason for rejection</label>
                            <textarea id="rejectReason" name="reason" maxlength="500" rows="3" class="form-control" placeholder="Why this request is rejected" style="margin-top:4px"></textarea>
                        </div>
                        <div class="pay-actions">
                            <button class="pay-go" type="submit" name="action" value="delete" formnovalidate style="background:#fdecec;color:#9b1c1c" id="reviewDelete">Delete</button>
                            <button class="pay-go" type="submit" name="action" value="pay">Approve and Pay</button>
                            <button class="pay-go" type="button" id="rejectOpen" style="background:#fff;color:#9b1c1c;border:1px solid #f3c7c7">Reject</button>
                            <button class="pay-go" type="submit" name="action" value="reject" id="rejectSend" formnovalidate style="display:none;background:#9b1c1c;color:#fff;border:0">Reject and notify</button>
                        </div>
                    </form>
                    <script>
                    (function () {
                        var form = document.getElementById('reviewForm');
                        var all = document.getElementById('reviewAll');
                        var del = document.getElementById('reviewDelete');
                        if (!form || !del) return;
                        function boxes() { return form.querySelectorAll('.review-line'); }
                        if (all) {
                            all.addEventListener('change', function () {
                                boxes().forEach(function (box) { box.checked = all.checked; });
                            });
                        }
                        del.addEventListener('click', function (event) {
                            var count = 0;
                            boxes().forEach(function (box) { if (box.checked) count++; });
                            if (!count) {
                                event.preventDefault();
                                return;
                            }
                            if (!confirm('Delete ' + count + (count === 1 ? ' person?' : ' people?'))) {
                                event.preventDefault();
                            }
                        });
                        var rejectOpen = document.getElementById('rejectOpen');
                        var rejectSend = document.getElementById('rejectSend');
                        var rejectBox = document.getElementById('rejectBox');
                        var rejectReason = document.getElementById('rejectReason');
                        if (rejectOpen && rejectSend && rejectBox && rejectReason) {
                            rejectOpen.addEventListener('click', function () {
                                rejectBox.style.display = 'block';
                                rejectOpen.style.display = 'none';
                                rejectSend.style.display = '';
                                rejectReason.focus();
                            });
                            rejectSend.addEventListener('click', function (event) {
                                if (rejectReason.value.trim() === '') {
                                    event.preventDefault();
                                    rejectReason.focus();
                                }
                            });
                        }
                    })();
                    </script>
                @else
                    @if($reviewLines->where('status', 'failed')->count() > 0)
                        <p class="pay-help">Select the payments that failed and delete them. A retry will not send a deleted payment.</p>
                    @endif
                    <form method="POST" action="{{ route('payout.drop') }}" id="failedForm">
                        @csrf
                        <div class="table-responsive">
                            <table class="pay-table">
                                <thead>
                                    <tr>
                                        <th style="width:36px"><input type="checkbox" id="failedAll" title="Select all failed"></th>
                                        <th>Name</th>
                                        <th>Number</th>
                                        <th>Amount (XAF)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reviewLines as $line)
                                        @php
                                            $tone = $line->status === 'paid' ? 'ok' : ($line->status === 'pending' ? 'wait' : 'no');
                                            $statusLabel = $line->status === 'paid' ? 'Paid' : ($line->status === 'pending' ? 'Waiting' : ($line->status === 'failed' ? 'Not paid' : 'Rejected'));
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($line->status === 'failed')
                                                    <input type="checkbox" class="failed-line" name="ids[]" value="{{ $line->id }}">
                                                @endif
                                            </td>
                                            <td>{{ $line->person_name }}</td>
                                            <td>{{ $line->phone }}</td>
                                            <td>{{ number_format($line->amount, 0, '.', ' ') }}</td>
                                            <td>
                                                <span class="pay-pill pay-pill-{{ $tone }}">{{ $statusLabel }}</span>
                                                @if($line->error)<div class="pay-note">{{ $line->error }}</div>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($reviewLines->where('status', 'failed')->count() > 0)
                            <div class="pay-actions">
                                <button class="pay-go" type="submit" style="background:#fdecec;color:#9b1c1c" id="failedDelete">Delete</button>
                            </div>
                        @endif
                    </form>
                    <script>
                    (function () {
                        var form = document.getElementById('failedForm');
                        var all = document.getElementById('failedAll');
                        var del = document.getElementById('failedDelete');
                        if (!form || !del) return;
                        function boxes() { return form.querySelectorAll('.failed-line'); }
                        if (all) {
                            all.addEventListener('change', function () {
                                boxes().forEach(function (box) { box.checked = all.checked; });
                            });
                        }
                        del.addEventListener('click', function (event) {
                            var count = 0;
                            boxes().forEach(function (box) { if (box.checked) count++; });
                            if (!count) {
                                event.preventDefault();
                                return;
                            }
                            if (!confirm('Delete ' + count + (count === 1 ? ' failed payment? A retry will not send it.' : ' failed payments? A retry will not send them.'))) {
                                event.preventDefault();
                            }
                        });
                    })();
                    </script>
                @endif
            </div>
        </div>
    @endif

    <div class="pay-card">
        <div class="pay-card-head"><h2>Requested payments</h2></div>
        <div class="table-responsive">
            <table class="pay-table">
                <thead>
                    <tr><th>When</th><th>People</th><th>Reason</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @php
                        $rows = collect();
                        foreach ($invites as $row) {
                            $rows->push(['at' => $row->created_at, 'kind' => 'invite', 'row' => $row]);
                        }
                        foreach ($submitted as $row) {
                            $rows->push(['at' => $row->created_at, 'kind' => 'submitted', 'row' => $row]);
                        }
                        $rows = $rows->sortByDesc(function ($item) {
                            return $item['at'] ? $item['at']->timestamp : 0;
                        })->values();
                    @endphp
                    @forelse($rows as $item)
                        @php $row = $item['row']; @endphp
                        <tr>
                            <td>
                                {{ $row->created_at ? $row->created_at->format('M j, Y H:i') : '' }}
                                @if($item['kind'] === 'invite' && $row->sent_at && $row->created_at && $row->sent_at->gt($row->created_at->copy()->addMinute()))
                                    <div class="pay-muted">Resent {{ $row->sent_at->format('M j, H:i') }}</div>
                                @endif
                            </td>
                            <td>
                                @if($item['kind'] === 'invite')
                                    @foreach((array) $row->people as $person)
                                        <div>{{ $person['name'] ?? '' }} <span class="pay-muted">{{ $person['phone'] ?? '' }}</span></div>
                                    @endforeach
                                @else
                                    @foreach($row->lines as $line)
                                        <div>{{ $line->person_name }} <span class="pay-muted">{{ number_format($line->amount, 0, '.', ' ') }} XAF</span></div>
                                    @endforeach
                                    @if($row->requester_name)
                                        <div class="pay-muted">From {{ $row->requester_name }}</div>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $item['kind'] === 'invite' ? $row->reason : $row->note }}</td>
                            <td>
                                @php
                                    $linePaid = $item['kind'] === 'submitted' ? $row->lines->where('status', 'paid')->count() : 0;
                                    $lineFailed = $item['kind'] === 'submitted' ? $row->lines->where('status', 'failed')->count() : 0;
                                    $linePending = $item['kind'] === 'submitted' ? $row->lines->where('status', 'pending')->count() : 0;
                                @endphp
                                @if($item['kind'] === 'invite')
                                    <span class="pay-pill pay-pill-wait">Asked</span>
                                @elseif($row->status === 'rejected')
                                    <span class="pay-pill pay-pill-no">Rejected</span>
                                    @php $why = trim((string) optional($row->lines->first())->error); @endphp
                                    @if($why !== '' && $why !== 'Rejected')
                                        <div class="pay-muted">{{ $why }}</div>
                                    @endif
                                @elseif($linePending > 0)
                                    <span class="pay-pill pay-pill-wait">Submitted</span>
                                @elseif($linePaid > 0 && $lineFailed === 0)
                                    <span class="pay-pill pay-pill-ok">Paid</span>
                                @elseif($lineFailed > 0 && $linePaid === 0)
                                    <span class="pay-pill pay-pill-no">Not paid</span>
                                @elseif($linePaid > 0)
                                    <span class="pay-pill pay-pill-wait">Partly paid</span>
                                @elseif($row->status === 'done')
                                    <span class="pay-pill pay-pill-ok">Paid</span>
                                @else
                                    <span class="pay-pill pay-pill-wait">Submitted</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                                @if($item['kind'] === 'submitted' && $row->status === 'pending')
                                    <form method="POST" action="{{ route('payout.request.revise') }}">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $row->id }}">
                                        <button class="pay-open" type="submit" name="action" value="approve" style="border:0;cursor:pointer" onclick="return confirm('Approve and pay everyone still waiting on this request?')">Approve and Pay</button>
                                    </form>
                                    <a class="pay-open" href="{{ route('payout.request', ['review' => $row->id]) }}" style="background:#e7eef8;color:#0b3f90 !important">Edit</a>
                                    <form method="POST" action="{{ route('payout.pending.delete') }}">
                                        @csrf
                                        <input type="hidden" name="ids[]" value="{{ $row->id }}">
                                        <button class="pay-open" type="submit" style="border:0;cursor:pointer;background:#fdecec;color:#9b1c1c" onclick="return confirm('Cancel this payment request?')">Cancel</button>
                                    </form>
                                @elseif($item['kind'] === 'submitted' && ($linePending + $lineFailed) > 0)
                                    <form method="POST" action="{{ route('payout.mass', $row->id) }}">
                                        @csrf
                                        <button class="pay-open" type="submit" style="border:0;cursor:pointer" onclick="return confirm('Pay everyone still waiting? People already paid are left out.')">Pay</button>
                                    </form>
                                    <a class="pay-open" href="{{ route('payout.request', ['review' => $row->id]) }}" style="background:#fdecec;color:#9b1c1c !important">Delete failed</a>
                                @endif
                                <form method="POST" action="{{ route('payout.request.resend') }}">
                                    @csrf
                                    <input type="hidden" name="kind" value="{{ $item['kind'] }}">
                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                    <button class="pay-open" type="submit" style="border:0;cursor:pointer">Resend</button>
                                </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="pay-muted">No payment requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
<script>
(function () {
    var depositOpen = document.getElementById('openDepositRequest');
    var depositForm = document.getElementById('depositRequestForm');
    var payoutOpen = document.getElementById('openPayoutRequest');
    var payoutBox = document.getElementById('payoutRequestBox');
    if (depositOpen && depositForm) {
        depositOpen.addEventListener('click', function () {
            depositForm.style.display = depositForm.style.display === 'none' ? 'block' : 'none';
            if (depositForm.style.display !== 'none') {
                document.getElementById('depositRequestPhone').focus();
            }
        });
    }
    if (payoutOpen && payoutBox) {
        payoutOpen.addEventListener('click', function () {
            payoutBox.style.display = payoutBox.style.display === 'none' ? 'block' : 'none';
        });
    }
    var depositPhone = document.getElementById('depositRequestPhone');
    var depositName = document.getElementById('depositRequestName');
    var lookupUrl = @json(route('payout.lookup'));
    if (depositPhone && depositName) {
        var depositWait = null;
        depositPhone.addEventListener('input', function () {
            clearTimeout(depositWait);
            var raw = depositPhone.value.trim();
            if (raw.replace(/\D/g, '').length < 9) {
                depositName.textContent = '';
                return;
            }
            depositName.textContent = 'Looking up…';
            depositWait = setTimeout(function () {
                fetch(lookupUrl + '?phone=' + encodeURIComponent(raw), {headers: {Accept: 'application/json'}})
                    .then(function (response) { return response.json(); })
                    .then(function (info) {
                        if (!info || !info.ok || !info.name) {
                            depositName.textContent = (info && info.error) ? info.error : 'Campay has no MTN or Orange name for this number.';
                            return;
                        }
                        depositName.textContent = info.name + (info.operator ? ' · ' + info.operator : '');
                    });
            }, 350);
        });
        depositForm.addEventListener('submit', function (event) {
            if (depositName.textContent.indexOf('·') === -1 && depositName.textContent.indexOf('Looking') !== -1) {
                event.preventDefault();
            }
        });
    }
})();
(function () {
    var form = document.getElementById('inviteForm');
    var body = document.getElementById('inviteBody');
    var empty = document.getElementById('inviteEmpty');
    var hits = document.getElementById('inviteHits');
    var search = document.getElementById('inviteSearch');
    var phoneRow = document.getElementById('invitePhoneRow');
    var phoneInput = document.getElementById('invitePhone');
    var searchUrl = @json(route('payout.search'));
    var lookupUrl = @json(route('payout.lookup'));
    var added = {};
    function refresh() {
        empty.style.display = body.querySelectorAll('tr[data-key]').length ? 'none' : '';
    }
    function addPerson(key, name, phone, fields) {
        if (added[key]) return;
        added[key] = true;
        var row = document.createElement('tr');
        row.setAttribute('data-key', key);
        var nameCell = document.createElement('td');
        nameCell.textContent = name;
        var phoneCell = document.createElement('td');
        phoneCell.textContent = phone;
        fields.forEach(function (field) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = field.name;
            input.value = field.value;
            nameCell.appendChild(input);
        });
        var removeCell = document.createElement('td');
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-link text-danger';
        remove.textContent = 'Remove';
        remove.addEventListener('click', function () {
            delete added[key];
            row.remove();
            refresh();
        });
        removeCell.appendChild(remove);
        row.appendChild(nameCell);
        row.appendChild(phoneCell);
        row.appendChild(removeCell);
        body.appendChild(row);
        refresh();
    }
    var timer = null;
    search.addEventListener('input', function () {
        clearTimeout(timer);
        var q = search.value.trim();
        if (q.length < 1) {
            hits.innerHTML = '';
            return;
        }
        timer = setTimeout(function () {
            fetch(searchUrl + '?q=' + encodeURIComponent(q), {headers: {Accept: 'application/json'}})
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    hits.innerHTML = '';
                    (data.people || []).forEach(function (person) {
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'list-group-item list-group-item-action';
                        button.textContent = person.name + ' (' + person.phone + ')';
                        button.addEventListener('click', function () {
                            var idName = person.kind === 'user' ? 'user_id[]' : 'customer_id[]';
                            fetch(lookupUrl + '?phone=' + encodeURIComponent(person.phone), {headers: {Accept: 'application/json'}})
                                .then(function (response) { return response.json(); })
                                .then(function (info) {
                                    hits.innerHTML = '';
                                    if (!info || !info.ok || !info.name) {
                                        var none = document.createElement('div');
                                        none.className = 'list-group-item text-muted';
                                        none.textContent = (info && info.error) ? info.error : 'Campay has no MTN or Orange name for this number';
                                        hits.appendChild(none);
                                        return;
                                    }
                                    addPerson(person.kind + ':' + person.id, info.name, info.phone, [{name: idName, value: person.id}]);
                                    search.value = '';
                                });
                        });
                        hits.appendChild(button);
                    });
                    if (!hits.children.length) {
                        var digits = q.replace(/\D/g, '');
                        if (digits.length >= 8) {
                            fetch(lookupUrl + '?phone=' + encodeURIComponent(q), {headers: {Accept: 'application/json'}})
                                .then(function (response) { return response.json(); })
                                .then(function (info) {
                                    hits.innerHTML = '';
                                    if (!info || !info.ok || !info.name) {
                                        var none = document.createElement('div');
                                        none.className = 'list-group-item text-muted';
                                        none.textContent = 'No matching name or number';
                                        hits.appendChild(none);
                                        return;
                                    }
                                    var button = document.createElement('button');
                                    button.type = 'button';
                                    button.className = 'list-group-item list-group-item-action';
                                    button.textContent = info.name + (info.operator ? ' · ' + info.operator : '');
                                    button.addEventListener('click', function () {
                                        addPerson('phone:' + info.phone, info.name, info.phone, [{name: 'extra_phone[]', value: info.phone}]);
                                        hits.innerHTML = '';
                                        search.value = '';
                                    });
                                    hits.appendChild(button);
                                });
                            return;
                        }
                        var none = document.createElement('div');
                        none.className = 'list-group-item text-muted';
                        none.textContent = 'No matching name or number';
                        hits.appendChild(none);
                    }
                });
        }, 200);
    });
    document.getElementById('inviteAddPhone').addEventListener('click', function () {
        phoneRow.style.display = 'block';
        phoneInput.focus();
    });
    var phoneWait = null;
    phoneInput.addEventListener('input', function () {
        clearTimeout(phoneWait);
        var raw = phoneInput.value.trim();
        if (raw.replace(/\D/g, '').length < 9) return;
        phoneWait = setTimeout(function () {
            fetch(lookupUrl + '?phone=' + encodeURIComponent(raw), {headers: {Accept: 'application/json'}})
                .then(function (response) { return response.json(); })
                .then(function (info) {
                    if (!info || !info.ok) return;
                    var label = info.name ? info.name : info.phone;
                    addPerson('phone:' + info.phone, label, info.phone, [{name: 'extra_phone[]', value: info.phone}]);
                    phoneInput.value = '';
                });
        }, 400);
    });
    form.addEventListener('submit', function (event) {
        if (!body.querySelector('tr[data-key]')) event.preventDefault();
    });
    document.getElementById('copyLink').addEventListener('click', function () {
        var input = document.getElementById('requestUrl');
        var button = this;
        input.focus();
        input.select();
        var done = function () { button.textContent = 'Copied'; };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(done);
            return;
        }
        document.execCommand('copy');
        done();
    });
})();
</script>
@endsection
