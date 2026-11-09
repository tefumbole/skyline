@extends('layout.main')
@section('content')
@php
    $eventLabels = [];
    foreach ($events as $event) {
        $eventLabels[$event['id']] = $event['label'].' · '.$event['when'];
    }
    $quoteLabels = [];
    foreach ($quotations as $quote) {
        $quoteLabels[$quote['id']] = $quote['label'];
    }
    $money = function ($amount) {
        return number_format((float) $amount, 0).' XAF';
    };
@endphp
<style>
    .ex-page { max-width: 1080px; margin: 0 auto 48px; }
    .ex-hero { background: #0c1830; color: #fff; border-radius: 22px; padding: 22px 24px; display: flex; justify-content: space-between; gap: 16px; align-items: flex-end; flex-wrap: wrap; }
    .ex-kicker { color: #e1b368; font-size: 12px; letter-spacing: .14em; text-transform: uppercase; margin: 0 0 6px; }
    .ex-hero h1 { margin: 0; font-size: 32px; font-weight: 700; }
    .ex-hero p { margin: 8px 0 0; color: #d5deea; max-width: 560px; }
    .ex-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .ex-btn { display: inline-block; min-height: 44px; line-height: 44px; padding: 0 16px; border-radius: 12px; font-weight: 700; text-decoration: none; border: 0; cursor: pointer; }
    .ex-btn.gold { background: #e1b368; color: #0c1830; }
    .ex-btn.ghost { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.35); }
    .ex-budgets { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 16px; }
    .ex-budget { background: #fff; border-radius: 16px; padding: 14px 16px; box-shadow: 0 8px 24px rgba(12,24,48,.06); text-decoration: none; color: inherit; display: block; }
    .ex-budget strong { display: block; color: #0c1830; }
    .ex-budget span { color: #5c6b80; font-size: 13px; }
    .ex-bar { height: 8px; background: #e7edf5; border-radius: 99px; margin-top: 10px; overflow: hidden; }
    .ex-bar i { display: block; height: 100%; background: #0c1830; }
    .ex-bar.over i { background: #8a1f1f; }
    .ex-layout { display: grid; grid-template-columns: 340px 1fr; gap: 16px; margin-top: 16px; }
    .ex-card { background: #fff; border-radius: 18px; padding: 16px; box-shadow: 0 8px 24px rgba(12,24,48,.06); }
    .ex-card h2 { margin: 0 0 12px; font-size: 18px; color: #0c1830; }
    .ex-field { margin-bottom: 12px; }
    .ex-field label { display: block; font-size: 12px; font-weight: 700; color: #5c6b80; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
    .ex-field input, .ex-field select, .ex-field textarea { width: 100%; border: 1px solid #d5deea; border-radius: 12px; min-height: 44px; padding: 8px 12px; background: #f8fafc; color: #0c1830; }
    .ex-field textarea { min-height: 72px; }
    .ex-note { color: #5c6b80; font-size: 13px; margin: 0 0 12px; }
    .ex-pay, .ex-eng { border: 1px solid #e7edf5; border-radius: 12px; padding: 8px 10px; margin-bottom: 8px; background: #f8fafc; }
    .ex-pay label { font-weight: 600; color: #0c1830; margin: 0; text-transform: none; letter-spacing: 0; font-size: 14px; }
    .ex-eng b { color: #0c1830; }
    .ex-list { display: flex; flex-direction: column; gap: 12px; }
    .ex-item { background: #fff; border-radius: 18px; padding: 16px; box-shadow: 0 8px 24px rgba(12,24,48,.06); }
    .ex-item header { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
    .ex-item h3 { margin: 0; font-size: 18px; color: #0c1830; }
    .ex-meta { color: #5c6b80; font-size: 13px; margin-top: 4px; }
    .ex-amount { font-size: 20px; font-weight: 700; color: #0c1830; }
    .ex-chip { display: inline-block; margin-top: 8px; margin-right: 6px; background: #f3f6fb; color: #0c1830; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 700; }
    .ex-empty { color: #5c6b80; padding: 12px 0; }
    .ex-filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
    .ex-filters select { border: 1px solid #d5deea; border-radius: 12px; min-height: 40px; padding: 0 10px; background: #fff; }
    @media (max-width: 860px) { .ex-layout { grid-template-columns: 1fr; } }
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
            <p>Record a cost, give the category a budget, and optionally tie it to an event or a quotation. Engineer costs for that job show up on their own.</p>
        </div>
        <div class="ex-actions">
            <a class="ex-btn ghost" href="{{ route('expense_categories.index') }}">Categories &amp; budgets</a>
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

    <div class="ex-layout">
        @if($canAdd || $editing)
        <form class="ex-card" method="post" action="{{ $editing ? route('expenses.update', $editing->id) : route('expenses.store') }}">
            @csrf
            @if($editing)
                @method('PUT')
                <input type="hidden" name="expense_id" value="{{ $editing->id }}">
            @endif
            <h2>{{ $editing ? 'Update expense' : 'New expense' }}</h2>
            <p class="ex-note">Linking an event or a quotation is optional. Past and upcoming events are both listed.</p>
            <div class="ex-field">
                <label>Category</label>
                <select name="expense_category_id" required>
                    <option value="">Choose a category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $editing && (int) $editing->expense_category_id === (int) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ex-field">
                <label>Amount (XAF)</label>
                <input type="number" name="amount" min="0" step="1" required value="{{ $editing ? $editing->amount : '' }}">
            </div>
            <div class="ex-field">
                <label>Warehouse</label>
                <select name="warehouse_id" required>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $editing && (int) $editing->warehouse_id === (int) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ex-field">
                <label>Account</label>
                <select name="account_id">
                    <option value="">No account</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ $editing && (int) $editing->account_id === (int) $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ex-field">
                <label>Event, optional</label>
                <select name="event_id" id="ex-event">
                    <option value="">No event</option>
                    @foreach($events as $event)
                        <option value="{{ $event['id'] }}" {{ (string) ($editing ? $editing->event_id : request('event')) === (string) $event['id'] ? 'selected' : '' }}>{{ $event['when'] }} · {{ $event['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ex-field">
                <label>Quotation, optional</label>
                <select name="quotation_id" id="ex-quote">
                    <option value="">No quotation</option>
                    @foreach($quotations as $quote)
                        <option value="{{ $quote['id'] }}" {{ (int) ($editing ? $editing->quotation_id : request('quotation')) === (int) $quote['id'] ? 'selected' : '' }}>{{ $quote['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ex-field">
                <label>Note</label>
                <textarea name="note">{{ $editing ? $editing->note : '' }}</textarea>
            </div>
            <div id="ex-pay-box">
                <p class="ex-note">Payments for the event or quotation. Tick the ones this expense relates to.</p>
                <div id="ex-payments">
                    @forelse($formPayments as $payment)
                        <div class="ex-pay">
                            <label><input type="checkbox" name="payment_ids[]" value="{{ $payment['id'] }}" {{ in_array($payment['id'], $checkedPayments) ? 'checked' : '' }}> {{ $payment['label'] }}</label>
                        </div>
                    @empty
                        <p class="ex-empty" id="ex-pay-empty">Choose an event or a quotation to see its payments.</p>
                    @endforelse
                </div>
            </div>
            <div id="ex-eng-box">
                <p class="ex-note">Engineer expenses for this event or quotation.</p>
                <div id="ex-engineers">
                    @forelse($formEngineers as $engineer)
                        <div class="ex-eng"><b>{{ $engineer['name'] }}</b> · {{ $engineer['role'] }} · {{ $money($engineer['amount']) }} · {{ $engineer['status'] }}</div>
                    @empty
                        <p class="ex-empty" id="ex-eng-empty">Engineer costs appear here when the event or quotation has them.</p>
                    @endforelse
                </div>
            </div>
            <button class="ex-btn gold" type="submit">{{ $editing ? 'Save changes' : 'Save expense' }}</button>
            @if($editing)
                <a class="ex-btn" href="{{ route('expenses.index') }}" style="color:#0c1830;">Cancel</a>
            @endif
        </form>
        @endif

        <div>
            <form class="ex-filters" method="get" action="{{ route('expenses.index') }}">
                <select name="category" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="event" onchange="this.form.submit()">
                    <option value="">All events</option>
                    @foreach($events as $event)
                        <option value="{{ $event['id'] }}" {{ (string) request('event') === (string) $event['id'] ? 'selected' : '' }}>{{ $event['label'] }}</option>
                    @endforeach
                </select>
                <select name="quotation" onchange="this.form.submit()">
                    <option value="">All quotations</option>
                    @foreach($quotations as $quote)
                        <option value="{{ $quote['id'] }}" {{ (string) request('quotation') === (string) $quote['id'] ? 'selected' : '' }}>{{ $quote['label'] }}</option>
                    @endforeach
                </select>
            </form>
            <div class="ex-list">
                @forelse($expenses as $expense)
                    <article class="ex-item">
                        <header>
                            <div>
                                <h3>{{ $expense->expenseCategory ? $expense->expenseCategory->name : 'Expense' }}</h3>
                                <div class="ex-meta">{{ $expense->reference_no }} · {{ $expense->created_at ? $expense->created_at->format('j M Y H:i') : '' }}</div>
                            </div>
                            <div class="ex-amount">{{ $money($expense->amount) }}</div>
                        </header>
                        @if($expense->note)
                            <p class="ex-note" style="margin-top:8px;">{{ $expense->note }}</p>
                        @endif
                        @if($expense->event_id && isset($eventLabels[$expense->event_id]))
                            <span class="ex-chip">{{ $eventLabels[$expense->event_id] }}</span>
                        @endif
                        @if($expense->quotation_id && isset($quoteLabels[$expense->quotation_id]))
                            <span class="ex-chip">{{ $quoteLabels[$expense->quotation_id] }}</span>
                        @endif
                        @if(isset($linkMap[$expense->id]))
                            @foreach($linkMap[$expense->id] as $paymentId)
                                @if(isset($paymentLabels[$paymentId]))
                                    <span class="ex-chip">{{ $paymentLabels[$paymentId] }}</span>
                                @endif
                            @endforeach
                        @endif
                        @if(!empty($engineerMap[$expense->id]))
                            <div style="margin-top:10px;">
                                @foreach($engineerMap[$expense->id] as $engineer)
                                    <div class="ex-eng"><b>{{ $engineer['name'] }}</b> · {{ $engineer['role'] }} · {{ $money($engineer['amount']) }} · {{ $engineer['status'] }}</div>
                                @endforeach
                            </div>
                        @endif
                        @if($canEdit)
                            <div style="margin-top:10px;"><a href="{{ route('expenses.index', ['edit' => $expense->id]) }}">Edit</a></div>
                        @endif
                    </article>
                @empty
                    <div class="ex-card"><p class="ex-empty">No expenses yet.</p></div>
                @endforelse
            </div>
            <div style="margin-top:12px;">{{ $expenses->links() }}</div>
        </div>
    </div>
</section>
<script>
    $("ul#expense").siblings('a').attr('aria-expanded','true');
    $("ul#expense").addClass("show");
    $("ul#expense #exp-list-menu").addClass("active");
(function () {
    var eventSel = document.getElementById('ex-event');
    var quoteSel = document.getElementById('ex-quote');
    if (!eventSel || !quoteSel) return;
    var payBox = document.getElementById('ex-payments');
    var engBox = document.getElementById('ex-engineers');
    function load() {
        var params = 'event_id=' + encodeURIComponent(eventSel.value) + '&quotation_id=' + encodeURIComponent(quoteSel.value);
        fetch('{{ route('expenses.link-options') }}?' + params, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (res) { return res.json(); })
            .then(function (data) {
                payBox.innerHTML = '';
                engBox.innerHTML = '';
                if (!data.payments || !data.payments.length) {
                    payBox.innerHTML = '<p class="ex-empty">No payments recorded for this yet.</p>';
                } else {
                    data.payments.forEach(function (payment) {
                        var row = document.createElement('div');
                        row.className = 'ex-pay';
                        row.innerHTML = '<label><input type="checkbox" name="payment_ids[]" value="' + payment.id + '"> ' + payment.label + '</label>';
                        payBox.appendChild(row);
                    });
                }
                if (!data.engineers || !data.engineers.length) {
                    engBox.innerHTML = '<p class="ex-empty">No engineer expenses on this event or quotation.</p>';
                } else {
                    data.engineers.forEach(function (row) {
                        var line = document.createElement('div');
                        line.className = 'ex-eng';
                        line.innerHTML = '<b></b>';
                        line.querySelector('b').textContent = row.name;
                        line.appendChild(document.createTextNode(' · ' + row.role + ' · ' + Number(row.amount).toLocaleString() + ' XAF · ' + row.status));
                        engBox.appendChild(line);
                    });
                }
            });
    }
    eventSel.addEventListener('change', function () {
        if (eventSel.value) quoteSel.value = '';
        load();
    });
    quoteSel.addEventListener('change', function () {
        if (quoteSel.value) eventSel.value = '';
        load();
    });
})();
</script>
@endsection
