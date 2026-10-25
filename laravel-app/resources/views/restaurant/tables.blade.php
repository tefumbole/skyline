@extends('layout.main')
@section('content')
    <style>
        .sky-head h1 { margin: 2px 0 0; font-size: 28px; font-weight: 700; color: #0c1830; }
        .sky-kicker { margin: 0; font-size: 12px; letter-spacing: .14em; text-transform: uppercase; color: #8a7350; }
        .sky-tables { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-top: 16px; }
        .sky-table-card {
            background: #fff; border: 1px solid #d7deea; border-radius: 16px;
            padding: 16px; text-align: center; color: #0c1830;
        }
        .sky-table-card strong { display: block; font-size: 22px; }
        .sky-table-card span { display: block; margin-top: 4px; color: #5c6b80; font-size: 13px; }
    </style>
    <section class="container-fluid">
        <header class="sky-head">
            <p class="sky-kicker">Orders</p>
            <h1>Tables</h1>
        </header>
        <div class="sky-tables">
            @forelse($tables as $table)
                <article class="sky-table-card">
                    <strong>{{ $table->name }}</strong>
                    @if($table->capacity)<span>{{ $table->capacity }} persons</span>@endif
                    @if($table->description)<span>{{ $table->description }}</span>@endif
                </article>
            @empty
                <p>No tables yet.</p>
            @endforelse
        </div>
    </section>
@endsection
