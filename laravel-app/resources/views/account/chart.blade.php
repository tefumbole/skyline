@extends('layout.main')
@section('content')
<style>
    .coa { background: #f3f6fb; margin: -8px -12px 0; padding: 12px; }
    .coa-card { background: #fff; border-radius: 18px; box-shadow: 0 12px 32px rgba(12, 24, 48, .08); overflow: hidden; }
    .coa-head { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 14px 18px; background: #0c1830; }
    .coa-head h2 { margin: 0; color: #e1b368; font-size: 14px; font-weight: 800; letter-spacing: .08em; line-height: 1.25; text-transform: uppercase; white-space: nowrap; }
    .coa-head p { margin: 0; color: #fff; font-size: 13px; font-weight: 700; text-align: right; white-space: nowrap; }
    .coa table { width: 100%; border-collapse: collapse; }
    .coa th { padding: 8px 12px; background: #f7f4ee; color: #8a6a32; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; text-align: left; }
    .coa td { padding: 8px 12px; border-bottom: 1px solid #eef1f6; color: #1c2434; font-size: 14px; }
    .coa .group td { background: #0c1830; color: #e1b368; font-weight: 800; letter-spacing: .04em; }
    .coa .code { font-weight: 800; color: #0c1830; white-space: nowrap; }
    .coa .amt { text-align: right; font-variant-numeric: tabular-nums; font-weight: 800; white-space: nowrap; }
    .coa .in { color: #178a45; }
    .coa .out { color: #9b1c1c; }
    .coa tr:hover td { background: #f8fafc; }
    .coa .group:hover td { background: #0c1830; }
</style>
<section class="coa">
    <script>
        $("ul#account").siblings('a').attr('aria-expanded', 'true');
        $("ul#account").addClass("show");
        $("#chart-of-account-menu").addClass("active");
    </script>
    <div class="coa-card">
        <div class="coa-head">
            <h2>Chart of<br>Account</h2>
            <p>Debits {{ number_format($proof['debit'], 0) }} · Credits {{ number_format($proof['credit'], 0) }} XAF</p>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Account</th>
                        <th>Statement</th>
                        <th>Normal balance</th>
                        <th class="amt">Balance (XAF)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $active = array();
                        foreach ($rows as $row) {
                            if (round((float) $row['account']->total_balance, 2) != 0) {
                                $active[] = $row;
                            }
                        }
                    @endphp
                    @if(count($active))
                        <tr class="group"><td colspan="5">Accounts with a balance</td></tr>
                        @foreach($active as $row)
                            @php
                                $balance = (float) $row['account']->total_balance;
                                $tone = $balance < 0 ? 'out' : 'in';
                            @endphp
                            <tr>
                                <td class="code">{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['statement'] }}</td>
                                <td>{{ $row['normal'] }}</td>
                                <td class="amt {{ $tone }}">{{ number_format($balance, 0, '.', ' ') }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr class="group"><td colspan="5">Full chart of account</td></tr>
                    @php $group = ''; @endphp
                    @foreach($rows as $row)
                        @if($group !== $row['group'])
                            @php $group = $row['group']; @endphp
                            <tr class="group"><td colspan="5">Class {{ $row['class'] }} · {{ $group }}</td></tr>
                        @endif
                        @php
                            $balance = (float) $row['account']->total_balance;
                            $tone = $balance < 0 ? 'out' : 'in';
                        @endphp
                        <tr>
                            <td class="code">{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['statement'] }}</td>
                            <td>{{ $row['normal'] }}</td>
                            <td class="amt {{ $tone }}">{{ number_format($balance, 0, '.', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
