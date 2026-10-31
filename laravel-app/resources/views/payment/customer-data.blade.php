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
                @foreach($sales as $key=>$sale)
                    @php
                        $settlement = isset($sale->settlement) ? $sale->settlement : null;
                        $due = $settlement ? $sale->settlement['due'] : max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
                    @endphp
                    <tr data-id="{{$sale->id}}">
                        <td>{{$key}}</td>
                        <td>{{ $sale->customer->name }}</td>
                        <td>{{ $sale->customer->unique_id }}</td>
                        <td>{{ $sale->reference_no }}</td>
                        <td>{{ $sale->customer->phone_number }}</td>
                        <td>{{ number_format($sale->grand_total, 2) }}</td>
                        <td>{{ number_format($sale->paid_amount, 2) ?? 0 }}</td>
                        <td>{{ number_format($due, 2) }}</td>
                        <td>
                            @if($settlement && !empty($settlement['undelivered']))
                                @foreach($settlement['undelivered'] as $openItem)
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
                            <span class="badge badge-warning">{{ $due <= 0.009 ? 'Paid' : ($sale->payment_status == 1 ? 'Pending' : ($sale->payment_status == 3 ? 'Partial' : 'Due')) }}</span>
                        </td>
                        <td>{{ $sale->created_at }}</td>
                        <td>
                            <button type="button" class="add-payment btn btn-sm btn-success" data-id="{{ $sale->id }}" data-name="{{ e(optional($sale->customer)->name) }}" data-phone="{{ e(optional($sale->customer)->phone_number) }}" data-ref="{{ e($sale->reference_no) }}" data-due="{{ $due }}" data-toggle="modal" data-target="#add-payment"><i class="fa fa-plus"></i>{{ trans('file.Add Payment') }}</button>
                            <button type="button" class="get-payment btn btn-info btn-sm" data-id = "{{ $sale->id }}"><i class="fa fa-money"></i>{{ trans('file.View') }}</button>
                            <a class="get-payment btn btn-warning btn-sm" href="{{ route('sales.edit',$sale->id ) }}"><i class="fa fa-pencil"></i> Edit Sale</a>
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
        #add-payment .modal-dialog { max-width: 460px; margin: 28px auto; }
        #add-payment .modal-content { border: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(12, 24, 48, .28); }
        #add-payment .pay-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 18px 18px 16px; background: #0c1830; color: #fff; }
        #add-payment .pay-kicker { color: #e1b368; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        #add-payment .pay-title { margin: 4px 0 0; font-size: 20px; font-weight: 700; }
        #add-payment .pay-ref { margin-top: 2px; color: rgba(255,255,255,.72); font-size: 13px; }
        #add-payment .pay-x { width: 36px; height: 36px; border: 0; border-radius: 18px; background: rgba(255,255,255,.1); color: #fff; font-size: 22px; line-height: 1; }
        #add-payment .pay-body { padding: 16px 18px 18px; background: #f3f6fb; }
        #add-payment .pay-due { margin-bottom: 12px; padding: 14px 16px; border-radius: 14px; background: #fff; }
        #add-payment .pay-due strong { display: block; margin-top: 2px; color: #0c1830; font-size: 28px; letter-spacing: -.03em; }
        #add-payment .pay-phone { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; padding: 12px 14px; border-radius: 14px; background: #fff; }
        #add-payment .pay-phone span, #add-payment label { color: #5c6b82; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        #add-payment .pay-phone strong { color: #0c1830; font-size: 16px; }
        #add-payment label { display: block; margin: 0 0 6px; }
        #add-payment .form-control { min-height: 46px; border: 1px solid #d7deea; border-radius: 12px; background: #fff; font-size: 16px; }
        #add-payment .pay-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; }
        #add-payment .pay-choice button { min-height: 52px; border: 1px solid #d7deea; border-radius: 14px; background: #fff; color: #0c1830; font-weight: 700; }
        #add-payment .pay-choice button small { display: block; color: #5c6b82; font-weight: 600; }
        #add-payment .pay-choice button.is-on { border-color: #0c1830; background: #0c1830; color: #fff; }
        #add-payment .pay-choice button.is-on small { color: #e1b368; }
        #add-payment .pay-amount { margin-bottom: 12px; }
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
                    {!! Form::open(['route' => 'sale.add-payment', 'method' => 'post', 'class' => 'payment-form' ]) !!}
                    <input type="hidden" name="balance">
                    <input type="hidden" name="paying_amount">
                    <input type="hidden" name="paid_by_id" value="1">
                    <input type="hidden" name="sale_id">
                    <input type="hidden" name="account_id" value="{{ optional($defaultPayAccount)->id }}">

                    <div class="pay-due">
                        <div class="pay-kicker" style="color:#8a6230">Still due</div>
                        <strong id="payDueLabel">0 XAF</strong>
                    </div>
                    <div class="pay-phone">
                        <span>Client number</span>
                        <strong id="payClientPhone">—</strong>
                    </div>
                    <div style="margin-bottom:12px">
                        <label for="payOtherPhone">Different number</label>
                        <input type="text" id="payOtherPhone" name="momo_phone" class="form-control" placeholder="Leave blank to use the client number" autocomplete="off">
                    </div>
                    <div class="pay-choice" id="payMethods">
                        <button type="button" class="pay-method is-on" data-id="1">Cash<small>Ask the client to confirm</small></button>
                        <button type="button" class="pay-method" data-id="8">MoMo / OM<small>Prompt on the phone</small></button>
                    </div>
                    <div class="pay-choice" id="payModes">
                        <button type="button" class="pay-mode is-on" data-mode="full">Full amount<small>Pay everything due</small></button>
                        <button type="button" class="pay-mode" data-mode="part">Partial<small>Pay part now</small></button>
                    </div>
                    <div class="pay-amount">
                        <label for="amount">Amount (XAF)</label>
                        <input type="number" id="amount" name="amount" class="form-control" min="1" step="1" required readonly>
                        <div class="pay-left">Left after this payment: <span id="payLeft">0 XAF</span></div>
                    </div>
                    <div class="pay-error" id="payError"></div>
                    <button type="submit" class="pay-send">Ask the client to confirm</button>
                    <p class="pay-hint">The bill stays unpaid until the client confirms.</p>
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
        function setPayMethod(id) {
            $('#add-payment input[name="paid_by_id"]').val(id);
            $('#add-payment .pay-method').removeClass('is-on');
            $('#add-payment .pay-method[data-id="' + id + '"]').addClass('is-on');
        }
        function setPayMode(mode) {
            $('#add-payment .pay-mode').removeClass('is-on');
            $('#add-payment .pay-mode[data-mode="' + mode + '"]').addClass('is-on');
            var input = $('#add-payment input[name="amount"]');
            if (mode === 'full') {
                input.val(Math.round(payDue)).prop('readonly', true);
            } else {
                input.prop('readonly', false);
                if (!input.val() || parseFloat(input.val()) >= payDue) {
                    input.val('');
                }
            }
            syncPayAmount();
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
        $('#add-payment form.payment-form').on('submit', function (event) {
            var amount = parseFloat($('#add-payment input[name="amount"]').val());
            var other = $.trim($('#add-payment input[name="momo_phone"]').val());
            var client = $.trim($('#payClientPhone').attr('data-phone') || '');
            var error = $('#payError');
            error.hide().text('');
            if (!(amount > 0) || amount > payDue + 0.009) {
                event.preventDefault();
                error.text('Enter an amount up to the amount still due.').show();
                return;
            }
            if (!other && !client) {
                event.preventDefault();
                error.text('Add a phone number so the client can confirm.').show();
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
            $('#payClientPhone').text(phone || 'No number on file').attr('data-phone', phone);
            $('#add-payment input[name="momo_phone"]').val('');
            $('#add-payment input[name="balance"]').val(payDue);
            $('#add-payment input[name="sale_id"]').val(button.data('id'));
            $('#payDueLabel').text(payMoney(payDue));
            $('#payError').hide().text('');
            setPayMethod(1);
            setPayMode('full');
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
                            var id = $(this.node()).data('id');
                            if (id) {
                                saleIds.push(id);
                            }
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
