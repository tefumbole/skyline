@extends('layout.main') @section('content')

@if($errors->has('name'))
<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ $errors->first('name') }}</div>
@endif
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<style>
    .acct-board { background:#f3f6fb; margin:-8px -15px 0; padding:12px; }
    .acct-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 10px 28px rgba(12,24,48,.08); }
    .acct-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; background:#0c1830; }
    .acct-head h2 { margin:0; color:#e1b368; font-size:13px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .acct-head p { margin:3px 0 0; color:#fff; font-size:12px; }
    .acct-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .acct-btn { border:0; border-radius:999px; background:#e1b368; color:#0c1830; font-weight:800; font-size:12px; padding:6px 12px; }
    .acct-btn.ghost { background:transparent; color:#e1b368; border:1px solid #e1b368; }
    #department-table { margin:0; }
    #department-table thead th { background:#0c1830; color:#e1b368; font-size:11px; letter-spacing:.04em; text-transform:uppercase; border:0; padding:6px 10px; white-space:nowrap; }
    #department-table tbody td { padding:4px 10px; white-space:nowrap; vertical-align:middle; border-bottom:1px solid #eef1f6; font-size:13px; line-height:1.25; }
    #department-table tbody tr:nth-child(even) td { background:#f7f4ee; }
    .acct-board .dataTables_wrapper { padding:8px 10px 12px; }
    .acct-board .dt-buttons .btn { background:#0c1830; color:#e1b368; border:0; border-radius:8px; padding:3px 8px; }
</style>
<section class="acct-board">
    <div class="acct-card">
    <div class="acct-head">
        <div>
            <h2>HRM · Department</h2>
            <p>Staff departments. This list stays under Human Resource.</p>
        </div>
        <div class="acct-actions">
            <button type="button" class="acct-btn" data-toggle="modal" data-target="#createModal"><i class="dripicons-plus"></i> {{trans('file.Add Department')}}</button>
            <button class="acct-btn ghost" data-toggle="modal" data-target="#importDepartment"><i class="dripicons-copy"></i> {{trans('file.Import Department')}}</button>
        </div>
    </div>
    <div class="table-responsive">
        <table id="department-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Department')}}</th>
                    <th>{{trans('file.Code')}}</th>
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_department_all as $key=>$department)
                <tr data-id="{{$department->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $department->name }}</td>
                    <td>{{ $department->code }}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{trans('file.action')}}
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="{{$department->id}}" data-code="{{$department->code}}" data-name="{{$department->name}}" class="edit-btn btn btn-link" data-toggle="modal" data-target="#editModal" ><i class="dripicons-document-edit"></i>  {{trans('file.edit')}}</button>
                                </li>
                                <li class="divider"></li>
                                {{ Form::open(['route' => ['departments.destroy', $department->id], 'method' => 'DELETE'] ) }}
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
        </table>
    </div>
    </div>
</section>

<!-- Create Modal -->
<div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        {!! Form::open(['route' => 'departments.store', 'method' => 'post']) !!}
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Add Department')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
          <form>
            <div class="form-group">
                <label>{{trans('file.name')}} *</label>
                {{Form::text('name',null,array('required' => 'required', 'class' => 'form-control', 'placeholder' => 'Type department name...'))}}
            </div>
              <div class="form-group">
                  <label>{{trans('file.Code')}} </label>
                  {{Form::number('code',null,array( 'class' => 'form-control', 'placeholder' => 'Type department code...'))}}
              </div>
            <div class="form-group">
              <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary">
            </div>
          </form>
        </div>
        {{ Form::close() }}
      </div>
    </div>
</div>
<!-- Edit Modal -->
<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        {{ Form::open(['route' => ['departments.update', 1], 'method' => 'PUT'] ) }}
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Update Department')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
            <div class="form-group">
                <label>{{trans('file.name')}} *</label>
                {{Form::text('name',null, array('required' => 'required', 'class' => 'form-control'))}}
            </div>
              <div class="form-group">
                  <label>{{trans('file.Code')}} </label>
                  {{Form::number('code',null, array( 'class' => 'form-control'))}}
              </div>
            <input type="hidden" name="department_id">
            <div class="form-group">
                <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary">
              </div>
            </div>
      {{ Form::close() }}
    </div>
  </div>
</div>


<!-- Import Modal -->
<div id="importDepartment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            {!! Form::open(['route' => 'departments.import', 'method' => 'post', 'files' => true]) !!}
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Import Department')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                <p>{{trans('file.The correct column order is')}} (name*, code*) {{trans('file.and you must follow this')}}.</p>
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
                            <a href="public/sample_file/sample_department.csv" class="btn btn-info btn-block btn-md"><i class="dripicons-download"></i>  {{trans('file.Download')}}</a>
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
    $("ul#hrm").siblings('a').attr('aria-expanded','true');
    $("ul#hrm").addClass("show");
    $("#hrm-dept-menu").addClass("active");

    var department_id = [];
    var user_verified = <?php echo json_encode(true) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function confirmDelete() {
      if (confirm("Are you sure want to delete?")) {
          return true;
      }
      return false;
    }
$(document).ready(function() {
    $('.edit-btn').on('click', function(){
        console.log($(this).data('code'));
        $("#editModal input[name='department_id']").val($(this).data('id'));
        $("#editModal input[name='name']").val($(this).data('name'));
        $("#editModal input[name='code']").val($(this).data('code'));
    });
});

    $('#department-table').DataTable( {
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
                'targets': [0, 2]
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
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
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
                footer:true
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(true) {
                        department_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                department_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(department_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'departments/deletebyselection',
                                data:{
                                    departmentIdArray: department_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!department_id.length)
                            alert('No department is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
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
