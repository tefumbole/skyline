@extends('layout.main')
@section('content')
@php
    $money = function ($amount) {
        return number_format((float) $amount, 0).' XAF';
    };
    $chart = app(\App\Support\ChartOfAccounts::class);
    $chart->ensure();
    $debitAccounts = \App\Account::where('is_active', 1)->where('account_no', 'like', '6%')->orderBy('account_no')->get();
    $creditAccounts = \App\Account::where('is_active', 1)->whereIn('account_no', array('521', '5211', '571'))->orderBy('account_no')->get();
    $debitIds = array();
    foreach ($debitAccounts as $account) {
        $debitIds[(string) $account->account_no] = (int) $account->id;
    }
    $cashAccountId = 0;
    foreach ($creditAccounts as $account) {
        if ((string) $account->account_no === '571') {
            $cashAccountId = (int) $account->id;
        }
    }
    $categoryChoices = $categories->map(function ($category) use ($stats, $chart, $debitIds) {
        $parent = ($category->parent_id && isset($stats[$category->parent_id])) ? $stats[$category->parent_id]['name'] : '';
        $debitCode = $chart->expenseCode($category->name, '') ?: '658';
        return [
            'id' => (int) $category->id,
            'name' => $category->name,
            'code' => (string) $category->code,
            'parent' => $parent,
            'debit' => isset($debitIds[$debitCode]) ? $debitIds[$debitCode] : 0,
            'debitCode' => $debitCode,
        ];
    })->values();
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
    .ex-combo { position: relative; }
    .ex-combo input[type="text"] { padding-right: 44px; }
    .ex-combo.is-open input[type="text"] { border-color: #e1b368; box-shadow: 0 0 0 3px rgba(225,179,104,.28); }
    .ex-combo-toggle {
        position: absolute; top: 4px; right: 4px; width: 40px; height: 40px;
        border: 0; background: transparent; border-radius: 10px; cursor: pointer;
    }
    .ex-combo-toggle span {
        display: block; width: 8px; height: 8px; margin: 12px auto 0;
        border-right: 2px solid #5c6b80; border-bottom: 2px solid #5c6b80;
        transform: rotate(45deg);
    }
    .ex-combo-list {
        position: absolute; z-index: 30; left: 0; right: 0; top: calc(100% + 6px);
        margin: 0; padding: 6px; list-style: none; background: #fff;
        border-radius: 14px; box-shadow: 0 16px 40px rgba(12,24,48,.16);
        max-height: 240px; overflow: auto; -webkit-overflow-scrolling: touch;
    }
    .ex-combo-list button {
        display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 10px;
        min-height: 48px; padding: 8px 12px; border: 0; border-radius: 10px;
        background: transparent; color: #0c1830; text-align: left; font-size: 16px; cursor: pointer;
    }
    .ex-combo-list button.is-on, .ex-combo-list button:hover { background: #f7f1e4; }
    .ex-combo-list strong { font-weight: 700; }
    .ex-combo-list small { display: block; color: #5c6b80; font-size: 12px; font-weight: 600; }
    .ex-combo-list em { font-style: normal; color: #8a6a32; font-size: 12px; font-weight: 700; letter-spacing: .04em; }
    .ex-combo-empty { color: #5c6b80; padding: 12px; font-size: 14px; }
    .ex-hint { margin: 6px 0 0; color: #9a3b3b; font-size: 13px; }
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
            <label for="ex-category-search">Category</label>
            <div class="ex-combo" id="ex-combo">
                <input type="hidden" name="expense_category_id" id="ex-category" value="">
                <input type="text" id="ex-category-search" autocomplete="off" placeholder="Search or choose a category" role="combobox" aria-expanded="false" aria-controls="ex-category-list" aria-autocomplete="list">
                <button type="button" class="ex-combo-toggle" id="ex-category-toggle" aria-label="Show categories"><span></span></button>
                <ul class="ex-combo-list" id="ex-category-list" role="listbox" hidden></ul>
            </div>
            <p class="ex-hint" id="ex-category-hint" hidden>Choose a category.</p>
            @if($categories->isEmpty())
                <p style="margin-top:6px;">Add a category first from Categories &amp; budgets.</p>
            @endif
        </div>
        <div class="ex-field">
            <label for="ex-debit">Debit account</label>
            <select name="account_id" id="ex-debit" required>
                @foreach($debitAccounts as $account)
                    <option value="{{ $account->id }}" {{ (string) $account->account_no === '658' ? 'selected' : '' }}>{{ $account->account_no }} · {{ $account->name }}</option>
                @endforeach
            </select>
            <p class="ex-meta" id="ex-debit-hint">Kitchen, restaurant, and bar use 601. Chill On The Hill uses 658.</p>
        </div>
        <div class="ex-field">
            <label for="ex-credit">Credit account</label>
            <select name="credit_account_id" id="ex-credit" required>
                @foreach($creditAccounts as $account)
                    <option value="{{ $account->id }}" {{ (string) $account->account_no === '571' ? 'selected' : '' }}>{{ $account->account_no }} · {{ $account->name }}</option>
                @endforeach
            </select>
            <p class="ex-meta">Cash on hand is the usual payment. Choose Mobile Money when the money left that wallet.</p>
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
                        data-note="{{ $expense->note }}"
                        data-debit="{{ $expense->account_id }}"
                        data-credit="{{ $expense->credit_account_id }}">Edit</button>
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
        var categories = @json($categoryChoices);
        var reset = document.getElementById('ex-reset');
        var combo = document.getElementById('ex-combo');
        var hidden = document.getElementById('ex-category');
        var search = document.getElementById('ex-category-search');
        var list = document.getElementById('ex-category-list');
        var hint = document.getElementById('ex-category-hint');
        var toggle = document.getElementById('ex-category-toggle');
        function esc(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }
        function labelFor(item) {
            return item.parent ? item.parent + ' / ' + item.name : item.name;
        }
        var debitSelect = document.getElementById('ex-debit');
        var creditSelect = document.getElementById('ex-credit');
        var cashAccountId = @json($cashAccountId);
        function choose(item) {
            hidden.value = String(item.id);
            search.value = labelFor(item);
            hint.hidden = true;
            if (debitSelect && item.debit) {
                debitSelect.value = String(item.debit);
            }
            closeList();
        }
        function clearCategory() {
            hidden.value = '';
            search.value = '';
            hint.hidden = true;
        }
        function matches(item, query) {
            var hay = (item.parent + ' ' + item.name + ' ' + item.code).toLowerCase();
            return hay.indexOf(query) !== -1;
        }
        function openList(query) {
            var q = (query || '').toLowerCase().trim();
            var html = '';
            var found = 0;
            for (var i = 0; i < categories.length; i++) {
                var item = categories[i];
                if (q && !matches(item, q)) continue;
                found++;
                var on = String(item.id) === hidden.value ? ' is-on' : '';
                html += '<li><button type="button" class="' + on.trim() + '" data-id="' + item.id + '"><span><strong>' + esc(item.name) + '</strong>' + (item.parent ? '<small>' + esc(item.parent) + '</small>' : '') + '</span><em>' + esc(item.code || '') + '</em></button></li>';
            }
            if (!found) {
                html = '<li class="ex-combo-empty">No category matches.</li>';
            }
            list.innerHTML = html;
            list.hidden = false;
            combo.className = 'ex-combo is-open';
            search.setAttribute('aria-expanded', 'true');
        }
        function closeList() {
            list.hidden = true;
            combo.className = 'ex-combo';
            search.setAttribute('aria-expanded', 'false');
        }
        function pickById(id) {
            for (var i = 0; i < categories.length; i++) {
                if (String(categories[i].id) === String(id)) {
                    choose(categories[i]);
                    return;
                }
            }
            clearCategory();
        }
        search.addEventListener('focus', function () { openList(search.value); });
        search.addEventListener('input', function () {
            hidden.value = '';
            hint.hidden = true;
            openList(search.value);
        });
        toggle.addEventListener('click', function () {
            if (list.hidden) {
                search.focus();
                openList('');
            } else {
                closeList();
            }
        });
        list.addEventListener('click', function (event) {
            var button = event.target;
            while (button && button !== list && button.tagName !== 'BUTTON') {
                button = button.parentNode;
            }
            if (!button || button === list) return;
            pickById(button.getAttribute('data-id'));
        });
        document.addEventListener('click', function (event) {
            if (!combo.contains(event.target)) closeList();
        });
        form.addEventListener('submit', function (event) {
            if (hidden.value) return;
            var q = search.value.toLowerCase().trim();
            var only = null;
            var count = 0;
            for (var i = 0; i < categories.length; i++) {
                if (q && matches(categories[i], q)) {
                    only = categories[i];
                    count++;
                }
            }
            if (count === 1) {
                choose(only);
                return;
            }
            event.preventDefault();
            hint.hidden = false;
            openList(search.value);
            search.focus();
        });
        function createMode() {
            form.action = storeUrl;
            document.getElementById('ex-method').value = 'POST';
            document.getElementById('ex-id').value = '';
            document.getElementById('ex-title').textContent = 'Create expense';
            document.getElementById('ex-save').textContent = 'Save expense';
            document.getElementById('ex-date').value = today;
            clearCategory();
            document.getElementById('ex-amount').value = '';
            document.getElementById('ex-note').value = '';
            if (creditSelect && cashAccountId) creditSelect.value = String(cashAccountId);
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
                pickById(this.getAttribute('data-category'));
                if (debitSelect && this.getAttribute('data-debit')) debitSelect.value = this.getAttribute('data-debit');
                if (creditSelect && this.getAttribute('data-credit')) creditSelect.value = this.getAttribute('data-credit');
                document.getElementById('ex-amount').value = this.getAttribute('data-amount');
                document.getElementById('ex-note').value = this.getAttribute('data-note') || '';
                reset.hidden = false;
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    })();
</script>
@endsection
