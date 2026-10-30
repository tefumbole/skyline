@extends('layout.main')
@section('content')
@php
    $pdfQuery = ['start_date' => $start_date, 'end_date' => $end_date, 'warehouse_id' => $warehouse_id, 'export' => 'pdf'];
@endphp
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h4 class="text-center">{{ trans('file.Sale Report') }}</h4>
            </div>
            <form method="get" action="{{ route('report.sales') }}">
                <div class="col-md-8 offset-md-2 mt-4 mb-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{ trans('file.Choose Your Date') }}</strong> &nbsp;</label>
                        <div class="d-tc">
                            <div class="input-group">
                                <input type="text" class="daterangepicker-field form-control" value="{{ $start_date }} To {{ $end_date }}" required />
                                <input type="hidden" name="start_date" value="{{ $start_date }}" />
                                <input type="hidden" name="end_date" value="{{ $end_date }}" />
                                <select name="warehouse_id" class="form-control" style="max-width:180px;">
                                    <option value="0">{{ trans('file.All Warehouse') }}</option>
                                    @foreach($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}" @if((int) $warehouse_id === (int) $warehouse->id) selected @endif>{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">{{ trans('file.submit') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="table-responsive mb-4">
        <table id="report-table" class="table table-hover" data-pdf="{{ route('report.sales', $pdfQuery) }}">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{ trans('file.Date') }}</th>
                    <th>{{ trans('file.reference') }}</th>
                    <th>{{ trans('file.product') }}</th>
                    <th>{{ trans('file.Qty') }}</th>
                    <th>{{ trans('file.Price') }}</th>
                    <th>{{ trans('file.Warehouse') }}</th>
                    <th>{{ trans('file.Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $key => $line)
                    <tr>
                        <td>{{ $key }}</td>
                        <td>{{ $line['date'] }}</td>
                        <td>{{ $line['reference'] }}</td>
                        <td>{{ $line['product'] }}</td>
                        <td>{{ number_format((float) $line['qty'], 0, '.', '') }}</td>
                        <td>{{ number_format((float) $line['price'], 2, '.', '') }}</td>
                        <td>{{ $line['warehouse'] }}</td>
                        <td>{{ number_format((float) $line['total'], 2, '.', '') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active">
                <th></th>
                <th>{{ trans('file.Total') }}:</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th>0.00</th>
            </tfoot>
        </table>
    </div>
</section>
<script type="text/javascript">
    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #sale-report-menu").addClass("active");

    var salesPdf = $('#report-table').data('pdf');

    $('#report-table').DataTable({
        order: [],
        language: {
            lengthMenu: '_MENU_ {{ trans("file.records per page") }}',
            info: '<small>{{ trans("file.Showing") }} _START_ - _END_ (_TOTAL_)</small>',
            search: '{{ trans("file.Search") }}',
            paginate: {
                previous: '<i class="dripicons-chevron-left"></i>',
                next: '<i class="dripicons-chevron-right"></i>'
            }
        },
        columnDefs: [
            {
                orderable: false,
                targets: 0
            },
            {
                render: function (data, type) {
                    if (type === 'display') {
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }
                    return data;
                },
                checkboxes: {
                    selectRow: true,
                    selectAllRender: '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                targets: [0]
            }
        ],
        select: { style: 'multi', selector: 'td:first-child' },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                action: function () {
                    window.location = salesPdf;
                }
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer: true
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer: true
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            }
        ],
        drawCallback: function () {
            datatable_sum(this.api(), false);
        }
    });

    function datatable_sum(dt_selector) {
        $(dt_selector.column(7).footer()).html(dt_selector.column(7, { page: 'current' }).data().sum().toFixed(2));
    }

    $(".daterangepicker-field").daterangepicker({
        callback: function (startDate, endDate) {
            var start_date = startDate.format('YYYY-MM-DD');
            var end_date = endDate.format('YYYY-MM-DD');
            $(this).val(start_date + ' To ' + end_date);
            $('input[name="start_date"]').val(start_date);
            $('input[name="end_date"]').val(end_date);
        }
    });
</script>
@endsection
