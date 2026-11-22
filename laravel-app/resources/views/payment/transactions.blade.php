@extends('layout.main')
@section('content')
@include('payout.partials.style')
<style>
    .pay-ledger .pay-card { border: 0; border-radius: 18px; box-shadow: 0 12px 32px rgba(12, 24, 48, .08); }
    .pay-ledger .pay-card-head { padding: 18px 22px; background: #0c1830; border: 0; }
    .pay-ledger .pay-card-head h2 { color: #e1b368; font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .pay-ledger .pay-table th { padding: 14px 16px; background: #f7f4ee; color: #8a6a32; border: 0; font-size: 11px; letter-spacing: .08em; }
    .pay-ledger .pay-table td { padding: 16px; border-bottom: 1px solid #eef1f6; color: #1c2434; }
    .pay-ledger .pay-table tbody tr:hover td { background: #f8fafc; }
    .pay-ledger .pay-time { color: #5c6b82; font-size: 13px; font-weight: 700; white-space: nowrap; }
    .pay-ledger .pay-amt { font-variant-numeric: tabular-nums; font-weight: 750; white-space: nowrap; }
    .pay-ledger .is-in { color: #178a45; }
    .pay-ledger .is-out { color: #9b1c1c; }
    .pay-ledger .pay-who { color: #0c1830; font-weight: 750; }
    .pay-ledger .pay-note-cell { max-width: 220px; color: #5c6b82; }
    .pay-sums { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
    .pay-sum { min-width: 180px; padding: 16px 18px; border-radius: 18px; background: #fff; box-shadow: 0 12px 32px rgba(12, 24, 48, .08); }
    .pay-sum span { display: block; color: #5c6b82; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .pay-sum strong { display: block; margin-top: 4px; font-size: 26px; letter-spacing: -.03em; }
    .pay-find { max-width: 320px; }
</style>
<section class="container-fluid pay-app pay-ledger">
    <script>
        $("ul#payments").addClass("show").siblings("a").attr("aria-expanded", "true");
        $("ul#payments #payment-transactions-menu").addClass("active");
    </script>
    @php
        $inTotal = 0;
        $outTotal = 0;
        foreach ($rows as $row) {
            if ($row['tone'] !== 'ok') {
                continue;
            }
            if ($row['direction'] === 'withdrawal') {
                $outTotal += $row['amount'];
            } else {
                $inTotal += $row['amount'];
            }
        }
    @endphp
    <div class="pay-sums">
        <div class="pay-sum">
            <span>Deposits</span>
            <strong class="is-in">{{ number_format($inTotal, 0, '.', ' ') }} XAF</strong>
        </div>
        <div class="pay-sum">
            <span>Withdrawals</span>
            <strong class="is-out">{{ number_format($outTotal, 0, '.', ' ') }} XAF</strong>
        </div>
    </div>
    <div class="pay-card">
        <div class="pay-card-head">
            <h2>Transactions</h2>
            <input type="search" id="momoFind" class="form-control pay-find" placeholder="Search name or number">
        </div>
        <div class="table-responsive">
            <table class="pay-table" id="momoHistory">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Done by</th>
                        <th>Sender / receiver</th>
                        <th>Requested by</th>
                        <th>Status</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $incoming = $row['direction'] !== 'withdrawal';
                            $kind = $incoming ? 'Deposit' : 'Withdrawal';
                        @endphp
                        <tr>
                            <td class="pay-time">{{ $row['when'] }}</td>
                            <td><span class="pay-pill {{ $incoming ? 'pay-pill-ok' : 'pay-pill-no' }}">{{ $kind }}</span></td>
                            <td class="pay-amt {{ $incoming ? 'is-in' : 'is-out' }}">{{ $incoming ? '' : '−' }}{{ number_format($row['amount'], 0, '.', ' ') }} XAF</td>
                            <td class="pay-who">{{ $row['done_by'] !== '' ? $row['done_by'] : '—' }}</td>
                            <td>{{ $row['number'] !== '' ? $row['number'] : '—' }}<div class="pay-muted">{{ $incoming ? 'Sender' : 'Receiver' }}</div></td>
                            <td>{{ $row['requested_by'] !== '' ? $row['requested_by'] : '—' }}</td>
                            <td><span class="pay-pill pay-pill-{{ $row['tone'] }}">{{ $row['status'] }}</span></td>
                            <td class="pay-note-cell">{{ $row['note'] !== '' ? $row['note'] : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="pay-muted">No Mobile Money transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
<script>
    (function () {
        var box = document.getElementById('momoFind');
        var table = document.getElementById('momoHistory');
        if (!box || !table) return;
        box.addEventListener('input', function () {
            var needle = box.value.toLowerCase();
            var lines = table.querySelectorAll('tbody tr');
            for (var i = 0; i < lines.length; i++) {
                var text = (lines[i].textContent || '').toLowerCase();
                lines[i].style.display = needle === '' || text.indexOf(needle) !== -1 ? '' : 'none';
            }
        });
    })();
</script>
@endsection
