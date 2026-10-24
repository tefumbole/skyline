@extends('layout.main')
@section('content')
    <style>
        .sky-board { display:flex; flex-direction:column; gap:14px; }
        .sky-tabs { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 10px; }
        .sky-tabs a {
            border:1px solid #0c1830; color:#0c1830; background:#fff; border-radius:999px;
            padding:7px 12px; font-weight:700; text-decoration:none;
        }
        .sky-tabs a.active { background:#0c1830; color:#e1b368; }
        .sky-tabs .badge { background:#e1b368; color:#0c1830; margin-left:6px; }
        .sky-ticket {
            background:#fff; border:1px solid #d7deea; border-radius:16px;
            box-shadow:0 10px 28px rgba(12,24,48,.05);
            padding:16px 18px; display:grid; grid-template-columns:minmax(160px,220px) minmax(220px,1fr) minmax(220px,280px);
            gap:16px; align-items:start;
        }
        .sky-kicker { margin:0; font-size:12px; letter-spacing:.14em; text-transform:uppercase; color:#8a7350; }
        .sky-ref { margin:4px 0 0; font-size:18px; font-weight:700; color:#0c1830; }
        .sky-guest { margin:4px 0 0; color:#243044; }
        .sky-meta { margin:8px 0 0; color:#5c6b80; font-size:13px; }
        .sky-item { margin:0; font-size:20px; line-height:1.25; color:#0c1830; }
        .sky-pill {
            display:inline-block; margin-top:8px; padding:4px 10px; border-radius:999px;
            background:#f6f0e4; color:#6a5420; font-size:12px; font-weight:700;
        }
        .sky-pill.late { background:#fde8e8; color:#8a1f1f; }
        .sky-head h1 { margin:2px 0 0; font-size:28px; font-weight:700; color:#0c1830; }
        .sky-actions .btn-primary { background:#0c1830; border-color:#0c1830; color:#e1b368; font-weight:700; border-radius:999px; }
        .sky-empty { background:#fff; border:1px dashed #d7deea; border-radius:16px; padding:28px; color:#5c6b80; }
        .sky-note { margin:8px 0 0; padding:8px 10px; border-radius:12px; background:#f6f0e4; color:#6a5420; font-size:13px; }
        @media (max-width:900px) { .sky-ticket { grid-template-columns:1fr; } }
    </style>
    <section class="container-fluid">
        @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
        @if(session('not_permitted'))<div class="alert alert-danger">{{ session('not_permitted') }}</div>@endif
        <header class="sky-head">
            <p class="sky-kicker">Orders</p>
            <h1>{{ $lanes[$lane] ?? 'Orders' }} · {{ $tabs[$tab] ?? '' }}</h1>
        </header>
        <nav class="sky-tabs">
            @foreach($tabs as $tabKey => $tabLabel)
                @php $tabCount = (int) ($counts[$lane][$tabKey] ?? 0); @endphp
                <a class="{{ $tab === $tabKey ? 'active' : '' }}" href="{{ route('restaurant.orders', ['lane' => $lane, 'tab' => $tabKey]) }}">
                    {{ $tabLabel }}@if($tabCount)<span class="badge">{{ $tabCount }}</span>@endif
                </a>
            @endforeach
            @if(\App\Services\Restaurant\RestaurantAccess::can(auth()->user(), 'order-logs'))
                <a href="{{ route('restaurant.logs', ['lane' => $lane]) }}">Log</a>
            @endif
        </nav>
        <div class="sky-board">
            @forelse($items as $item)
                @php
                    $order = $item->order;
                    $sale = $order ? $order->sale : null;
                    $statusLabels = ['awaiting' => 'Awaiting', 'preparing' => 'Accepted', 'ready' => 'Ready for Delivery', 'hold' => 'On Hold', 'delivered' => 'Delivered'];
                    $canAdd = \App\Services\Restaurant\RestaurantAccess::canOrder(auth()->user(), 'add', $item);
                    $canEdit = \App\Services\Restaurant\RestaurantAccess::canOrder(auth()->user(), 'edit', $item);
                    $canDelete = \App\Services\Restaurant\RestaurantAccess::canOrder(auth()->user(), 'delete', $item);
                    $late = $item->expected_ready_at && $item->expected_ready_at->isPast() && $item->status === 'preparing';
                @endphp
                <article class="sky-ticket">
                    <div>
                        <p class="sky-kicker">{{ $order ? $order->serviceLabel() : 'Order' }}</p>
                        <p class="sky-ref">{{ $sale->reference_no ?? $item->restaurant_order_id }}</p>
                        <p class="sky-guest">{{ optional(optional($sale)->customer)->name ?: 'Guest' }}</p>
                        <p class="sky-meta">
                            Placed by {{ optional(optional($order)->placedBy)->name ?: 'Staff' }}
                            @if(optional($order)->diningTable)
                                · {{ $order->diningTable->name }}
                                @if($order->diningTable->capacity) ({{ $order->diningTable->capacity }} persons) @endif
                            @endif
                        </p>
                        @if(optional($order)->waitress && optional($order)->placed_by_user_id != optional($order)->waitress_user_id)
                            <p class="sky-meta">Waitress: {{ $order->waitress->name }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="sky-item">{{ $item->product_name }} <span style="color:#8a7350;">× {{ $item->qty }}</span></p>
                        <span class="sky-pill {{ $late ? 'late' : '' }}">
                            {{ \App\Services\Restaurant\RestaurantDepartment::label($item->lane ?: $item->department) }}
                            · {{ $statusLabels[$item->status] ?? $item->status }}
                        </span>
                        @if(optional($order)->customer_comment)<p class="sky-note">Note: {{ $order->customer_comment }}</p>@endif
                        @if($item->customer_comment)<p class="sky-note">Item note: {{ $item->customer_comment }}</p>@endif
                        <p class="sky-meta">
                            @if($item->ready_at)
                                Ready {{ $item->ready_at->format('H:i') }}
                            @elseif($item->expected_ready_at)
                                Due {{ $item->expected_ready_at->format('H:i') }}
                                @if($late) · taking longer than expected @endif
                            @elseif($item->status === 'preparing')
                                No time given
                            @endif
                        </p>
                    </div>
                    <div class="sky-actions">
                        @if($item->productSale)
                            <p class="sky-meta">{{ number_format((float) $item->productSale->total, 0) }}</p>
                        @endif
                        @if($item->status === 'awaiting' && ($canAdd || $canDelete))
                            <div style="margin-bottom:8px;">
                                @if($canAdd)
                                    <form method="post" action="{{ route('restaurant.accept', $item->id) }}" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-primary" type="submit">Accept</button>
                                    </form>
                                @endif
                                @if($canDelete)
                                    <form method="post" action="{{ route('restaurant.deny', $item->id) }}" style="display:inline;" onsubmit="return confirm('Reject this order?');">
                                        @csrf
                                        <input type="hidden" name="reason" value="Rejected">
                                        <button class="btn btn-default" type="submit">Reject</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                        @if($canEdit && $item->status === 'preparing')
                            <form method="post" action="{{ route('restaurant.ready.mark', $item->id) }}" style="margin-bottom:8px;">
                                @csrf
                                <button class="btn btn-success btn-block" type="submit">Mark ready</button>
                            </form>
                            <form method="post" action="{{ route('restaurant.estimate', $item->id) }}">
                                @csrf
                                <div class="input-group">
                                    <input class="form-control" type="number" name="minutes" min="1" max="240" placeholder="New minutes" required>
                                    <span class="input-group-btn"><button class="btn btn-default" type="submit">Update time</button></span>
                                </div>
                            </form>
                        @endif
                        @if($item->status === 'ready' && $canEdit)
                            <form method="post" action="{{ route('restaurant.deliver', $item->id) }}">
                                @csrf
                                <button class="btn btn-primary btn-block" type="submit">Delivered</button>
                            </form>
                        @endif
                        @if($canDelete && $item->status === 'delivered')
                            <details>
                                <summary>Record rejection</summary>
                                <form method="post" action="{{ route('restaurant.reject', $item->id) }}" style="margin-top:8px;">
                                    @csrf
                                    <input class="form-control" name="qty" type="number" min="0.01" max="{{ $item->qty }}" step="0.01" value="{{ $item->qty }}" required>
                                    <input class="form-control" name="reason" placeholder="Reason" required style="margin-top:6px;">
                                    <select class="form-control" name="resolution" style="margin-top:6px;">
                                        <option value="remake">Remake</option>
                                        <option value="replacement">Replacement</option>
                                        <option value="cancellation">Cancellation</option>
                                        <option value="price_adjustment">Approved price adjustment</option>
                                        <option value="refund">Refund</option>
                                    </select>
                                    <button class="btn btn-warning btn-block" style="margin-top:6px;" type="submit">Record rejection</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </article>
            @empty
                <div class="sky-empty">Nothing in this tab.</div>
            @endforelse
        </div>
    </section>
@endsection
