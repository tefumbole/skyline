@php
    $defaultPayAccount = $accounts->first(function ($account) { return $account->is_default; });
    if (! $defaultPayAccount) {
        $defaultPayAccount = $accounts->first();
    }
@endphp
<style>
    #add-payment .modal-dialog { max-width: 460px; margin: 16px auto; }
    #add-payment .modal-content { display: flex; flex-direction: column; max-height: calc(100vh - 32px); border: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(12, 24, 48, .28); }
    #add-payment .pay-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 14px 16px 12px; background: #0c1830; color: #fff; }
    #add-payment .pay-kicker { color: #e1b368; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
    #add-payment .pay-title { margin: 4px 0 0; font-size: 20px; font-weight: 700; }
    #add-payment .pay-ref { margin-top: 2px; color: rgba(255,255,255,.72); font-size: 13px; }
    #add-payment .pay-x { width: 44px; height: 44px; border: 0; border-radius: 22px; background: rgba(255,255,255,.1); color: #fff; font-size: 22px; line-height: 1; }
    #add-payment .pay-body { padding: 12px 16px 14px; background: #f3f6fb; overflow: auto; }
    #add-payment .pay-due { margin-bottom: 8px; padding: 10px 14px; border-radius: 14px; background: #fff; }
    #add-payment .pay-due strong { display: block; margin-top: 2px; color: #0c1830; font-size: 24px; letter-spacing: -.03em; }
    #add-payment label { display: block; margin: 0 0 6px; color: #5c6b82; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    #add-payment .form-control { min-height: 48px; border: 1px solid #d7deea; border-radius: 12px; background: #fff; font-size: 16px; }
    #add-payment .pay-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }
    #add-payment .pay-choice button { min-height: 48px; border: 1px solid #d7deea; border-radius: 14px; background: #fff; color: #0c1830; font-weight: 700; }
    #add-payment .pay-choice button small { display: block; color: #5c6b82; font-weight: 600; }
    #add-payment .pay-choice button.is-on { border-color: #0c1830; background: #0c1830; color: #fff; }
    #add-payment .pay-choice button.is-on small { color: #e1b368; }
    #add-payment .pay-left, #add-payment .pay-hint { margin-top: 6px; color: #5c6b82; font-size: 13px; }
    #add-payment .pay-error { display: none; margin-bottom: 10px; color: #9b1c1c; font-size: 13px; font-weight: 700; }
    #add-payment .pay-send { width: 100%; min-height: 48px; border: 0; border-radius: 14px; background: #e1b368; color: #0c1830; font-weight: 700; }
    @media (max-width: 480px) {
        #add-payment .pay-choice { grid-template-columns: 1fr; }
        #add-payment .modal-dialog { margin: 8px; }
    }
