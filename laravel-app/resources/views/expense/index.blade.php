@extends('layout.main')
@section('content')
@php
    $money = function ($amount) {
        return number_format((float) $amount, 0).' XAF';
    };
@endphp
<style>
    section.ex-page {
        width: 100%;
        max-width: 640px;
        margin: 0 auto 24px;
        padding: 0 0 16px;
        box-sizing: border-box;
    }
    .ex-card {
        background: #fff;
        border-radius: 16px;
        padding: 14px;
        box-shadow: 0 8px 24px rgba(12,24,48,.06);
        scroll-margin-top: 12px;
    }
    .ex-card h2 { margin: 0 0 10px; color: #0c1830; font-size: 22px; line-height: 1.2; }
    .ex-field { margin-bottom: 10px; min-width: 0; }
    .ex-field label { display: block; font-size: 12px; font-weight: 700; color: #5c6b80; margin-bottom: 4px; letter-spacing: .04em; text-transform: uppercase; }
    .ex-field input, .ex-field select, .ex-field textarea {
        display: block;
        width: 100% !important;
        max-width: 100%;
        border: 1px solid #d5deea;
        border-radius: 12px;
        min-height: 48px;
        padding: 10px 12px;
        background: #fff;
        color: #0c1830;
        font-size: 16px;
        line-height: 1.3;
        box-sizing: border-box;
    }
    .ex-field textarea { min-height: 72px; resize: vertical; }
    .ex-actions { display: flex; flex-direction: column; gap: 8px; }
    .ex-btn {
        display: block; width: 100%; min-height: 48px; line-height: 48px; padding: 0 16px;
        border-radius: 12px; font-weight: 700; border: 0; cursor: pointer; text-align: center; font-size: 16px;
        box-sizing: border-box;
    }
    .ex-btn.gold { background: #e1b368; color: #0c1830; }
    .ex-btn.quiet { color: #0c1830; background: #f3f6fb; }
    .ex-list { display: flex; flex-direction: column; gap: 10px; margin-top: 14px; }
    .ex-item { background: #fff; border-radius: 16px; padding: 14px; box-shadow: 0 8px 24px rgba(12,24,48,.06); }
    .ex-item header { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
    .ex-item h3 { margin: 0; font-size: 17px; color: #0c1830; word-break: break-word; }
    .ex-meta { color: #5c6b80; font-size: 13px; margin-top: 4px; word-break: break-word; }
    .ex-amount { font-size: 18px; font-weight: 700; color: #0c1830; white-space: nowrap; }
    .ex-empty { color: #5c6b80; background: #fff; border-radius: 16px; padding: 16px; margin-top: 12px; }
    .ex-link { display: block; width: 100%; margin-top: 10px; min-height: 44px; border-radius: 12px; border: 1px solid #d5deea; background: #fff; color: #0c1830; font-weight: 700; font-size: 16px; }
    .ex-page .pagination { flex-wrap: wrap; justify-content: center; margin: 8px 0 0; }
    @media (max-width: 359px) {
        .ex-card, .ex-item, .ex-empty { padding: 12px; border-radius: 12px; }
        .ex-card h2 { font-size: 20px; }
        .ex-field { margin-bottom: 8px; }
        .ex-field textarea { min-height: 64px; }
    }
    @media (max-height: 700px) and (max-width: 767px) {
        .ex-field { margin-bottom: 8px; }
        .ex-field textarea { min-height: 56px; }
        .ex-card h2 { margin-bottom: 8px; }
    }
    @media (min-width: 768px) {
        .ex-card { padding: 18px; }
        .ex-card h2 { font-size: 26px; }
        .ex-actions { flex-direction: row; justify-content: flex-end; }
        .ex-btn { width: auto; min-width: 160px; }
    }
</style>
<section class="ex-page">
    @if(session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif
    @if(session()->has('not_permitted'))
        <div class="alert alert-danger">{{ session('not_permitted') }}</div>
    @endif

    @if($canAdd || $canEdit)
    <form class="ex-card" id="ex-form" method="post" action="{{ route('expenses.store') }}">
        @csrf
        <input type="hidden" name="_method" id="ex-method" value="POST">
        <input type="hidden" name="expense_id" id="ex-id" value="">
        <h2 id="ex-title">Create expense</h2>
        <div class="ex-field">
            <label>Date</label>
            <input type="date" name="spent_on" id="ex-date" required value="{{ date('Y-m-d') }}">
        </div>
        <div class="ex-field">
            <label>Category</label>
            <select name="expense_category_id" id="ex-category" required>
                <option value="">Choose a category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->parent_id && isset($stats[$category->parent_id]) ? $stats[$category->parent_id]['name'].' / ' : '' }}{{ $category->name }}</option>
                @endforeach
            </select>
            @if($categories->isEmpty())
                <p style="margin-top:6px;">Add a category first from Categories &amp; budgets.</p>
            @endif
        </div>
        <div class="ex-field">
            <label>Amount (XAF)</label>
            <input type="number" name="amount" id="ex-amount" min="0" step="1" inputmode="numeric" required placeholder="0">
        </div>
        <div class="ex-field">
            <label>Description</label>
            <textarea name="note" id="ex-note" required></textarea>
        </div>
        <div class="ex-actions">
            <button type="button" class="ex-btn quiet" id="ex-reset" hidden>Cancel edit</button>
            <button type="submit" class="ex-btn gold" id="ex-save">Save expense</button>
        </div>
    </form>
    @endif

    <div class="ex-list">
        @forelse($expenses as $expense)
            <article class="ex-item">
                <header>
                    <div>
                        <h3>{{ $expense->expenseCategory ? $expense->expenseCategory->name : 'Expense' }}</h3>
                        <div class="ex-meta">{{ $expense->created_at ? $expense->created_at->format('j M Y') : '' }}</div>
                    </div>
                    <div class="ex-amount">{{ $money($expense->amount) }}</div>
                </header>
                @if($expense->note)
                    <p class="ex-meta">{{ $expense->note }}</p>
                @endif
                @if($canEdit)
                    <button type="button" class="ex-link ex-edit"
                        data-id="{{ $expense->id }}"
                        data-date="{{ $expense->created_at ? $expense->created_at->format('Y-m-d') : date('Y-m-d') }}"
                        data-category="{{ $expense->expense_category_id }}"
                        data-amount="{{ $expense->amount }}"
                        data-note="{{ $expense->note }}">Edit</button>
                @endif
            </article>
        @empty
            <p class="ex-empty">No expenses yet.</p>
        @endforelse
    </div>
    <div style="margin-top:12px;">{{ $expenses->links() }}</div>
</section>
<script>
    $("ul#expense").siblings('a').attr('aria-expanded','true');
    $("ul#expense").addClass("show");
    $("ul#expense #exp-list-menu").addClass("active");
    (function () {
        var form = document.getElementById('ex-form');
        if (!form) return;
        var storeUrl = @json(route('expenses.store'));
        var updateBase = @json(url('expenses'));
        var today = @json(date('Y-m-d'));
        var reset = document.getElementById('ex-reset');
        function createMode() {
            form.action = storeUrl;
            document.getElementById('ex-method').value = 'POST';
            document.getElementById('ex-id').value = '';
            document.getElementById('ex-title').textContent = 'Create expense';
            document.getElementById('ex-save').textContent = 'Save expense';
            document.getElementById('ex-date').value = today;
            document.getElementById('ex-category').value = '';
            document.getElementById('ex-amount').value = '';
            document.getElementById('ex-note').value = '';
            reset.hidden = true;
        }
        reset.addEventListener('click', function () {
            createMode();
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        if (window.matchMedia('(max-width: 767px)').matches) {
            setTimeout(function () { form.scrollIntoView({ block: 'start' }); }, 50);
        }
        var edits = document.querySelectorAll('.ex-edit');
        for (var i = 0; i < edits.length; i++) {
            edits[i].addEventListener('click', function () {
                form.action = updateBase + '/' + this.getAttribute('data-id');
                document.getElementById('ex-method').value = 'PUT';
                document.getElementById('ex-id').value = this.getAttribute('data-id');
                document.getElementById('ex-title').textContent = 'Edit expense';
                document.getElementById('ex-save').textContent = 'Save changes';
                document.getElementById('ex-date').value = this.getAttribute('data-date');
                document.getElementById('ex-category').value = this.getAttribute('data-category');
                document.getElementById('ex-amount').value = this.getAttribute('data-amount');
                document.getElementById('ex-note').value = this.getAttribute('data-note') || '';
                reset.hidden = false;
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    })();
</script>
@endsection
