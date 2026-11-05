@php
    $customerName = '';
    if ($sale->relationLoaded('customer')) {
        $customerName = $sale->customer->name ?? '';
    } else {
        $customer = DB::table('customers')->find($sale->customer_id);
        $customerName = $customer->name ?? '';
    }
    $pay = (int) $sale->payment_status;
    if ($pay === 4) {
        $payLabel = trans('file.Paid');
        $payClass = 'badge-success';
    } elseif ($pay === 3) {
        $payLabel = trans('file.Partial');
        $payClass = 'badge-warning';
    } elseif ($pay === 2) {
        $payLabel = trans('file.Due');
        $payClass = 'badge-danger';
    } else {
        $payLabel = trans('file.Pending');
        $payClass = 'badge-danger';
    }
    $saleDate = $sale->created_at instanceof \Carbon\Carbon
        ? $sale->created_at->toDateString()
        : date('Y-m-d', strtotime((string) $sale->created_at));
@endphp
<tr>
    <td>{{ date($general_setting->date_format, strtotime($saleDate)) }}</td>
    <td>{{ $sale->reference_no }}</td>
    <td>{{ $customerName }}</td>
    <td><div class="badge {{ $payClass }}">{{ $payLabel }}</div></td>
    <td>{{ $sale->grand_total }}</td>
</tr>