</style>
<div id="add-payment" tabindex="-1" role="dialog" aria-labelledby="addPaymentTitle" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="pay-head">
                <div>
                    <div class="pay-kicker">Add payment</div>
                    <h5 id="addPaymentTitle" class="pay-title">Table</h5>
                    <div class="pay-ref" id="payRef"></div>
                </div>
                <button type="button" data-dismiss="modal" aria-label="Close" class="pay-x">&times;</button>
            </div>
            <div class="pay-body">
                {!! Form::open(['route' => 'sale.add-payment', 'method' => 'post', 'class' => 'payment-form']) !!}
                <input type="hidden" name="balance">
                <input type="hidden" name="paying_amount">
                <input type="hidden" name="paid_by_id" value="1">
                <input type="hidden" name="sale_id">
                <input type="hidden" name="account_id" value="{{ optional($defaultPayAccount)->id }}">
                <input type="hidden" name="redirect_to" value="{{ '/tables/'.$table['id'] }}">
                <div class="pay-due">
                    <div class="pay-kicker" style="color:#8a6230">Still due</div>
                    <strong id="payDueLabel">0 XAF</strong>
                </div>
                <div style="margin-bottom:12px">
                    <label for="payPhone">Phone number</label>
                    <input type="text" id="payPhone" name="momo_phone" class="form-control" autocomplete="off" inputmode="tel">
                    <p id="payHolder" class="pay-hint" style="min-height:18px;"></p>
                </div>
                <div class="pay-choice" id="payMethods">
                    <button type="button" class="pay-method is-on" data-id="1">Cash<small>Submit</small></button>
                    <button type="button" class="pay-method" data-id="8">MoMo / OM<small>Ask the client to confirm</small></button>
                </div>
                <div class="pay-choice" id="payModes">
                    <button type="button" class="pay-mode is-on" data-mode="full">Full amount<small>Pay everything due</small></button>
                    <button type="button" class="pay-mode" data-mode="part">Partial<small>Pay part now</small></button>
                </div>
                <div>
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
<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    var payDue = 0;
    var payLookupTimer = null;
    function payMoney(amount) {
        var value = Math.round(parseFloat(amount) || 0);
        return value.toLocaleString('fr-FR') + ' XAF';
    }
    function syncPayAmount() {
        var amount = parseFloat($('#add-payment input[name="amount"]').val());
        if (isNaN(amount) || amount < 0) amount = 0;
        $('#add-payment input[name="paying_amount"]').val(amount);
        $('#payLeft').text(payMoney(Math.max(0, payDue - amount)));
    }
    function setPayMethod(id) {
        $('#add-payment input[name="paid_by_id"]').val(id);
        $('#add-payment .pay-method').removeClass('is-on');
        $('#add-payment .pay-method[data-id="' + id + '"]').addClass('is-on');
        var momo = String(id) === '8';
        $('#add-payment .pay-send').text(momo ? 'Ask the client to confirm' : 'Submit');
        $('#payHint').text(momo ? 'The bill stays unpaid until the client confirms.' : 'This records the cash payment.');
    }
    function setPayMode(mode) {
        $('#add-payment .pay-mode').removeClass('is-on');
        $('#add-payment .pay-mode[data-mode="' + mode + '"]').addClass('is-on');
        var input = $('#add-payment input[name="amount"]');
        if (mode === 'full') {
            input.val(Math.round(payDue)).prop('readonly', true);
        } else {
            input.prop('readonly', false);
            if (!input.val() || parseFloat(input.val()) >= payDue) input.val('');
        }
        syncPayAmount();
    }
    function lookupPayPhone() {
        var phone = $.trim($('#payPhone').val());
        var box = $('#payHolder');
        if (phone.replace(/\D/g, '').length < 9) { box.text(''); return; }
        box.text('Checking the Mobile Money name…');
        $.get(@json(route('deposit.phone')), {phone: phone}).done(function (data) {
            if (data && data.ok && data.name) {
                box.text(data.name + (data.operator ? ' · ' + data.operator : ''));
                if (data.phone) $('#payPhone').val(data.phone);
            } else {
                box.text(data && data.error ? data.error : '');
            }
        }).fail(function () { box.text(''); });
    }
    $('#add-payment').on('click', '.pay-method', function () { setPayMethod($(this).data('id')); });
    $('#add-payment').on('click', '.pay-mode', function () {
        setPayMode($(this).data('mode'));
        if ($(this).data('mode') === 'part') $('#add-payment input[name="amount"]').focus();
    });
    $('#add-payment input[name="amount"]').on('input', function () {
        var amount = parseFloat($(this).val());
        if (!isNaN(amount) && amount > payDue + 0.009) $(this).val(Math.round(payDue));
        syncPayAmount();
    });
    $('#payPhone').on('input', function () {
        clearTimeout(payLookupTimer);
        payLookupTimer = setTimeout(lookupPayPhone, 450);
    });
    $('#add-payment form.payment-form').on('submit', function (event) {
        var amount = parseFloat($('#add-payment input[name="amount"]').val());
        var phone = $.trim($('#payPhone').val());
        var momo = String($('#add-payment input[name="paid_by_id"]').val()) === '8';
        var error = $('#payError');
        error.hide().text('');
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
    $(document).on('click', '.add-payment', function () {
        var button = $(this);
        var phone = $.trim(button.attr('data-phone') || '');
        payDue = parseFloat(button.attr('data-due')) || 0;
        $('#addPaymentTitle').text(button.attr('data-name') || 'Table');
        $('#payRef').text(button.attr('data-ref') || '');
        $('#payPhone').val(phone);
        $('#payHolder').text('');
        $('#add-payment input[name="balance"]').val(payDue);
        $('#add-payment input[name="sale_id"]').val(button.data('id'));
        $('#payDueLabel').text(payMoney(payDue));
        $('#payError').hide().text('');
        setPayMethod(1);
        setPayMode('full');
        if (phone) lookupPayPhone();
    });
</script>
