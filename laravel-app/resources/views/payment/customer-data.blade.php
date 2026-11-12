@extends('layout.main') @section('content')
    @if($errors->has('title'))
        <div class="alert alert-danger alert-dismissible text-center">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ $errors->first('title') }}</div>
    @endif
    @if($errors->has('image'))
        <div class="alert alert-danger alert-dismissible text-center">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ $errors->first('image') }}</div>
    @endif
    @if(session()->has('message'))
        <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
    @endif
    @if(session()->has('not_permitted'))
        <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
    @endif

    <section>
        @include('payout.partials.style')
        <p style="margin:0 0 12px"><a class="btn btn-default btn-sm" href="{{ route('payment.collected') }}">Collected payments</a></p>

        <div class="table-responsive">
            <table id="biller-table" class="table sale-list">
                <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Customer Name')}}</th>
                    <th>Unique ID</th>
                    <th>Order Id</th>
                    <th>{{trans('file.Phone Number')}}</th>
                    <th>{{trans('file.Amount')}}</th>
                    <th>{{trans('file.Paid Amount')}}</th>
                    <th>{{trans('file.Due')}}</th>
                    <th>Delivery</th>
                    <th>{{trans('file.Payment Status')}}</th>
                    <th>{{trans('file.Date')}}</th>
                    <th>{{trans('file.Add Payment')}}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($bills as $key=>$bill)
                    <tr data-id="{{ $bill['sale_ids'][0] }}" data-sale-ids="{{ implode(',', $bill['sale_ids']) }}">
                        <td>{{$key}}</td>
                        <td>{{ $bill['name'] }}</td>
                        <td>{{ $bill['unique_id'] }}</td>
                        <td>{{ $bill['orders'] > 1 ? $bill['orders'].' orders' : $bill['references'][0] }}</td>
                        <td>{{ $bill['phone'] }}</td>
                        <td>{{ number_format($bill['total'], 2) }}</td>
                        <td>{{ number_format($bill['paid'], 2) }}</td>
                        <td>{{ number_format($bill['due'], 2) }}</td>
                        <td>
                            @if(count($bill['undelivered']))
                                @foreach($bill['undelivered'] as $openItem)
                                    <div>
                                        <strong>{{ $openItem['name'] }}</strong> × {{ $openItem['qty'] }}
                                        <span class="text-muted">{{ number_format($openItem['amount'], 2) }}</span>
                                        <div class="text-muted">
                                            @if($openItem['minutes_left'] === null)
                                                Waiting to accept
                                            @elseif($openItem['minutes_left'] >= 0)
                                                {{ $openItem['minutes_left'] }} min left
                                            @else
                                                {{ abs($openItem['minutes_left']) }} min over
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-warning">{{ $bill['status'] }}</span>
                        </td>
                        <td>{{ $bill['latest_label'] }}</td>
                        <td>
                            @if($bill['due'] > 0.009)
                            <button type="button" class="add-payment btn btn-sm btn-success" data-id="{{ $bill['sale_ids'][0] }}" data-sale-ids="{{ implode(',', $bill['sale_ids']) }}" data-name="{{ e($bill['name']) }}" data-phone="{{ e($bill['phone']) }}" data-ref="{{ $bill['orders'] > 1 ? $bill['orders'].' orders' : e($bill['references'][0]) }}" data-due="{{ $bill['due'] }}" data-toggle="modal" data-target="#add-payment"><i class="fa fa-plus"></i>{{ trans('file.Add Payment') }}</button>
                            @endif
                            @if($bill['shared'])
                                <a class="btn btn-default btn-sm" target="_blank" rel="noopener" href="{{ route('sale.invoice', $bill['print_sale_id']) }}">Print</a>
                            @else
                                <a class="btn btn-default btn-sm" target="_blank" rel="noopener" href="{{ route('payment.client-bill', $bill['customer_id']) }}">Print</a>
                            @endif
                            @if($bill['orders'] === 1)
                            <button type="button" class="get-payment btn btn-info btn-sm" data-id="{{ $bill['print_sale_id'] }}"><i class="fa fa-money"></i>{{ trans('file.View') }}</button>
                            <a class="btn btn-warning btn-sm" href="{{ route('sales.edit', $bill['print_sale_id']) }}"><i class="fa fa-pencil"></i> Edit Sale</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div id="view-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{trans('file.All')}} {{trans('file.Payment')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <table class="table table-hover payment-list">
                        <thead>
                        <tr>
                            <th>{{trans('file.date')}}</th>
                            <th>{{trans('file.reference')}}</th>
                            <th>{{trans('file.Account')}}</th>
                            <th>{{trans('file.Amount')}}</th>
                            <th>{{trans('file.Paid By')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @php
        $defaultPayAccount = $lims_account_list->first(function ($account) { return $account->is_default; });
        if (! $defaultPayAccount) {
            $defaultPayAccount = $lims_account_list->first();
        }
    @endphp
    <style>
        #add-payment .modal-dialog { max-width: 460px; margin: 16px auto; }
        #add-payment .modal-content { display: flex; flex-direction: column; max-height: calc(100vh - 32px); border: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(12, 24, 48, .28); }
        #add-payment .pay-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 14px 16px 12px; background: #0c1830; color: #fff; }
        #add-payment .pay-kicker { color: #e1b368; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        #add-payment .pay-title { margin: 4px 0 0; font-size: 20px; font-weight: 700; }
        #add-payment .pay-ref { margin-top: 2px; color: rgba(255,255,255,.72); font-size: 13px; }
        #add-payment .pay-x { width: 36px; height: 36px; border: 0; border-radius: 18px; background: rgba(255,255,255,.1); color: #fff; font-size: 22px; line-height: 1; }
        #add-payment .pay-body { padding: 12px 16px 14px; background: #f3f6fb; overflow: auto; }
        #add-payment .pay-due { margin-bottom: 8px; padding: 10px 14px; border-radius: 14px; background: #fff; }
        #add-payment .pay-due strong { display: block; margin-top: 2px; color: #0c1830; font-size: 24px; letter-spacing: -.03em; }
        #add-payment .pay-phone { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 8px; padding: 10px 14px; border-radius: 14px; background: #fff; }
        #add-payment .pay-phone span, #add-payment label { color: #5c6b82; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        #add-payment .pay-phone strong { color: #0c1830; font-size: 16px; }
        #add-payment label { display: block; margin: 0 0 6px; }
        #add-payment .form-control { min-height: 46px; border: 1px solid #d7deea; border-radius: 12px; background: #fff; font-size: 16px; }
        #add-payment .pay-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }
        #add-payment .pay-choice button { min-height: 46px; border: 1px solid #d7deea; border-radius: 14px; background: #fff; color: #0c1830; font-weight: 700; }
        #add-payment .pay-choice button small { display: block; color: #5c6b82; font-weight: 600; }
        #add-payment .pay-choice button.is-on { border-color: #0c1830; background: #0c1830; color: #fff; }
        #add-payment .pay-choice button.is-on small { color: #e1b368; }
        #add-payment #paySplitBtn { width: 100%; min-height: 46px; border: 1px solid #d7deea; border-radius: 14px; background: #fff; color: #0c1830; font-weight: 700; }
        #add-payment #paySplitBtn small { display: block; color: #5c6b82; font-weight: 600; }
        #add-payment #paySplitBtn.is-on { border-color: #0c1830; background: #0c1830; color: #fff; }
        #add-payment #paySplitBtn.is-on small { color: #e1b368; }
        #add-payment .pay-amount { margin-bottom: 8px; }
        #add-payment .pay-split { margin-bottom: 8px; }
        #add-payment .pay-split-row { display: flex; gap: 8px; margin-bottom: 8px; }
        #add-payment .pay-split-row .split-phone { flex: 1; }
        #add-payment .pay-split-row .split-amount { width: 120px; }
        #add-payment .split-remove { border: 0; background: transparent; color: #8a1f1f; font-weight: 700; }
        #add-payment .pay-split-add { width: 100%; min-height: 42px; border: 1px dashed #d7deea; border-radius: 12px; background: #fff; color: #0c1830; font-weight: 700; }
        #add-payment .pay-left { margin-top: 6px; color: #5c6b82; font-size: 13px; }
        #add-payment .pay-error { display: none; margin-bottom: 10px; color: #9b1c1c; font-size: 13px; font-weight: 700; }
        #add-payment .pay-send { width: 100%; min-height: 48px; border: 0; border-radius: 14px; background: #e1b368; color: #0c1830; font-weight: 700; }
        #add-payment .pay-send:hover { background: #c99645; }
        #add-payment .pay-hint { margin: 8px 0 0; color: #5c6b82; font-size: 12px; text-align: center; }
    </style>
    <div id="add-payment" tabindex="-1" role="dialog" aria-labelledby="addPaymentTitle" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="pay-head">
                    <div>
                        <div class="pay-kicker">Add payment</div>
                        <h5 id="addPaymentTitle" class="pay-title">Client</h5>
                        <div class="pay-ref" id="payRef"></div>
                    </div>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="pay-x">&times;</button>
                </div>
                <div class="pay-body">
                    {!! Form::open(['route' => 'payment.client-pay', 'method' => 'post', 'class' => 'payment-form' ]) !!}
                    <input type="hidden" name="balance">
                    <input type="hidden" name="paying_amount">
                    <input type="hidden" name="paid_by_id" value="1">
                    <input type="hidden" name="sale_id">
                    <input type="hidden" name="sale_ids">
                    <input type="hidden" name="account_id" value="{{ optional($defaultPayAccount)->id }}">

                    <div class="pay-due">
                        <div class="pay-kicker" style="color:#8a6230">Still due</div>
                        <strong id="payDueLabel">0 XAF</strong>
                    </div>
                    <div style="margin-bottom:12px">
                        <label for="payPhone">Phone number</label>
                        <input type="text" id="payPhone" name="momo_phone" class="form-control" autocomplete="off" inputmode="tel">
                        <p id="payHolder" class="pay-hint" style="text-align:left;min-height:18px;"></p>
                    </div>
                    <div class="pay-choice" id="payMethods">
                        <button type="button" class="pay-method is-on" data-id="1">Cash<small>Submit</small></button>
                        <button type="button" class="pay-method" data-id="8">MoMo / OM<small>Wait for the phone</small></button>
                    </div>
                    <div class="pay-choice" id="payModes">
                        <button type="button" class="pay-mode is-on" data-mode="full">Full amount<small>Pay everything due</small></button>
                        <button type="button" class="pay-mode" data-mode="part">Partial<small>Pay part now</small></button>
                    </div>
                    <div id="paySplitToggle" hidden style="margin-bottom:8px">
                        <button type="button" class="pay-mode" data-mode="split" id="paySplitBtn">Split payment<small>Several Mobile Money numbers</small></button>
                    </div>
                    <div id="paySplitRows" class="pay-split" hidden>
                        <div id="paySplitList"></div>
                        <button type="button" id="paySplitAdd" class="pay-split-add">Add a number</button>
                        <div class="pay-left" id="paySplitSum"></div>
                    </div>
                    <div class="pay-amount">
                        <label for="amount">Amount (XAF)</label>
                        <input type="number" id="amount" name="amount" class="form-control" min="1" step="1" required readonly>
                        <div class="pay-left">Left after this payment: <span id="payLeft">0 XAF</span></div>
                    </div>
                    <div class="pay-error" id="payError"></div>
                    <button type="submit" class="pay-send">Submit</button>
                    <p class="pay-hint" id="payHint">This records the cash payment.</p>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript">

        $("ul#payments").siblings('a').attr('aria-expanded','true');
        $("ul#payments").addClass("show");
        $("ul#payments #payment-index-menu").addClass("active");

        var user_verified = <?php echo json_encode(true) ?>;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var payDue = 0;
        function payMoney(amount) {
            var value = Math.round(parseFloat(amount) || 0);
            return value.toLocaleString('fr-FR') + ' XAF';
        }
        function syncPayAmount() {
            var amount = parseFloat($('#add-payment input[name="amount"]').val());
            if (isNaN(amount) || amount < 0) amount = 0;
            $('#add-payment input[name="paying_amount"]').val(amount);
            var left = Math.max(0, payDue - amount);
            $('#payLeft').text(payMoney(left));
        }
        var payLookupTimer = null;
        function setPayMethod(id) {
            $('#add-payment input[name="paid_by_id"]').val(id);
            $('#add-payment .pay-method').removeClass('is-on');
            $('#add-payment .pay-method[data-id="' + id + '"]').addClass('is-on');
            var momo = String(id) === '8';
            $('#paySplitToggle').prop('hidden', !momo);
            if (!momo && $('#paySplitRows').is(':visible')) {
                setPayMode('full');
            }
            var split = momo && $('#paySplitRows').is(':visible');
            $('#add-payment .pay-send').text(momo ? 'Send prompt and wait' : 'Submit');
            $('#payHint').text(split
                ? 'Each person gets their own prompt. A share is recorded only after that phone approves it.'
                : (momo ? 'The bill is confirmed only after the client approves on their phone.' : 'This records the cash payment.'));
        }
        function lookupPayPhone() {
            var phone = $.trim($('#payPhone').val());
            var box = $('#payHolder');
            if (phone.replace(/\D/g, '').length < 9) {
                box.text('');
                return;
            }
            box.text('Checking the Mobile Money name…');
            $.get(@json(route('deposit.phone')), {phone: phone}).done(function (data) {
                if (data && data.ok && data.name) {
                    box.text(data.name + (data.operator ? ' · ' + data.operator : ''));
                    $('#addPaymentTitle').text(data.name);
                    if (data.phone) {
                        $('#payPhone').val(data.phone);
                    }
                } else {
                    box.text(data && data.error ? data.error : '');
                }
            }).fail(function () {
                box.text('');
            });
        }
        function splitRow() {
            return $('<div class="pay-split-row">' +
                '<input type="text" name="split_phones[]" class="form-control split-phone" placeholder="Phone" inputmode="tel" autocomplete="off">' +
                '<input type="number" name="split_amounts[]" class="form-control split-amount" min="100" step="1" placeholder="Amount">' +
                '<button type="button" class="split-remove">Remove</button>' +
                '</div>');
        }
        function syncSplit() {
            var sum = 0;
            var count = 0;
            $('#paySplitList .split-amount').each(function () {
                var value = parseFloat($(this).val());
                if (value > 0) {
                    sum += value;
                    count++;
                }
            });
            var left = Math.round(payDue) - Math.round(sum);
            $('#paySplitSum').text(count
                ? ('Shares ' + payMoney(sum) + ' · still to assign ' + payMoney(Math.max(0, left)))
                : 'Add each person\'s Mobile Money number and their share.');
            $('#add-payment input[name="amount"]').val(Math.round(sum));
            syncPayAmount();
        }
        function setPayMode(mode) {
            $('#add-payment .pay-mode').removeClass('is-on');
            $('#add-payment .pay-mode[data-mode="' + mode + '"]').addClass('is-on');
            var input = $('#add-payment input[name="amount"]');
            var split = mode === 'split';
            $('#paySplitRows').prop('hidden', !split);
            $('#payPhone').closest('div').prop('hidden', split);
            $('#add-payment .pay-amount').prop('hidden', split);
            $('#paySplitList input').prop('disabled', !split);
            if (split && $('#paySplitList .pay-split-row').length < 2) {
                $('#paySplitList').empty().append(splitRow()).append(splitRow());
                var knownPhone = $.trim($('#payPhone').val());
                if (knownPhone) {
                    $('#paySplitList .split-phone').first().val(knownPhone);
                }
            }
            if (mode === 'full') {
                input.val(Math.round(payDue)).prop('readonly', true);
            } else if (mode === 'part') {
                input.prop('readonly', false);
                if (!input.val() || parseFloat(input.val()) >= payDue) {
                    input.val('');
                }
            }
            if (split) {
                syncSplit();
                $('#payHint').text('Each person gets their own prompt. A share is recorded only after that phone approves it.');
            } else {
                syncPayAmount();
                var payingByPhone = String($('#add-payment input[name="paid_by_id"]').val()) === '8';
                $('#payHint').text(payingByPhone ? 'The bill is confirmed only after the client approves on their phone.' : 'This records the cash payment.');
            }
        }
        $('#add-payment').on('click', '.pay-method', function () {
            setPayMethod($(this).data('id'));
        });
        $('#add-payment').on('click', '.pay-mode', function () {
            setPayMode($(this).data('mode'));
            if ($(this).data('mode') === 'part') {
                $('#add-payment input[name="amount"]').focus();
            }
        });
        $('#add-payment input[name="amount"]').on('input', function () {
            var amount = parseFloat($(this).val());
            if (!isNaN(amount) && amount > payDue + 0.009) {
                $(this).val(Math.round(payDue));
            }
            syncPayAmount();
        });
        $('#paySplitAdd').on('click', function () {
            $('#paySplitList').append(splitRow());
            syncSplit();
        });
        $('#paySplitList').on('click', '.split-remove', function () {
            var rows = $('#paySplitList .pay-split-row');
            if (rows.length <= 2) {
                $(this).closest('.pay-split-row').find('input').val('');
            } else {
                $(this).closest('.pay-split-row').remove();
            }
            syncSplit();
        });
        $('#paySplitList').on('input', '.split-amount, .split-phone', syncSplit);
        $('#payPhone').on('input', function () {
            clearTimeout(payLookupTimer);
            payLookupTimer = setTimeout(lookupPayPhone, 450);
        });
        $('#add-payment form.payment-form').on('submit', function (event) {
            var split = $('#paySplitRows').is(':visible');
            var amount = parseFloat($('#add-payment input[name="amount"]').val());
            var phone = $.trim($('#payPhone').val());
            var momo = String($('#add-payment input[name="paid_by_id"]').val()) === '8';
            var error = $('#payError');
            error.hide().text('');
            if (split) {
                if (!momo) {
                    event.preventDefault();
                    error.text('Split payment is only for Mobile Money.').show();
                    return;
                }
                var shares = [];
                var seen = {};
                var bad = '';
                $('#paySplitList .pay-split-row').each(function () {
                    var rowPhone = $.trim($(this).find('.split-phone').val());
                    var rowAmount = parseFloat($(this).find('.split-amount').val());
                    if (!rowPhone && !(rowAmount > 0)) return;
                    var digits = rowPhone.replace(/\D/g, '');
                    if (digits.indexOf('237') === 0) digits = digits.slice(3);
                    if (digits.length !== 9) bad = 'Each share needs an MTN or Orange Mobile Money number.';
                    if (!(rowAmount >= 100)) bad = 'Each Mobile Money share must be at least 100 XAF.';
                    if (seen[digits]) bad = 'Use a different Mobile Money number for each person.';
                    seen[digits] = true;
                    shares.push(Math.round(rowAmount));
                });
                var sum = shares.reduce(function (total, value) { return total + value; }, 0);
                if (!bad && shares.length < 2) bad = 'Split payment needs at least two Mobile Money numbers.';
                if (!bad && sum !== Math.round(payDue)) bad = 'The shares must add up to the amount still due.';
                if (bad) {
                    event.preventDefault();
                    error.text(bad).show();
                    return;
                }
                $('#add-payment input[name="amount"]').val(sum);
                return;
            }
            if (!(amount > 0) || amount > payDue + 0.009) {
                event.preventDefault();
                error.text('Enter an amount up to the amount still due.').show();
                return;
            }
            if (momo && phone.replace(/\D/g, '').length < 9) {
                event.preventDefault();
                error.text('Add the Mobile Money number so the client can confirm.').show();
            }
        });

        $( "#select_all" ).on( "change", function() {
            if ($(this).is(':checked')) {
                $("tbody input[type='checkbox']").prop('checked', true);
            }
            else {
                $("tbody input[type='checkbox']").prop('checked', false);
            }
        });

        $(document).on("click", "table.sale-list tbody .add-payment", function() {
            var button = $(this);
            var phone = $.trim(button.attr('data-phone') || '');
            payDue = parseFloat(button.attr('data-due')) || 0;
            $('#addPaymentTitle').text(button.attr('data-name') || 'Client');
            $('#payRef').text(button.attr('data-ref') || '');
            $('#payPhone').val(phone);
            $('#payHolder').text('');
            $('#add-payment input[name="balance"]').val(payDue);
            $('#add-payment input[name="sale_id"]').val(button.data('id'));
            $('#add-payment input[name="sale_ids"]').val(button.attr('data-sale-ids') || button.data('id'));
            $('#payDueLabel').text(payMoney(payDue));
            $('#payError').hide().text('');
            $('#paySplitList').empty();
            setPayMethod(1);
            setPayMode('full');
            if (phone) {
                lookupPayPhone();
            }
        });

        $(document).on("click", "table.sale-list tbody .get-payment", function(event) {
            rowindex = $(this).closest('tr').index();
            deposit = $('table.sale-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.deposit').val();
            var id = $(this).data('id').toString();
            $.get('/sales/getpayment/' + id, function(data) {
                $(".payment-list tbody").remove();
                var newBody = $("<tbody>");
                payment_date  = data[0];
                payment_reference = data[1];
                paid_amount = data[2];
                paying_method = data[3];
                payment_id = data[4];
                payment_note = data[5];
                cheque_no = data[6];
                gift_card_id = data[7];
                change = data[8];
                paying_amount = data[9];
                account_name = data[10];
                account_id = data[11];

                $.each(payment_date, function(index){
                    var newRow = $("<tr>");
                    var cols = '';

                    cols += '<td>' + payment_date[index] + '</td>';
                    cols += '<td>' + payment_reference[index] + '</td>';
                    cols += '<td>' + account_name[index] + '</td>';
                    cols += '<td>' + paid_amount[index] + '</td>';
                    cols += '<td>' + paying_method[index] + '</td>';
                    newRow.append(cols);
                    newBody.append(newRow);
                    $("table.payment-list").append(newBody);
                });
                $('#view-payment').modal('show');
            });
        });

        $(document).ready(function() {
            $(document).on('click', '.open-EditbrandDialog', function() {
                var url = "/reason/"
                var id = $(this).data('id').toString();
                url = url.concat(id).concat("/edit");

                $.get(url, function(data) {
                    $("input[name='reason']").val(data['reason']);
                    $("input[name='reason_id']").val(data['id']);

                });
            });
        });

        $('#biller-table').DataTable( {
            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
                "info":      '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                "search":  '{{trans("file.Search")}}',
                'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
                }
            },
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [0, 1, 3]
                },
                {
                    'render': function(data, type, row, meta){
                        if(type === 'display'){
                            data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                        }

                        return data;
                    },
                    'checkboxes': {
                        'selectRow': true,
                        'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                    },
                    'targets': [0]
                }
            ],
            'select': { style: 'multi',  selector: 'td:first-child'},
            'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                    customize: function(doc) {
                        for (var i = 1; i < doc.content[1].table.body.length; i++) {
                            if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                                var imagehtml = doc.content[1].table.body[i][0].text;
                                var regex = /<img.*?src=['"](.*?)['"]/;
                                var src = regex.exec(imagehtml)[1];
                                var tempImage = new Image();
                                tempImage.src = src;
                                var canvas = document.createElement("canvas");
                                canvas.width = tempImage.width;
                                canvas.height = tempImage.height;
                                var ctx = canvas.getContext("2d");
                                ctx.drawImage(tempImage, 0, 0);
                                var imagedata = canvas.toDataURL("image/png");
                                delete doc.content[1].table.body[i][0].text;
                                doc.content[1].table.body[i][0].image = imagedata;
                                doc.content[1].table.body[i][0].fit = [30, 30];
                            }
                        }
                    },
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        format: {
                            body: function ( data, row, column, node ) {
                                if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                    var regex = /<img.*?src=['"](.*?)['"]/;
                                    data = regex.exec(data)[1];
                                }
                                return data;
                            }
                        }
                    },
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="fa fa-print"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                },
                {
                    text: '<i title="delete" class="dripicons-cross"></i> Delete selected',
                    className: 'buttons-delete',
                    action: function ( e, dt, node, config ) {
                        var saleIds = [];
                        dt.rows({ selected: true }).every(function () {
                            var raw = $(this.node()).attr('data-sale-ids') || $(this.node()).attr('data-id') || '';
                            raw.split(',').forEach(function (part) {
                                var id = parseInt(part, 10);
                                if (id) saleIds.push(id);
                            });
                        });
                        if (!saleIds.length) {
                            alert('Select at least one payment.');
                            return;
                        }
                        if (!confirm('Delete the selected payments?')) {
                            return;
                        }
                        $.ajax({
                            type: 'POST',
                            url: '{{ url('sales/deletebyselection') }}',
                            data: { saleIdArray: saleIds },
                            success: function () {
                                window.location.reload();
                            },
                            error: function () {
                                alert('Could not delete the selected payments.');
                            }
                        });
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i title="column visibility" class="fa fa-eye"></i>',
                    columns: ':gt(0)'
                },
            ],
        } );

    </script>
@endsection
