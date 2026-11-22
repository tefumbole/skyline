@extends('layout.main')
@section('content')
@include('payout.partials.style')
<style>
    .pay-ledger .pay-card { border: 0; border-radius: 18px; box-shadow: 0 12px 32px rgba(12, 24, 48, .08); }
    .pay-ledger .pay-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; background: #0c1830; border: 0; }
    .pay-ledger .pay-card-head h2 { margin: 0; color: #e1b368; font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .pay-ledger .pay-table { font-size: 12px; }
    .pay-ledger .pay-table th { padding: 6px 8px; background: #f7f4ee; color: #8a6a32; border: 0; font-size: 10px; letter-spacing: .06em; }
    .pay-ledger .pay-table td { padding: 5px 8px; border-bottom: 1px solid #eef1f6; color: #1c2434; line-height: 1.25; }
    .pay-ledger .pay-table tbody tr:hover td { background: #f8fafc; }
    .pay-ledger .pay-time { width: 92px; color: #5c6b82; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .pay-ledger .pay-type { width: 78px; }
    .pay-ledger .pay-amt { width: 110px; font-variant-numeric: tabular-nums; font-weight: 750; white-space: nowrap; }
    .pay-ledger .pay-amt.is-in { color: #178a45 !important; }
    .pay-ledger .pay-amt.is-out { color: #9b1c1c !important; }
    .pay-ledger .pay-who { width: 92px; max-width: 92px; overflow: hidden; color: #0c1830; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .pay-ledger .pay-note-cell { width: 150px; max-width: 150px; color: #5c6b82; white-space: nowrap; }
    .pay-ledger .pay-pill { padding: 2px 8px; font-size: 11px; }
    .pay-sums { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
    .pay-sum { min-width: 180px; padding: 14px 16px; border-radius: 18px; background: #fff; box-shadow: 0 12px 32px rgba(12, 24, 48, .08); color: inherit; text-decoration: none; }
    .pay-sum:hover { background: #f3f6fb; color: inherit; text-decoration: none; }
    .pay-sum span { display: block; color: #5c6b82; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .pay-sum strong { display: block; margin-top: 4px; font-size: 26px; letter-spacing: -.03em; }
    .pay-tools { display: flex; align-items: center; gap: 8px; }
    .pay-find { width: 220px; min-height: 36px; }
    .pay-export { display: inline-flex; align-items: center; min-height: 32px; padding: 4px 10px; border-radius: 8px; background: #e1b368; color: #0c1830 !important; font-size: 13px; font-weight: 700; text-decoration: none !important; }
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
        <a class="pay-sum" href="{{ route('deposit.index') }}">
            <span>Deposits</span>
            <strong class="is-in">{{ number_format($inTotal, 0, '.', ' ') }} XAF</strong>
        </a>
        <a class="pay-sum" href="{{ route('payout.index') }}">
            <span>Withdrawals</span>
            <strong class="is-out">{{ number_format($outTotal, 0, '.', ' ') }} XAF</strong>
        </a>
    </div>
    <div class="pay-card">
        <div class="pay-card-head">
            <h2>Transactions</h2>
            <div class="pay-tools">
                <input type="search" id="momoFind" class="form-control pay-find" placeholder="Search name or number">
                <a class="pay-export" target="_blank" href="{{ route('payment.transactions', ['print' => 'letter']) }}">Letterhead</a>
                <a class="pay-export" target="_blank" href="{{ route('payment.transactions', ['print' => 'thermal']) }}">Thermal</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="pay-table" id="momoHistory">
                <thead>
                    <tr>
                        <th>When</th>
                        <th class="pay-type">Type</th>
                        <th>Amount</th>
                        <th class="pay-who">Done by</th>
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
                            $note = \App\Support\MomoLedger::shortNote($row['note'].' '.$row['reference']);
                        @endphp
                        <tr>
                            <td class="pay-time">{{ $row['when'] }}</td>
                            <td class="pay-type"><span class="pay-pill {{ $incoming ? 'pay-pill-ok' : 'pay-pill-no' }}">{{ $kind }}</span></td>
                            <td class="pay-amt {{ $incoming ? 'is-in' : 'is-out' }}">{{ $incoming ? '' : '−' }}{{ number_format($row['amount'], 0, '.', ' ') }}</td>
                            <td class="pay-who">{{ $row['done_by'] !== '' ? $row['done_by'] : '—' }}</td>
                            <td>{{ $row['number'] !== '' ? $row['number'] : '—' }}</td>
                            <td>{{ $row['requested_by'] !== '' ? $row['requested_by'] : '—' }}</td>
                            <td><span class="pay-pill pay-pill-{{ $row['tone'] }}">{{ $row['status'] }}</span></td>
                            <td class="pay-note-cell">{{ $note !== '' ? $note : '—' }}</td>
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
