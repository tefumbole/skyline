@extends('layout.main')
@section('content')
@php
    $money = function ($amount) {
        return number_format((float) $amount, 0).' XAF';
    };
@endphp
<style>
    .ex-page { max-width: 1080px; margin: 0 auto 48px; }
    .ex-hero { background: #0c1830; color: #fff; border-radius: 22px; padding: 22px 24px; }
    .ex-kicker { color: #e1b368; font-size: 12px; letter-spacing: .14em; text-transform: uppercase; margin: 0 0 6px; }
    .ex-hero h1 { margin: 0; font-size: 32px; }
    .ex-hero p { margin: 8px 0 0; color: #d5deea; max-width: 640px; }
    .ex-layout { display: grid; grid-template-columns: 320px 1fr; gap: 16px; margin-top: 16px; }
    .ex-card { background: #fff; border-radius: 18px; padding: 16px; box-shadow: 0 8px 24px rgba(12,24,48,.06); }
    .ex-card h2 { margin: 0 0 12px; font-size: 18px; color: #0c1830; }
    .ex-field { margin-bottom: 12px; }
    .ex-field label { display: block; font-size: 12px; font-weight: 700; color: #5c6b80; margin-bottom: 4px; text-transform: uppercase; }
    .ex-field input { width: 100%; border: 1px solid #d5deea; border-radius: 12px; min-height: 44px; padding: 8px 12px; background: #f8fafc; }
    .ex-btn { display: inline-block; min-height: 44px; line-height: 44px; padding: 0 16px; border-radius: 12px; font-weight: 700; border: 0; background: #e1b368; color: #0c1830; cursor: pointer; text-decoration: none; }
    .ex-grid { display: grid; gap: 12px; }
    .ex-cat { background: #fff; border-radius: 18px; padding: 16px; box-shadow: 0 8px 24px rgba(12,24,48,.06); }
    .ex-cat h3 { margin: 0; color: #0c1830; }
    .ex-meta { color: #5c6b80; font-size: 13px; }
    .ex-bar { height: 8px; background: #e7edf5; border-radius: 99px; margin: 10px 0; overflow: hidden; }
    .ex-bar i { display: block; height: 100%; background: #0c1830; }
    .ex-bar.over i { background: #8a1f1f; }
    .ex-inline { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
    .ex-inline input { border: 1px solid #d5deea; border-radius: 10px; min-height: 40px; padding: 0 10px; }
    @media (max-width: 860px) { .ex-layout { grid-template-columns: 1fr; } }
</style>
<section class="ex-page">
    @if($errors->has('code') || $errors->has('name'))
        <div class="alert alert-danger">{{ $errors->first('code') ?: $errors->first('name') }}</div>
    @endif
    @if(session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif
    <div class="ex-hero">
        <p class="ex-kicker">Budgets</p>
        <h1>Expense categories</h1>
        <p>Create a category and a fixed budget. Every expense in that category counts against it. <a href="{{ route('expenses.index') }}" style="color:#e1b368;">Back to expenses</a></p>
    </div>
    <div class="ex-layout">
        <form class="ex-card" method="post" action="{{ route('expense_categories.store') }}">
            @csrf
            <h2>New category</h2>
            <input type="hidden" name="is_active" value="1">
            <div class="ex-field">
                <label>Name</label>
                <input type="text" name="name" required placeholder="Sound, transport, food…">
            </div>
            <div class="ex-field">
                <label>Budget (XAF)</label>
                <input type="number" name="budget_amount" min="0" step="1" placeholder="Optional fixed budget">
            </div>
            <button class="ex-btn" type="submit">Save category</button>
        </form>
        <div class="ex-grid">
            @foreach($lims_expense_category_all as $category)
                @php
                    $used = isset($spent[$category->id]) ? (float) $spent[$category->id] : 0;
                    $budget = $category->budget_amount !== null ? (float) $category->budget_amount : null;
                    $pct = ($budget && $budget > 0) ? min(100, round(($used / $budget) * 100)) : 0;
                @endphp
                <article class="ex-cat">
                    <h3>{{ $category->name }}</h3>
                    <div class="ex-meta">Code {{ $category->code }}</div>
                    @if($budget !== null && $budget > 0)
                        <div class="ex-meta" style="margin-top:6px;">{{ $money($used) }} spent of {{ $money($budget) }}</div>
                        <div class="ex-bar {{ $used > $budget ? 'over' : '' }}"><i style="width: {{ $pct }}%"></i></div>
                        <a href="{{ route('expenses.index', ['category' => $category->id]) }}">See expenses</a>
                    @else
                        <div class="ex-meta" style="margin-top:6px;">{{ $money($used) }} spent · no fixed budget yet</div>
                    @endif
                    <form class="ex-inline" method="post" action="{{ route('expense_categories.update', $category->id) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="expense_category_id" value="{{ $category->id }}">
                        <input type="hidden" name="code" value="{{ $category->code }}">
                        <input type="text" name="name" value="{{ $category->name }}" required>
                        <input type="number" name="budget_amount" min="0" step="1" value="{{ $budget !== null ? $budget : '' }}" placeholder="Budget">
                        <button class="ex-btn" type="submit">Update</button>
                    </form>
                </article>
            @endforeach
        </div>
    </div>
</section>
<script>
    $("ul#expense").siblings('a').attr('aria-expanded','true');
    $("ul#expense").addClass("show");
    $("ul#expense #exp-cat-menu").addClass("active");
</script>
@endsection
