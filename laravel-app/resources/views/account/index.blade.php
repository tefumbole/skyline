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
@php
    $lims_department_list = \App\Department::where('is_active', true)->get();
@endphp
<style>
    .acct-board { background:#f3f6fb; margin:-8px -15px 0; padding:12px; }
    .acct-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 10px 28px rgba(12,24,48,.08); }
    .acct-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; background:#0c1830; }
    .acct-head h2 { margin:0; color:#e1b368; font-size:13px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .acct-actions { display:flex; gap:8px; }
    .acct-btn { border:0; border-radius:999px; background:#e1b368; color:#0c1830; font-weight:800; font-size:12px; padding:6px 12px; }
    .acct-btn.ghost { background:transparent; color:#e1b368; border:1px solid #e1b368; }
    #account-table { margin:0; width:100%; }
    #account-table thead th { background:#0c1830; color:#e1b368; font-size:11px; letter-spacing:.04em; text-transform:uppercase; border:0 !important; padding:6px 10px; white-space:nowrap; }
    #account-table tbody td { padding:3px 10px !important; white-space:nowrap; vertical-align:middle; border-bottom:1px solid #eef1f6; font-size:13px; line-height:1.2; color:#1c2434; }
    #account-table tbody tr:nth-child(even) td { background:#f7f4ee; }
    #account-table tbody tr:hover td { background:#fff8ea; }
    #account-table .note-cell { max-width:220px; overflow:hidden; text-overflow:ellipsis; }
    #account-table .money { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    #account-table .btn-group > .btn { padding:1px 8px; line-height:1.4; border-radius:999px; font-size:12px; }
    .acct-board .toggle.btn { height:20px !important; min-height:20px !important; min-width:52px !important; width:52px !important; border-radius:999px !important; padding:0 !important; }
    .acct-board .toggle .toggle-group { height:20px !important; }
    .acct-board .toggle-group .btn { padding:0 6px !important; line-height:18px !important; font-size:10px !important; }
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
                    <th>{{trans('file.Account')}} No</th>
                    <th>{{trans('file.Department')}} / {{trans('file.Code')}}</th>
                    <th>{{trans('file.name')}}</th>
                    <th>{{trans('file.Initial Balance')}}</th>
                    <th>{{trans('file.Default')}}</th>
                    <th>{{trans('file.Note')}}</th>
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_account_all as $key=>$account)
                <tr>
                    <td>{{$key}}</td>
                    <td>{{ $account->account_no }}</td>
                    @php
                        $deptName = optional($account->departments)->name;
                        $deptCode = optional($account->departments)->code;
                        $deptLabel = trim(($deptName ? $deptName : '').($deptCode ? ' '.$deptCode : ''));
                    @endphp
                    <td>{{ $deptLabel !== '' ? $deptLabel : '—' }}</td>
                    <td>{{ $account->name }}</td>
                    <td class="money">{{ number_format((float) $account->initial_balance, 0, '.', ' ') }}</td>
                    <td>
                        @if($account->is_default)
                        <input type="checkbox" checked class="default" data-id="{{$account->id}}" data-toggle="toggle" data-height="20" data-width="52" data-onstyle="success" data-offstyle="danger">
                        @else
                        <input type="checkbox" class="default" data-id="{{$account->id}}" data-toggle="toggle" data-height="20" data-width="52" data-onstyle="success" data-offstyle="danger">
                        @endif
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
                        <label>{{trans('file.Department')}} *</label>
                        <select class="form-control selectpicker" name="department_id" data-live-search="true">
                            <option value="0">Choose department</option>
                            @foreach($lims_department_list as $account)
                                <option value="{{$account->id}}">{{$account->name}} - {{$account->code}}</option>
                            @endforeach
                        </select>
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

    $('.default').on('change', function() {
        //off to on
        if ($(this).parent().hasClass("btn-success")) {
            var id = $(this).data('id');
            $('.default').not($(this)).parent().removeClass('btn-success');
            $('.default').not($(this)).parent().addClass('btn-danger off');
            $('.default').not($(this)).prop('checked', false);
            $(this).prop('checked', true);
            $.get('accounts/make-default/' + id, function(data) {
                alert(data);
            });
        }
        //on to off
        else {
            $(this).parent().removeClass('btn-danger off');
            $(this).parent().addClass('btn-success');
            $(this).prop('checked', true);
            alert('Please make another account default first!');
        }
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
                'targets': [0, 6]
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
    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();
            $( dt_selector.column( 3 ).footer() ).html(dt_selector.cells( rows, 3, { page: 'current' } ).data().sum().toFixed(2));
        }
        else {
            $( dt_selector.column( 3 ).footer() ).html(dt_selector.cells( rows, 3, { page: 'current' } ).data().sum().toFixed(2));
        }
    }

</script>
@endsection
