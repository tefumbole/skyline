@php
    $board = $dash_tables ?? ['rows' => [], 'sold' => 0, 'paid' => 0, 'pending' => 0, 'bills' => 0, 'top_amount' => null, 'top_frequency' => null];
    $money = function ($amount) {
        return number_format((float) $amount, 0, '.', ',');
    };
    $topAmount = $board['top_amount'] ?? null;
    $topVisits = $board['top_frequency'] ?? null;
@endphp
<section class="dash-sales">
    <div class="dash-sale-head">
        <h4>Today's Tables</h4>
        <a href="{{ route('report.tables', ['range' => 'today']) }}">Open report</a>
    </div>
    <div class="dash-sale-cards">
        <div><span>Paid</span><strong>{{ $money($board['paid'] ?? 0) }}</strong></div>
        <div><span>Pending</span><strong>{{ $money($board['pending'] ?? 0) }}</strong></div>
        <div><span>Sold</span><strong>{{ $money($board['sold'] ?? 0) }}</strong></div>
    </div>
    <div class="dash-sale-cards">
        <div>
            <span>Highest by amount</span>
            <strong>{{ $topAmount ? $topAmount['name'] : '—' }}</strong>
        </div>
        <div>
            <span>Highest by frequency</span>
            <strong>{{ $topVisits ? $topVisits['name'].' · '.$topVisits['bills'] : '—' }}</strong>
        </div>
    </div>
    <div class="dash-sale-table-wrap">
        <table class="dash-sale-table">
            <thead>
                <tr>
                    <th>Table</th>
                    <th class="num">Sold</th>
                    <th class="num">Paid</th>
                    <th class="num">Pending</th>
                    <th class="num">Bills</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($board['rows'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['rank'] }}. {{ $row['name'] }}</td>
                        <td class="num">{{ $money($row['sold']) }}</td>
                        <td class="num">{{ $money($row['paid']) }}</td>
                        <td class="num">{{ $money($row['pending']) }}</td>
                        <td class="num">{{ $row['bills'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No table sales today.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
