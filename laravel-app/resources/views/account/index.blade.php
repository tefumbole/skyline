@extends('layout.main') @section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
@if($errors->has('account_no'))
<div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ $errors->first('account_no') }}</div>
@endif
<style>
    .acct-board { background:#f3f6fb; margin:-8px -15px 0; padding:12px; }
    .acct-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 10px 28px rgba(12,24,48,.08); }
    .acct-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; background:#0c1830; }
    .acct-head h2 { margin:0; color:#e1b368; font-size:13px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .acct-actions { display:flex; gap:8px; }
    .acct-btn { border:0; border-radius:999px; background:#e1b368; color:#0c1830; font-weight:800; font-size:12px; padding:6px 12px; }
    .acct-btn.ghost { background:transparent; color:#e1b368; border:1px solid #e1b368; }
    #account-table { margin:0; width:100%; }
    #account-table thead th { background:#0c1830 !important; color:#e1b368 !important; font-size:11px; letter-spacing:.04em; text-transform:uppercase; border:0 !important; padding:6px 10px !important; white-space:nowrap; }
    #account-table tbody td { padding:4px 10px !important; white-space:nowrap; vertical-align:middle; border-bottom:1px solid #eef1f6; font-size:13px; line-height:1.2; color:#1c2434; }
    #account-table tbody tr:nth-child(even) td { background:#f7f4ee; }
    #account-table tbody tr:hover td { background:#fff8ea; }
    #account-table tr.is-off td { color:#8b93a7; }
    #account-table .name-cell, #account-table .note-cell { max-width:260px; overflow:hidden; text-overflow:ellipsis; }
    #account-table .money { text-align:right; font-variant-numeric:tabular-nums; font-weight:800; color:#0c1830; }
    #account-table tr.is-off .money { color:#8b93a7; }
    #account-table .btn-group > .btn { padding:2px 10px; line-height:1.4; border-radius:999px; font-size:12px; background:#0c1830; color:#e1b368; border:0; }
    .acct-switch { border:0; border-radius:999px; min-width:54px; height:22px; padding:0 10px; font-size:11px; font-weight:800; cursor:pointer; background:#f8d7d7; color:#9b1c1c; }
    .acct-switch.is-on { background:#d9f5e6; color:#0d7a3a; }
    .acct-default { display:inline-block; margin-left:6px; background:#e1b368; color:#0c1830; border-radius:999px; font-size:10px; font-weight:800; padding:1px 7px; letter-spacing:.04em; }
    .acct-board .dataTables_wrapper { padding:8px 10px 12px; }
    .acct-board .dataTables_wrapper .row { margin:0 0 6px; align-items:center; }
    .acct-board .dt-buttons .btn { background:#0c1830; color:#e1b368; border:0; border-radius:8px; padding:3px 8px; margin-left:4px; }
    .acct-board .dataTables_filter input { border:1px solid #e1b368; border-radius:999px; height:28px; padding:0 10px; }
</style>
<section class="acct-board">
    <div class="acct-card">
    <div class="acct-head">
        <h2>Account List</h2>
        <div class="acct-actions">
            <button class="acct-btn" data-toggle="modal" data-target="#account-modal"><i class="dripicons-plus"></i> {{trans('file.Add Account')}}</button>
            <button class="acct-btn ghost" data-toggle="modal" data-target="#importAccount"><i class="dripicons-copy"></i> {{trans('file.Import Account')}}</button>
        </div>
    </div>
    <div class="table-responsive">
        <table id="account-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>Code</th>
                    <th>{{trans('file.name')}}</th>
                    <th>Balance</th>
                    <th>On</th>
                    <th>{{trans('file.Note')}}</th>
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_account_all as $key=>$account)
                <tr class="{{ $account->is_active ? '' : 'is-off' }}">
                    <td>{{$key}}</td>
                    <td>{{ $account->account_no }}</td>
                    <td class="name-cell" title="{{ $account->name }}">{{ $account->name }}@if($account->is_default)<span class="acct-default">Default</span>@endif</td>
                    <td class="money">{{ number_format((float) $account->total_balance, 0, '.', ' ') }}</td>
                    <td>
                        <button type="button" class="acct-switch {{ $account->is_active ? 'is-on' : '' }}" data-id="{{ $account->id }}">{{ $account->is_active ? 'On' : 'Off' }}</button>
                    </td>
                    <td class="note-cell" title="{{ $account->note }}">{{ $account->note }}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{trans('file.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li><button type="button" data-id="{{$account->id}}" data-account_no="{{$account->account_no}}" data-name="{{$account->name}}"  data-initial_balance="{{$account->initial_balance}}" data-note="{{$account->note}}" class="edit-btn btn btn-link" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i> {{trans('file.edit')}}</button></li>
                                @if(!$account->is_default)
                                <li><button type="button" class="make-default btn btn-link" data-id="{{ $account->id }}"><i class="dripicons-checkmark"></i> Set as default</button></li>
                                @endif
                                <li class="divider"></li>
                                {{ Form::open(['route' => ['accounts.destroy', $account->id], 'method' => 'DELETE'] ) }}
                                <li>
                                    <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> {{trans('file.delete')}}</button>
                                </li>
                                {{ Form::close() }}
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active">
                <th></th>
                <th>{{trans('file.Total')}}</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tfoot>
        </table>
    </div>
    </div>
</section>

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Update Account')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                {!! Form::open(['route' => ['accounts.update', 1], 'method' => 'put']) !!}
                    <div class="form-group">
                        <label>{{trans('file.Account')}} No *</label>
                        <input type="text" name="account_no" required class="form-control">
                        <input type="hidden" name="account_id">
                    </div>
                    <div class="form-group">
                        <label>{{trans('file.name')}} *</label>
                        <input type="text" name="name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{trans('file.Initial Balance')}}</label>
                        <input type="number" name="initial_balance" step="any" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{trans('file.Note')}}</label>
                        <textarea name="note" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">{{trans('file.update')}}</button>
                    </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>
<!-- Import Modal -->
<div id="importAccount" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            {!! Form::open(['route' => 'account.import', 'method' => 'post', 'files' => true]) !!}
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Import Account')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                <p>{{trans('file.The correct column order is')}} (department_id*, account_no*, name*, note) {{trans('file.and you must follow this')}}.</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{trans('file.Upload CSV File')}} *</label>
                            {{Form::file('file', array('class' => 'form-control','required'))}}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label> {{trans('file.Sample File')}}</label>
                            <a href="public/sample_file/sample_account.csv" class="btn btn-info btn-block btn-md"><i class="dripicons-download"></i>  {{trans('file.Download')}}</a>
                        </div>
                    </div>
                </div>
                <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary">
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>

<script type="text/javascript">

    $("ul#account").siblings('a').attr('aria-expanded','true');
    $("ul#account").addClass("show");
    $("ul#account #account-list-menu").addClass("active");

    $('.edit-btn').on('click', function() {
        $("#editModal input[name='account_no']").val( $(this).data('account_no') );
        $("#editModal input[name='name']").val( $(this).data('name') );
        $("#editModal input[name='initial_balance']").val( $(this).data('initial_balance') );
        $("#editModal input[name='account_id']").val( $(this).data('id') );
        $("#editModal textarea[name='note']").val( $(this).data('note') );
    });

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    $(document).on('click', '#account-table .acct-switch', function () {
        var btn = $(this);
        $.ajax({
            url: '{{ url('accounts/toggle') }}/' + btn.data('id'),
            method: 'POST',
            success: function (res) {
                var on = !!(res && res.on);
                btn.toggleClass('is-on', on).text(on ? 'On' : 'Off');
                btn.closest('tr').toggleClass('is-off', !on);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not change this account.';
                alert(msg);
            }
        });
    });

    $(document).on('click', '.make-default', function () {
        $.get('{{ url('accounts/make-default') }}/' + $(this).data('id'), function () {
            window.location.reload();
        });
    });

function confirmDelete() {
    if (confirm("Are you sure want to delete?")) {
        return true;
    }
    return false;
}
    var table = $('#account-table').DataTable( {
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
                'targets': [0, 4, 6]
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
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            },
        ],
        drawCallback: function () {
            var api = this.api();
            datatable_sum(api, false);
        }
    } );
    function datatable_sum(dt_selector) {
        if (!dt_selector || !dt_selector.column(3).footer()) {
            return;
        }
        var total = 0;
        dt_selector.column(3, { page: 'current' }).data().each(function (value) {
            total += parseFloat(String(value).replace(/[^0-9.-]/g, '')) || 0;
        });
        $(dt_selector.column(3).footer()).html(total.toLocaleString());
    }

</script>
@endsection
