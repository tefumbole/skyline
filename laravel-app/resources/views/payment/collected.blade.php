@extends('layout.main')
@section('content')
@if(session()->has('message'))
    <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session('message') }}</div>
@endif
@if(session()->has('not_permitted'))
    <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session('not_permitted') }}</div>
@endif
<section>
    <p style="margin:0 0 12px"><a class="btn btn-default btn-sm" href="{{ route('payment.index') }}">Awaiting payment</a></p>
    <div class="table-responsive">
        <table id="collected-table" class="table">
            <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Invoice number</th>
                <th>Paid</th>
                <th>Due</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($sales as $sale)
                @php
                    $due = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
                    $when = $sale->updated_at ?: $sale->created_at;
                @endphp
                <tr>
                    <td>{{ $when ? $when->format('d-m-Y H:i') : '' }}</td>
                    <td>{{ optional($sale->customer)->name ?: 'Client' }}</td>
                    <td>{{ $sale->reference_no }}</td>
                    <td>{{ number_format((float) $sale->paid_amount, 0) }} XAF</td>
                    <td>{{ number_format($due, 0) }} XAF</td>
                    <td><a class="btn btn-default btn-sm" target="_blank" rel="noopener" href="{{ route('sale.invoice', $sale->id) }}">Print</a></td>
                </tr>
            @empty
                <tr><td colspan="6">No collected payment yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
<script>
    $("ul#payments").siblings('a').attr('aria-expanded','true');
    $("ul#payments").addClass("show");
    $("ul#payments #payment-collected-menu").addClass("active");
    $('#collected-table').DataTable({
        order: [],
        language: {
            lengthMenu: '_MENU_ {{trans("file.records per page")}}',
            info: '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            search: '{{trans("file.Search")}}',
            paginate: {
                previous: '<i class="dripicons-chevron-left"></i>',
                next: '<i class="dripicons-chevron-right"></i>'
            }
        },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lf>rtip'
    });
</script>
@endsection
