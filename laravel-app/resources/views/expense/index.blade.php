@extends('layout.main')
@section('content')
@php
    $money = function ($amount) {
        return number_format((float) $amount, 0).' XAF';
    };
@endphp
<style>
    .ex-page { max-width: 920px; margin: 0 auto 48px; }
    .ex-hero { background: #0c1830; color: #fff; border-radius: 22px; padding: 22px 24px; display: flex; justify-content: space-between; gap: 16px; align-items: flex-end; flex-wrap: wrap; }
    .ex-kicker { color: #e1b368; font-size: 12px; letter-spacing: .14em; text-transform: uppercase; margin: 0 0 6px; }
    .ex-hero h1 { margin: 0; font-size: 32px; font-weight: 700; }
    .ex-hero p { margin: 8px 0 0; color: #d5deea; max-width: 460px; }
    .ex-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .ex-btn { display: inline-block; min-height: 44px; line-height: 44px; padding: 0 16px; border-radius: 12px; font-weight: 700; text-decoration: none; border: 0; cursor: pointer; background: transparent; }
    .ex-btn.gold { background: #e1b368; color: #0c1830; }
    .ex-btn.ghost { color: #fff; border: 1px solid rgba(255,255,255,.35); }
    .ex-btn.quiet { color: #0c1830; background: #f3f6fb; }
    .ex-budgets { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 16px; }
    .ex-budget { background: #fff; border-radius: 16px; padding: 14px 16px; box-shadow: 0 8px 24px rgba(12,24,48,.06); text-decoration: none; color: inherit; display: block; }
    .ex-budget strong { display: block; color: #0c1830; }
    .ex-budget span { color: #5c6b80; font-size: 13px; }
    .ex-bar { height: 8px; background: #e7edf5; border-radius: 99px; margin-top: 10px; overflow: hidden; }
    .ex-bar i { display: block; height: 100%; background: #0c1830; }
    .ex-bar.over i { background: #8a1f1f; }
    .ex-tools { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin: 16px 0; }
    .ex-native { position: absolute; left: -9999px; width: 1px; height: 1px; }
    .ex-combo { position: relative; width: 100%; }
    .ex-filter { width: 260px; }
    .ex-combo-input {
        width: 100%; border: 1px solid #d5deea; border-radius: 10px; height: 44px; padding: 0 12px;
        background: #fff; color: #0c1830; font-size: 15px;
    }
    .ex-combo-list {
        position: absolute; left: 0; right: 0; top: 48px; z-index: 30; background: #fff;
        border: 1px solid #d5deea; border-radius: 12px; max-height: 220px; overflow: auto;
        box-shadow: 0 12px 30px rgba(12,24,48,.12);
    }
    .ex-combo-item, .ex-combo-empty {
        display: block; width: 100%; text-align: left; border: 0; background: #fff; color: #0c1830;
        padding: 10px 12px; font-size: 15px; cursor: pointer;
    }
    .ex-combo-item:hover { background: #f3f6fb; }
    .ex-combo-empty { color: #5c6b80; cursor: default; }
    .ex-list { display: flex; flex-direction: column; gap: 12px; }
    .ex-item { background: #fff; border-radius: 18px; padding: 16px; box-shadow: 0 8px 24px rgba(12,24,48,.06); }
    .ex-item header { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
    .ex-item h3 { margin: 0; font-size: 18px; color: #0c1830; }
    .ex-meta { color: #5c6b80; font-size: 13px; margin-top: 4px; }
    .ex-amount { font-size: 20px; font-weight: 700; color: #0c1830; }
    .ex-empty { color: #5c6b80; background: #fff; border-radius: 18px; padding: 18px; }
    .ex-shade { position: fixed; inset: 0; background: rgba(12,24,48,.45); z-index: 80; display: none; align-items: center; justify-content: center; padding: 16px; }
    .ex-shade.is-open { display: flex; }
    .ex-dialog { width: min(420px, 100%); background: #fff; border-radius: 18px; padding: 18px; box-shadow: 0 20px 50px rgba(12,24,48,.25); }
    .ex-dialog h2 { margin: 0 0 4px; color: #0c1830; font-size: 22px; }
    .ex-dialog p { margin: 0 0 14px; color: #5c6b80; font-size: 13px; }
    .ex-field { margin-bottom: 12px; }
    .ex-field label { display: block; font-size: 12px; font-weight: 700; color: #5c6b80; margin-bottom: 4px; letter-spacing: .04em; text-transform: uppercase; }
    .ex-field input, .ex-field textarea {
        width: 100%; border: 1px solid #d5deea; border-radius: 10px; min-height: 44px; padding: 8px 12px;
        background: #fff; color: #0c1830; font-size: 15px;
    }
    .ex-field textarea { min-height: 80px; }
    .ex-dialog-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 4px; }
    .ex-link { background: none; border: 0; color: #0c1830; font-weight: 700; padding: 0; cursor: pointer; }
</style>
<section class="ex-page">
    @if(session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif
    @if(session()->has('not_permitted'))
        <div class="alert alert-danger">{{ session('not_permitted') }}</div>
    @endif
    <div class="ex-hero">
        <div>
            <p class="ex-kicker">Money out</p>
            <h1>Expenses</h1>
            <p>Create a category with a budget, then add an expense with a date, a category, an amount, and a description.</p>
        </div>
        <div class="ex-actions">
            <a class="ex-btn ghost" href="{{ route('expense_categories.index') }}">Categories &amp; budgets</a>
            @if($canAdd)
                <button type="button" class="ex-btn gold" id="ex-open">Create expense</button>
            @endif
        </div>
    </div>

    <div class="ex-budgets">
        @foreach($categories as $category)
            @php
                $used = isset($spent[$category->id]) ? (float) $spent[$category->id] : 0;
                $budget = $category->budget_amount !== null ? (float) $category->budget_amount : null;
                $pct = ($budget && $budget > 0) ? min(100, round(($used / $budget) * 100)) : 0;
            @endphp
            @if($budget !== null && $budget > 0)
                <a class="ex-budget" href="{{ route('expenses.index', ['category' => $category->id]) }}">
                    <strong>{{ $category->name }}</strong>
                    <span>{{ $money($used) }} of {{ $money($budget) }}</span>
                    <div class="ex-bar {{ $used > $budget ? 'over' : '' }}"><i style="width: {{ $pct }}%"></i></div>
                </a>
            @endif
        @endforeach
    </div>

    <div class="ex-tools">
        <form method="get" action="{{ route('expenses.index') }}">
            <select class="ex-select ex-native" name="category" data-submit="1" data-placeholder="Search categories">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

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

@if($canAdd || $canEdit)
<div class="ex-shade" id="ex-shade">
    <form class="ex-dialog" id="ex-form" method="post" action="{{ route('expenses.store') }}">
        @csrf
        <input type="hidden" name="_method" id="ex-method" value="POST">
        <input type="hidden" name="expense_id" id="ex-id" value="">
        <h2 id="ex-title">Create expense</h2>
        <p>Date, category, amount, and a short description.</p>
        <div class="ex-field">
            <label>Date</label>
            <input type="date" name="spent_on" id="ex-date" required value="{{ date('Y-m-d') }}">
        </div>
        <div class="ex-field">
            <label>Category</label>
            <select class="ex-select ex-native" name="expense_category_id" id="ex-category" data-placeholder="Type to search a category">
                <option value="">Choose a category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}@if($category->budget_amount) — {{ $money($category->budget_amount) }}@endif</option>
                @endforeach
            </select>
            @if($categories->isEmpty())
                <p style="margin-top:6px;">Add a category and a budget first. <a href="{{ route('expense_categories.index') }}">Categories &amp; budgets</a></p>
            @endif
        </div>
        <div class="ex-field">
            <label>Amount (XAF)</label>
            <input type="number" name="amount" id="ex-amount" min="0" step="1" required>
        </div>
        <div class="ex-field">
            <label>Description</label>
            <textarea name="note" id="ex-note" required></textarea>
        </div>
        <div class="ex-dialog-actions">
            <button type="button" class="ex-btn quiet" id="ex-close">Cancel</button>
            <button type="submit" class="ex-btn gold" id="ex-save">Save expense</button>
        </div>
    </form>
</div>
@endif
<script>
    $("ul#expense").siblings('a').attr('aria-expanded','true');
    $("ul#expense").addClass("show");
    $("ul#expense #exp-list-menu").addClass("active");
    (function () {
        var shade = document.getElementById('ex-shade');
        var form = document.getElementById('ex-form');
        if (!shade || !form) return;
        var storeUrl = @json(route('expenses.store'));
        var updateBase = @json(url('expenses'));
        function openCreate() {
            form.action = storeUrl;
            document.getElementById('ex-method').value = 'POST';
            document.getElementById('ex-id').value = '';
            document.getElementById('ex-title').textContent = 'Create expense';
            document.getElementById('ex-save').textContent = 'Save expense';
            document.getElementById('ex-date').value = @json(date('Y-m-d'));
            document.getElementById('ex-category').value = '';
            if (document.getElementById('ex-category').exSync) document.getElementById('ex-category').exSync();
            document.getElementById('ex-amount').value = '';
            document.getElementById('ex-note').value = '';
            shade.className = 'ex-shade is-open';
        }
        function openEdit(button) {
            form.action = updateBase + '/' + button.getAttribute('data-id');
            document.getElementById('ex-method').value = 'PUT';
            document.getElementById('ex-id').value = button.getAttribute('data-id');
            document.getElementById('ex-title').textContent = 'Edit expense';
            document.getElementById('ex-save').textContent = 'Save changes';
            document.getElementById('ex-date').value = button.getAttribute('data-date');
            document.getElementById('ex-category').value = button.getAttribute('data-category');
            if (document.getElementById('ex-category').exSync) document.getElementById('ex-category').exSync();
            document.getElementById('ex-amount').value = button.getAttribute('data-amount');
            document.getElementById('ex-note').value = button.getAttribute('data-note');
            shade.className = 'ex-shade is-open';
        }
        var opener = document.getElementById('ex-open');
        if (opener) opener.addEventListener('click', openCreate);
        document.getElementById('ex-close').addEventListener('click', function () {
            shade.className = 'ex-shade';
        });
        shade.addEventListener('click', function (event) {
            if (event.target === shade) shade.className = 'ex-shade';
        });
        var edits = document.querySelectorAll('.ex-edit');
        for (var i = 0; i < edits.length; i++) {
            edits[i].addEventListener('click', function () { openEdit(this); });
        }
        form.addEventListener('submit', function (event) {
            var category = document.getElementById('ex-category');
            if (category && !category.value) {
                event.preventDefault();
                if (category.exFocus) category.exFocus();
            }
        });
    })();
    (function () {
        var selects = document.querySelectorAll('select.ex-select');
        for (var s = 0; s < selects.length; s++) {
            bindSearch(selects[s]);
        }
        function bindSearch(select) {
            var box = document.createElement('div');
            box.className = 'ex-combo' + (select.getAttribute('data-submit') ? ' ex-filter' : '');
            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'ex-combo-input';
            input.autocomplete = 'off';
            input.placeholder = select.getAttribute('data-placeholder') || 'Search';
            var list = document.createElement('div');
            list.className = 'ex-combo-list';
            list.hidden = true;
            select.parentNode.insertBefore(box, select);
            box.appendChild(input);
            box.appendChild(list);
            function labelFor(value) {
                for (var i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === String(value)) return select.options[i].text;
                }
                return '';
            }
            function paint(term) {
                var query = (term || '').toLowerCase();
                list.innerHTML = '';
                var shown = 0;
                for (var i = 0; i < select.options.length; i++) {
                    var option = select.options[i];
                    if (query && option.text.toLowerCase().indexOf(query) === -1) continue;
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'ex-combo-item';
                    button.textContent = option.text;
                    button.setAttribute('data-value', option.value);
                    list.appendChild(button);
                    shown++;
                }
                if (!shown) {
                    var empty = document.createElement('div');
                    empty.className = 'ex-combo-empty';
                    empty.textContent = 'No match';
                    list.appendChild(empty);
                }
                list.hidden = false;
            }
            function choose(value) {
                select.value = value;
                input.value = value ? labelFor(value) : '';
                list.hidden = true;
                if (select.getAttribute('data-submit') === '1' && select.form) select.form.submit();
            }
            input.addEventListener('focus', function () {
                paint(input.value === labelFor(select.value) ? '' : input.value);
            });
            input.addEventListener('input', function () {
                if (!input.value) select.value = '';
                paint(input.value);
            });
            list.addEventListener('click', function (event) {
                var button = event.target;
                if (!button.getAttribute) return;
                if (!button.getAttribute('data-value') && button.getAttribute('data-value') !== '') return;
                if (!button.className || button.className.indexOf('ex-combo-item') === -1) return;
                choose(button.getAttribute('data-value'));
            });
            document.addEventListener('click', function (event) {
                if (!box.contains(event.target)) list.hidden = true;
            });
            select.exSync = function () {
                input.value = select.value ? labelFor(select.value) : '';
                list.hidden = true;
            };
            select.exFocus = function () {
                input.focus();
                paint('');
            };
            select.exSync();
        }
    })();
</script>
@endsection
