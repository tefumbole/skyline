@extends('layout.main') @section('content')

@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif

@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('file.POS Setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'setting.posStore', 'method' => 'post']) !!}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{trans('file.Default Customer')}} *</label>
                                        @if($lims_pos_setting_data)
                                        <input type="hidden" name="customer_id_hidden" value="{{$lims_pos_setting_data->customer_id}}">
                                        @endif
                                        <select required name="customer_id" id="customer_id" class="selectpicker form-control" data-live-search="true"   title="Select customer...">
                                            @foreach($lims_customer_list as $customer)
                                            <option value="{{$customer->id}}">{{$customer->name . ' (' . $customer->phone_number . ')'}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>{{trans('file.Default Biller')}} *</label>
                                        @if($lims_pos_setting_data)
                                        <input type="hidden" name="biller_id_hidden" value="{{$lims_pos_setting_data->biller_id}}">
                                        @endif
                                        <select required name="biller_id" class="selectpicker form-control" data-live-search="true"   title="Select Biller...">
                                            @foreach($lims_biller_list as $biller)
                                            <option value="{{$biller->id}}">{{$biller->name . ' (' . $biller->company_name . ')'}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>{{trans('file.Default Credit Account')}} *</label>
                                        <select required name="account_id" class="selectpicker form-control" data-live-search="true"   title="Select Biller...">
                                            @foreach($lims_account_all as $account)
                                                <option value="{{$account->id}}" {{$account->id == optional($lims_account_default)->id ? 'selected' : '' }}>{{$account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Stripe Publishable key</label>
                                        <input type="text" name="stripe_public_key" class="form-control" value="@if($lims_pos_setting_data){{$lims_pos_setting_data->stripe_public_key}}@endif" required />
                                    </div>
                                    <div class="form-group">
                                        <label>Paypal Pro API Username</label>
                                        <input type="text" name="paypal_username" class="form-control" value="{{env('PAYPAL_SANDBOX_API_USERNAME')}}" />
                                    </div>
                                    <div class="form-group">
                                        <label>Paypal Pro API Signature</label>
                                        <input type="text" name="paypal_signature" class="form-control" value="{{env('PAYPAL_SANDBOX_API_SECRET')}}" />
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{trans('file.Default Warehouse')}} *</label>
                                        @if($lims_pos_setting_data)
                                        <input type="hidden" name="warehouse_id_hidden" value="{{$lims_pos_setting_data->warehouse_id}}">
                                        @endif
                                        <select required name="warehouse_id" class="selectpicker form-control" data-live-search="true"   title="Select warehouse...">
                                            @foreach($lims_warehouse_list as $warehouse)
                                            <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>{{trans('file.Displayed Number of Product Row')}} *</label>
                                        <input type="number" name="product_number" class="form-control" value="@if($lims_pos_setting_data){{$lims_pos_setting_data->product_number}}@endif" required />
                                    </div>
                                    <div class="form-group">
                                        <label>{{trans('file.Default Debit Account')}} *</label>
                                        <select required name="debit_account_id" class="selectpicker form-control" data-live-search="true"   title="Select Biller...">
                                            @foreach($lims_account_all as $account)
                                                <option value="{{$account->id}}" {{$account->id == optional($lims_account_default_debit)->id ? 'selected' : '' }}>{{$account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Stripe Secret key *</label>
                                        <input type="text" name="stripe_secret_key" class="form-control" value="@if($lims_pos_setting_data){{$lims_pos_setting_data->stripe_secret_key}}@endif"required />
                                    </div>
                                    <div class="form-group">
                                        <label>Paypal Pro API Password</label>
                                        <input type="password" name="paypal_password" class="form-control" value="{{env('PAYPAL_SANDBOX_API_PASSWORD')}}" />
                                    </div>
                                    <div class="form-group">
                                        @if($lims_pos_setting_data && $lims_pos_setting_data->keybord_active)
                                        <input class="mt-2" type="checkbox" name="keybord_active" value="1" checked>
                                        @else
                                        <input class="mt-2" type="checkbox" name="keybord_active" value="1">
                                        @endif
                                        <label class="mt-2"><strong>{{trans('file.Touchscreen keybord')}}</label>
                                    </div>
                                    <div class="form-group">
                                        <input class="mt-2" type="checkbox" name="sunmi_print_enabled" value="1" {{ $lims_pos_setting_data && (int) $lims_pos_setting_data->sunmi_print_enabled === 1 ? 'checked' : '' }}>
                                        <label class="mt-2"><strong>SUNMI printing</strong></label>
                                        <p class="mb-1"><small>Off uses the browser print dialog. On sends the invoice to the SUNMI web print service on this terminal. It does not detect the device by itself.</small></p>
                                        <label>Paper width</label>
                                        @php $sunmiPaper = (int) optional($lims_pos_setting_data)->sunmi_paper_mm === 58 ? 58 : 80; @endphp
                                        <select name="sunmi_paper_mm" id="sunmi-paper-mm" class="form-control">
                                            <option value="80" {{ $sunmiPaper === 80 ? 'selected' : '' }}>80 mm</option>
                                            <option value="58" {{ $sunmiPaper === 58 ? 'selected' : '' }}>58 mm</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        {!! Form::close() !!}
                        <div class="mt-3">
                            <button type="button" class="btn btn-default" id="sunmi-test-btn">Test SUNMI Printer</button>
                            <p id="sunmi-print-status" class="mt-2 mb-1"></p>
                            <button type="button" class="btn btn-default" id="sunmi-reprint" style="display:none;">Reprint</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="{{ asset('public/vendor/sunmi/bundle.umd.js') }}"></script>
<script src="{{ asset('public/js/sunmi-receipt.js') }}"></script>

<script type="text/javascript">

    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #pos-setting-menu").addClass("active");



    $('select[name="customer_id"]').val($("input[name='customer_id_hidden']").val());
    $('select[name="biller_id"]').val($("input[name='biller_id_hidden']").val());
    $('select[name="warehouse_id"]').val($("input[name='warehouse_id_hidden']").val());
    $('.selectpicker').selectpicker('refresh');

    var sunmiBusy = false;
    function sunmiHooks() {
        return {
            onBusy: function (isBusy) {
                sunmiBusy = isBusy;
                $('#sunmi-test-btn, #sunmi-reprint').prop('disabled', isBusy);
                if (isBusy) {
                    $('#sunmi-reprint').hide();
                }
            },
            onStatus: function (message, tone) {
                $('#sunmi-print-status').text(message).css('color', tone === 'error' ? '#a32020' : '#1a2744');
                $('#sunmi-reprint').toggle(tone === 'error' || message === 'Sent to printer.');
            }
        };
    }
    function runSunmiTest() {
        if (sunmiBusy) return;
        if (!window.SkylineSunmi) {
            $('#sunmi-print-status').text('The SUNMI printer script did not load.').css('color', '#a32020');
            return;
        }
        window.SkylineSunmi.printTest({
            paperMm: $('#sunmi-paper-mm').val(),
            hotel: @json(optional($general_setting ?? null)->site_title ?: 'Skyline Hotel')
        }, sunmiHooks());
    }
    $('#sunmi-test-btn').on('click', runSunmiTest);
    $('#sunmi-reprint').on('click', runSunmiTest);

</script>
@endsection
