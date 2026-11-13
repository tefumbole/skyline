@extends('layout.main')
@section('content')
<style>
    .table-board { max-width: 1100px; margin: 0 auto; padding: 8px 4px 28px; }
    .table-board h1 { margin: 4px 0 0; color: #0c1830; font-size: 32px; font-weight: 700; letter-spacing: -.03em; }
    .table-kicker { margin: 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .table-lead { margin: 8px 0 0; color: #5c6b82; font-size: 15px; }
    .table-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)); gap: 10px; margin-top: 18px; }
    .table-card {
        display: block; min-height: 0; padding: 14px 12px; border-radius: 16px; background: #e1b368;
        border: 0; box-shadow: 0 8px 20px rgba(12,24,48,.08);
        text-decoration: none; color: #0c1830;
    }
    .table-card:hover { background: #d7a45a; color: #0c1830; }
    .table-card:active { transform: scale(.99); }
    .table-name { margin: 0; color: #0c1830; font-size: 18px; font-weight: 800; }
    .table-sub { margin: 4px 0 0; color: #3d2e16; font-size: 12px; }
    .table-pill { display: inline-block; margin-top: 8px; padding: 3px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
    .table-pill.pending, .table-pill.open, .table-pill.waiting, .table-pill.accepted, .table-pill.hold { background: #0c1830; color: #e1b368; }
    .table-pill.due, .table-pill.ready { background: #fde8e8; color: #8a1f1f; }
    .table-pill.paid, .table-pill.delivered { background: #0c1830; color: #e1b368; }
    .table-empty { margin-top: 18px; padding: 28px 18px; border: 1px dashed #d7deea; border-radius: 18px; background: #fff; color: #5c6b82; text-align: center; }
    @media (max-width: 640px) {
        .table-board h1 { font-size: 26px; }
        .table-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
<section class="container-fluid table-board">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <p class="table-kicker">Sales</p>
    <h1>Tables</h1>
    <p class="table-lead">Only tables with an open order are listed. A table leaves this list once every dish is delivered.</p>
    @if(count($tables))
        <div class="table-grid">
            @foreach($tables as $row)
                <a class="table-card" href="{{ route('tables.show', $row['id']) }}">
                    <p class="table-name">{{ $row['name'] }}</p>
                    <p class="table-sub">{{ (int) $row['bills'] === 0 ? 'No orders' : ($row['bills'].' order'.($row['bills'] === 1 ? '' : 's')) }}</p>
                    <span class="table-pill {{ $row['status_class'] }}">{{ $row['status'] }}</span>
                </a>
            @endforeach
        </div>
    @else
        <div class="table-empty">No table has an open order.</div>
    @endif
</section>
<script>
    var tablesMenu = document.getElementById('tables-menu');
    if (tablesMenu) tablesMenu.classList.add('active');
</script>
@endsection
