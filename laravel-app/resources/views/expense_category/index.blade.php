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
    .ex-field { margin-bottom: 12px; position: relative; }
    .ex-field label { display: block; font-size: 12px; font-weight: 700; color: #5c6b80; margin-bottom: 4px; text-transform: uppercase; }
    .ex-field input { width: 100%; border: 1px solid #d5deea; border-radius: 12px; min-height: 44px; padding: 8px 12px; background: #fff; color: #0c1830; }
    .ex-btn { display: inline-block; min-height: 44px; line-height: 44px; padding: 0 16px; border-radius: 12px; font-weight: 700; border: 0; background: #e1b368; color: #0c1830; cursor: pointer; text-decoration: none; }
    .ex-grid { display: grid; gap: 12px; }
    .ex-cat { background: #fff; border-radius: 18px; padding: 16px; box-shadow: 0 8px 24px rgba(12,24,48,.06); }
    .ex-cat.is-over { border: 1px solid #8a1f1f; background: #fff5f5; }
    .ex-cat h3 { margin: 0; color: #0c1830; }
    .ex-meta { color: #5c6b80; font-size: 13px; }
    .ex-warn { color: #8a1f1f; font-weight: 700; margin-top: 8px; }
    .ex-bar { height: 8px; background: #e7edf5; border-radius: 99px; margin: 10px 0; overflow: hidden; }
    .ex-bar i { display: block; height: 100%; background: #0c1830; }
    .ex-bar.over i { background: #8a1f1f; }
    .ex-native { position: absolute; left: -9999px; width: 1px; height: 1px; }
    .ex-combo { position: relative; }
    .ex-combo-input { width: 100%; border: 1px solid #d5deea; border-radius: 10px; height: 44px; padding: 0 12px; background: #fff; color: #0c1830; }
    .ex-combo-list { position: absolute; left: 0; right: 0; top: 48px; z-index: 30; background: #fff; border: 1px solid #d5deea; border-radius: 12px; max-height: 200px; overflow: auto; box-shadow: 0 12px 30px rgba(12,24,48,.12); }
    .ex-combo-item, .ex-combo-empty { display: block; width: 100%; text-align: left; border: 0; background: #fff; color: #0c1830; padding: 10px 12px; cursor: pointer; }
    .ex-combo-item:hover { background: #f3f6fb; }
    .ex-combo-empty { color: #5c6b80; cursor: default; }
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
        <p>Give a category a parent, the same way product categories work, and an optional time frame such as one month or two months. <a href="{{ route('expenses.index') }}" style="color:#e1b368;">Back to expenses</a></p>
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
                <label>Parent category</label>
                <select class="ex-select ex-native" name="parent_id" data-placeholder="Search a parent, or leave empty">
                    <option value="">No parent</option>
                    @foreach($lims_expense_category_all as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ex-field">
                <label>Budget (XAF)</label>
                <input type="number" name="budget_amount" min="0" step="1" placeholder="Optional fixed budget">
            </div>
            <div class="ex-field">
                <label>Time frame</label>
                <select class="ex-select ex-native" name="budget_months" data-placeholder="Optional time frame">
                    <option value="">No time frame</option>
                    @foreach($frames as $months => $label)
                        <option value="{{ $months }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="ex-btn" type="submit">Save category</button>
        </form>
        <div class="ex-grid">
            @foreach($lims_expense_category_all as $category)
                @php $row = isset($stats[$category->id]) ? $stats[$category->id] : null; @endphp
                <article class="ex-cat {{ $row && $row['over'] ? 'is-over' : '' }}" style="margin-left: {{ min(3, (int) $category->tree_depth) * 18 }}px;">
                    <h3>{{ $category->name }}</h3>
                    <div class="ex-meta">Code {{ $category->code }}@if($row && $row['parent_name']) · under {{ $row['parent_name'] }}@endif</div>
                    @if($row && $row['budget'] !== null && $row['budget'] > 0)
                        <div class="ex-meta" style="margin-top:6px;">
                            {{ $money($row['used']) }} of {{ $money($row['budget']) }}
                            @if($row['frame']) · {{ $row['frame'] }}@endif
                        </div>
                        <div class="ex-meta">{{ $row['percent'] }}% used · {{ $row['remaining_percent'] }}% remaining</div>
                        <div class="ex-bar {{ $row['over'] ? 'over' : '' }}"><i style="width: {{ min(100, (int) $row['percent']) }}%"></i></div>
                        @if($row['over'])
                            <div class="ex-warn">Budget exceeded. Over by {{ $money(abs($row['remaining'])) }}.</div>
                        @else
                            <div class="ex-meta">{{ $money($row['remaining']) }} left</div>
                        @endif
                        <a href="{{ route('expenses.index', ['category' => $category->id]) }}">See expenses</a>
                    @else
                        <div class="ex-meta" style="margin-top:6px;">{{ $money($row ? $row['used'] : 0) }} spent · no fixed budget yet</div>
                    @endif
                    <form method="post" action="{{ route('expense_categories.update', $category->id) }}" style="margin-top:12px;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="expense_category_id" value="{{ $category->id }}">
                        <input type="hidden" name="code" value="{{ $category->code }}">
                        <div class="ex-field">
                            <label>Name</label>
                            <input type="text" name="name" value="{{ $category->name }}" required>
                        </div>
                        <div class="ex-field">
                            <label>Parent category</label>
                            <select class="ex-select ex-native" name="parent_id" data-placeholder="Search a parent">
                                <option value="">No parent</option>
                                @foreach($lims_expense_category_all as $parent)
                                    @if((int) $parent->id === (int) $category->id) @continue @endif
                                    <option value="{{ $parent->id }}" {{ (int) $category->parent_id === (int) $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ex-field">
                            <label>Budget (XAF)</label>
                            <input type="number" name="budget_amount" min="0" step="1" value="{{ $row && $row['budget'] !== null ? $row['budget'] : '' }}" placeholder="Budget">
                        </div>
                        <div class="ex-field">
                            <label>Time frame</label>
                            <select class="ex-select ex-native" name="budget_months" data-placeholder="Optional time frame">
                                <option value="">No time frame</option>
                                @foreach($frames as $months => $label)
                                    <option value="{{ $months }}" {{ (int) $category->budget_months === (int) $months ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
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
    (function () {
        var selects = document.querySelectorAll('select.ex-select');
        for (var s = 0; s < selects.length; s++) bindSearch(selects[s]);
        function bindSearch(select) {
            var box = document.createElement('div');
            box.className = 'ex-combo';
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
            input.addEventListener('focus', function () {
                paint(input.value === labelFor(select.value) ? '' : input.value);
            });
            input.addEventListener('input', function () {
                if (!input.value) select.value = '';
                paint(input.value);
            });
            list.addEventListener('click', function (event) {
                var button = event.target;
                if (!button.className || button.className.indexOf('ex-combo-item') === -1) return;
                select.value = button.getAttribute('data-value');
                input.value = select.value ? labelFor(select.value) : '';
                list.hidden = true;
            });
            document.addEventListener('click', function (event) {
                if (!box.contains(event.target)) list.hidden = true;
            });
            input.value = select.value ? labelFor(select.value) : '';
        }
    })();
</script>
@endsection
