@extends('layout.main') @section('content')
@if(session()->has('message1'))
        <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message1') !!}</div>
@endif
@if(session()->has('message2'))
        <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message2') }}</div>
@endif
@if(session()->has('message3'))
        <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message3') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section>
    <div class="table-responsive">
        <table id="user-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.name')}}</th>
                    <th>{{trans('file.Phone Number')}}</th>
                    <th>{{trans('file.Email')}}</th>
                    <th>{{trans('file.Company Name')}}</th>
                    <th>{{trans('file.Additional Phone Number')}}</th>
                    <th>{{trans('file.Role')}}</th>
                    <th>{{trans('file.Status')}}</th>
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_user_list as $key=>$user)
                <tr data-id="{{$user->id}}" @if(in_array("users-edit", $all_permission)) data-edit-url="{{ route('user.edit', $user->id) }}" style="cursor:pointer;" @endif>
                    <td>{{$key}}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->phone}}</td>
                    <td>{{ $user->email}}</td>
                    <td>{{ $user->company_name}}</td>
                    <td>{{ $user->additional_phone}}</td>
                    <td>{{ $rolesById[$user->role_id] ?? '—' }}</td>
                    @if($user->is_active)
                    <td><div class="badge badge-success">Active</div></td>
                    @else
                    <td><div class="badge badge-danger">Inactive</div></td>
                    @endif
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{trans('file.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                @if(in_array("users-edit", $all_permission))
                                <li>
                            <a href="{{ route('user.edit', $user->id) }}" class="btn btn-link"><i class="dripicons-document-edit"></i> {{trans('file.edit')}}</a>
                            <button type="button" class="btn btn-link edit-basics"
                                data-id="{{ $user->id }}"
                                data-name="{{ $user->name }}"
                                data-phone="{{ $user->phone }}"
                                data-role="{{ $user->role_id }}">Edit name, phone, role</button>
                                </li>
                                <li class="divider"></li>
                                <li>
                                    <a href="javascript:void(0)" class="btn btn-link btn-request-sig"
                                       data-url="{{ route('user.signature.request', $user->id) }}"
                                       data-type="approve"
                                       data-name="{{ $user->name }}">
                                        <i class="dripicons-user"></i> Request Approver
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" class="btn btn-link btn-request-sig"
                                       data-url="{{ route('user.signature.request', $user->id) }}"
                                       data-type="stemp"
                                       data-name="{{ $user->name }}">
                                        <i class="dripicons-message"></i> Request Comment
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" class="btn btn-link btn-request-sig"
                                       data-url="{{ route('user.signature.request', $user->id) }}"
                                       data-type="sign"
                                       data-name="{{ $user->name }}">
                                        <i class="dripicons-pencil"></i> Request Signature
                                    </a>
                                </li>
                                @endif
                                @if(in_array("users-delete", $all_permission))
                                <li class="divider"></li>
                                {{ Form::open(['route' => ['user.destroy', $user->id], 'method' => 'DELETE'] ) }}
                                <li>
                                    <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> {{trans('file.delete')}}</button>
                                </li>
                                {{ Form::close() }}
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div id="basics-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="basics-form" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Name, phone, and role</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" id="basics-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="basics-phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role_id" id="basics-role" class="form-control" required>
                            @foreach($roleChoices as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">

    $("ul#people").siblings('a').attr('aria-expanded','true');
    $("ul#people").addClass("show");
    $("ul#people #user-list-menu").addClass("active");

    var user_id = [];
    var user_verified = <?php echo json_encode(true) ?>;
    var all_permission = <?php echo json_encode($all_permission) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

	function confirmDelete() {
	    if (confirm("Permanently delete this person from the system? If they are an intern, their login, placement, submissions and timesheets are removed. This cannot be undone.")) {
	        return true;
	    }
	    return false;
	}

    $('#user-table').DataTable( {
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
                'targets': [0, 7, 8]
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
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(true) {
                        user_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                user_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(user_id.length && confirm("Permanently delete the selected people from the system? Interns lose their login, placement, submissions and timesheets. This cannot be undone.")) {
                            $.ajax({
                                type:'POST',
                                url:'user/deletebyselection',
                                data:{
                                    userIdArray: user_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!user_id.length)
                            alert('No user is selected!');
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

    if(all_permission.indexOf("users-delete") == -1)
        $('.buttons-delete').addClass('d-none');

    $(document).on('click', '.edit-basics', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var button = $(this);
        $('#basics-name').val(button.attr('data-name') || '');
        $('#basics-phone').val(button.attr('data-phone') || '');
        $('#basics-role').val(String(button.attr('data-role') || ''));
        $('#basics-form').attr('action', '{{ url('user') }}/' + button.attr('data-id') + '/basics');
        $('#basics-modal').modal('show');
    });

    $('#user-table tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('td:first-child, .btn-group, a, button, input, label, .dropdown-menu, .checkbox').length) {
            return;
        }
        var url = $(this).data('edit-url');
        if (url) {
            window.location = url;
        }
    });

    var requestLabels = { approve: 'Approver', stemp: 'Comment', sign: 'Signature' };
    $(document).on('click', '.btn-request-sig', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var type = $btn.data('type');
        var label = requestLabels[type] || type;
        var name = $btn.data('name') || 'this user';
        if (!confirm('Send a WhatsApp link so ' + name + ' can draw their ' + label + '?')) {
            return;
        }
        $btn.addClass('disabled').css('opacity', 0.6);
        $.ajax({
            type: 'POST',
            url: $btn.data('url'),
            data: { type: type },
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).done(function (res) {
            var msg = (res && res.message) ? res.message : (label + ' request sent.');
            if (res && res.link) {
                var copy = prompt(msg + '\n\nLink (copy if WhatsApp failed):', res.link);
                if (copy === null && res.link) {
                    window.open(res.link, '_blank');
                }
            } else {
                alert(msg);
            }
        }).fail(function (xhr) {
            var res = xhr.responseJSON || {};
            var msg = res.message || ('Could not send ' + label + ' request.');
            if (res.link) {
                prompt(msg + '\n\nOpen / copy this link:', res.link);
            } else {
                alert(msg);
            }
        }).always(function () {
            $btn.removeClass('disabled').css('opacity', 1);
        });
    });
</script>

{{-- Result toast area --}}
<div id="sig-request-toast" class="alert alert-info" style="display:none;position:fixed;bottom:20px;right:20px;z-index:9999;max-width:420px;"></div>
@endsection
