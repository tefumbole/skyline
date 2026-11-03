@extends('layout.main')
@section('content')
<style>
    .table-board { max-width: 1100px; margin: 0 auto; padding: 8px 4px 28px; }
    .table-board h1 { margin: 4px 0 0; color: #0c1830; font-size: 32px; font-weight: 700; letter-spacing: -.03em; }
    .table-kicker { margin: 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .table-lead { margin: 8px 0 0; color: #5c6b82; font-size: 15px; }
    .table-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-top: 18px; }
    .table-card {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        min-height: 92px; padding: 16px; border-radius: 18px; background: #fff;
        border: 1px solid #d7deea; box-shadow: 0 10px 28px rgba(12,24,48,.05);
        text-decoration: none; color: inherit;
    }
    .table-card:active { transform: scale(.99); }
    .table-name { margin: 0; color: #0c1830; font-size: 22px; font-weight: 700; }
    .table-sub { margin: 4px 0 0; color: #5c6b82; font-size: 13px; }
    .table-due { margin: 0; color: #0c1830; font-size: 20px; font-weight: 800; letter-spacing: -.03em; text-align: right; }
    .table-pill { display: inline-block; margin-top: 6px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .table-pill.pending { background: #fff4e5; color: #8a5a1f; }
    .table-pill.paid { background: #d9f5e5; color: #178a45; }
    .table-empty { margin-top: 18px; padding: 28px 18px; border: 1px dashed #d7deea; border-radius: 18px; background: #fff; color: #5c6b82; text-align: center; }
    @media (min-width: 700px) {
        .table-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (min-width: 1024px) {
        .table-grid { grid-template-columns: 1fr 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .table-board h1 { font-size: 26px; }
        .table-card { min-height: 84px; padding: 14px; }
    }
</style>
<section class="container-fluid table-board">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <p class="table-kicker">Sales</p>
    <h1>Tables</h1>
    <p class="table-lead">Each card is the table chosen when the order was placed.</p>
    @if(count($tables))
        <div class="table-grid">
            @foreach($tables as $row)
                <a class="table-card" href="{{ route('tables.show', $row['id']) }}">
                    <div>
                        <p class="table-name">{{ $row['name'] }}</p>
                        <p class="table-sub">{{ $row['open'] ? $row['open'].' open bill'.($row['open'] === 1 ? '' : 's') : 'Settled today' }}</p>
                    </div>
                    <div>
                        <p class="table-due">{{ \App\Support\TableBoard::money($row['due']) }} XAF</p>
                        <div style="text-align:right"><span class="table-pill {{ strtolower($row['status']) }}">{{ $row['status'] }}</span></div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="table-empty">No table has an open bill right now.</div>
    @endif
</section>
<script>
    var tablesMenu = document.getElementById('tables-menu');
    if (tablesMenu) tablesMenu.classList.add('active');
</script>
@endsection
