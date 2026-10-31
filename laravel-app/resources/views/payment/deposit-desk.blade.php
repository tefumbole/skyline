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

    <div class="pay-card">
        <div class="pay-card-head"><h2>Deposit</h2></div>
        <div class="pay-card-body">
            <p class="pay-help">Add a phone number. The name on that MoMo account is filled in from Campay, then the person approves the amount on their phone.</p>
            <div class="pay-actions" style="margin-top:0">
                <button class="pay-plus" type="button" id="depositAdd" title="Add a number">+</button>
            </div>
            <form method="POST" action="{{ route('deposit.desk.store') }}" id="depositForm" style="display:none;margin-top:14px">
                @csrf
                <div class="pay-search">
                    <label>Phone number</label>
                    <input type="tel" name="phone" id="depositPhone" class="form-control" placeholder="6XX XXX XXX" autocomplete="off" required>
                    <div id="depositName" class="pay-muted" style="margin-top:8px"></div>
                </div>
                <div class="form-group" style="max-width:220px;margin-top:12px">
                    <label>Amount (XAF)</label>
                    <input type="number" name="amount" class="form-control" min="100" max="1000000" step="1" required placeholder="100">
                </div>
                <div class="pay-actions">
                    <button class="pay-go" type="submit">Request</button>
                </div>
            </form>
        </div>
    </div>

    <div class="pay-card">
        <form method="POST" action="{{ route('deposit.desk.delete') }}" id="depositDeleteForm">@csrf</form>
        <div class="pay-card-head">
            <h2>Waiting for approval</h2>
            @if($rows->count())
                <button class="pay-danger" type="submit" form="depositDeleteForm">Delete selected</button>
            @endif
        </div>
        <div class="table-responsive">
            <table class="pay-table">
                <thead>
                    <tr>
                        <th style="width:36px">@if($rows->count())<input type="checkbox" id="depositAll" form="depositDeleteForm" title="Select all">@endif</th>
                        <th>When</th>
                        <th>Name on MoMo</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $timedOut = strpos((string) $row->error, 'Timed out') === 0;
                        @endphp
                        <tr>
                            <td><input type="checkbox" class="deposit-row" form="depositDeleteForm" name="ids[]" value="{{ $row->id }}"></td>
                            <td>{{ $row->created_at ? $row->created_at->format('M j H:i') : '' }}</td>
                            <td>{{ $row->person_name }}</td>
                            <td>{{ $row->phone }}</td>
                            <td>{{ number_format($row->amount, 0, '.', ' ') }} XAF</td>
                            <td>
                                @if($timedOut)
                                    <span class="pay-pill pay-pill-no">Timed out</span>
                                    <form method="POST" action="{{ route('deposit.desk.resend') }}" style="display:inline">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $row->id }}">
                                        <button class="pay-open" type="submit" style="border:0;cursor:pointer;margin-left:8px">Resend</button>
                                    </form>
                                @else
                                    <span class="pay-pill pay-pill-wait">Waiting</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="pay-muted">No deposit is waiting. Approved and rejected requests leave this list.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
<script>
(function () {
    var add = document.getElementById('depositAdd');
    var form = document.getElementById('depositForm');
    var phone = document.getElementById('depositPhone');
    var name = document.getElementById('depositName');
    var lookupUrl = @json(route('deposit.phone'));
    var wait = null;
    add.addEventListener('click', function () {
        form.style.display = 'block';
        phone.focus();
    });
    phone.addEventListener('input', function () {
        clearTimeout(wait);
        var raw = phone.value.trim();
        if (raw.replace(/\D/g, '').length < 9) {
            name.textContent = '';
            return;
        }
        name.textContent = 'Looking up the MoMo name…';
        wait = setTimeout(function () {
            fetch(lookupUrl + '?phone=' + encodeURIComponent(raw), {headers: {Accept: 'application/json'}})
                .then(function (response) { return response.json(); })
                .then(function (info) {
                    if (!info || !info.ok) {
                        name.textContent = (info && info.error) ? info.error : 'Not an MTN or Orange number';
                        return;
                    }
                    name.textContent = info.name ? info.name : 'Name not found on MoMo';
                    if (info.operator) name.textContent += ' · ' + info.operator;
                })
                .catch(function () { name.textContent = 'Could not look up that number'; });
        }, 400);
    });
    var deleteForm = document.getElementById('depositDeleteForm');
    var all = document.getElementById('depositAll');
    function boxes() { return document.querySelectorAll('.deposit-row'); }
    if (all) all.addEventListener('change', function () {
        boxes().forEach(function (box) { box.checked = all.checked; });
    });
    deleteForm.addEventListener('submit', function (event) {
        var count = 0;
        boxes().forEach(function (box) { if (box.checked) count += 1; });
        if (count < 1) {
            event.preventDefault();
            window.alert('Select the requests to delete.');
            return;
        }
        if (!window.confirm('Delete ' + count + ' ' + (count === 1 ? 'request' : 'requests') + '?')) {
            event.preventDefault();
        }
    });
})();
</script>
@endsection
