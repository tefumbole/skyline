@extends('layout.main')
@section('content')
<style>
    .bill-board { max-width: 760px; margin: 0 auto; padding: 8px 4px 40px; }
    .bill-back { display: inline-flex; align-items: center; min-height: 44px; color: #0c1830; font-weight: 700; text-decoration: none; }
    .bill-board h1 { margin: 8px 0 0; color: #0c1830; font-size: 32px; font-weight: 700; letter-spacing: -.03em; }
    .bill-kicker { margin: 12px 0 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .bill-card { margin-top: 14px; padding: 16px; border: 1px solid #d7deea; border-radius: 18px; background: #fff; }
    .bill-line { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid #e6ebf2; }
    .bill-line strong { display: block; color: #0c1830; font-size: 16px; }
    .bill-line span { color: #5c6b82; font-size: 13px; }
    .bill-line input { width: 88px; min-height: 44px; border: 1px solid #d7deea; border-radius: 12px; text-align: center; font-size: 16px; }
    .bill-add { display: grid; grid-template-columns: 1fr 100px; gap: 8px; margin-top: 8px; }
    .bill-add .form-control, .bill-add .bootstrap-select { min-height: 44px; }
    .bill-save { width: 100%; min-height: 48px; margin-top: 14px; border: 0; border-radius: 14px; background: #0c1830; color: #e1b368; font-weight: 800; }
    .bill-note { margin: 8px 0 0; color: #5c6b82; font-size: 13px; }
    @media (max-width: 640px) {
        .bill-board h1 { font-size: 26px; }
        .bill-add { grid-template-columns: 1fr; }
    }
</style>
<section class="container-fluid bill-board">
    @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
    <a class="bill-back" href="{{ route('tables.show', $tableId) }}">&larr; {{ $tableName }}</a>
    <p class="bill-kicker">Modify order</p>
    <h1>{{ $sale->reference_no }}</h1>
    <p class="bill-note">Change a quantity, set it to 0 to remove that item, or add something else. Food and drinks go to the kitchen or bar. The amount due updates when you save.</p>
    <form method="post" action="{{ route('tables.order.update', [$tableId, $sale->id]) }}" class="bill-card">
        @csrf
        @foreach($lines as $line)
            <div class="bill-line">
                <div>
                    <strong>{{ $line['name'] }}</strong>
                    <span>{{ \App\Support\TableBoard::money($line['unit']) }} XAF each · {{ ucfirst($line['status']) }}</span>
                </div>
                <input type="number" name="qty[{{ $line['id'] }}]" min="0" max="99" step="1" value="{{ $line['qty'] + 0 }}" required aria-label="Quantity for {{ $line['name'] }}">
            </div>
        @endforeach
        <p class="bill-kicker" style="margin-top:18px;">Add an item</p>
        <div class="bill-add">
            <select name="product_id" class="form-control selectpicker" data-live-search="true" title="Choose a product">
                <option value="">Choose a product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}@if($product->code) · {{ $product->code }}@endif · {{ \App\Support\TableBoard::money($product->price) }}</option>
                @endforeach
            </select>
            <input class="form-control" type="number" name="add_qty" min="1" max="99" step="1" placeholder="Qty" aria-label="Quantity to add">
        </div>
        <button class="bill-save" type="submit">Save order</button>
    </form>
</section>
<script>
    var tablesMenu = document.getElementById('tables-menu');
    if (tablesMenu) tablesMenu.classList.add('active');
</script>
@endsection
