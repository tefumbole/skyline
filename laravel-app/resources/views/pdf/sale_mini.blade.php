<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #111; padding: 8pt 8pt 12pt; }
        .center { text-align: center; }
        .logo { height: 42pt; width: auto; }
        .brand { font-size: 11pt; font-weight: bold; margin: 2pt 0 0; text-align: center; }
        .sub { text-align: center; margin: 1pt 0 0; font-size: 8.5pt; }
        .rule { border-top: 0.6pt dashed #222; margin: 6pt 0; }
        .meta { font-size: 8.5pt; line-height: 1.35; }
        table.lines { width: 100%; border-collapse: collapse; }
        table.lines td { padding: 1pt 0; vertical-align: top; font-size: 8.5pt; }
        .amt { text-align: right; white-space: nowrap; }
        .name { font-weight: bold; }
        .thanks { text-align: center; font-size: 8pt; margin: 6pt 0; }
        .inv-created-email { display: none; }
        .sign img, .qr img { height: 52pt; }
        .bar { text-align: center; margin-top: 4pt; }
        .bar img { width: 180pt; height: 28pt; }
    </style>
</head>
<body>
@php
    $paidAmount = (float) ($lims_sale_data->paid_amount ?? 0);
    $dueAmount = max(0, (float) $lims_sale_data->grand_total - $paidAmount);
    $orderTax = (float) ($lims_sale_data->order_tax ?? 0);
    $orderDiscount = (float) ($lims_sale_data->order_discount ?? 0);
    $couponDiscount = (float) ($lims_sale_data->coupon_discount ?? 0);
    $lineTaxTotal = 0;
    foreach ($lims_product_sale_data as $taxLine) {
        $lineTaxTotal += (float) ($taxLine->tax ?? 0);
    }
    $taxTotal = $lineTaxTotal + $orderTax;
    $saleStatus = (int) $lims_sale_data->sale_status === 1
        ? trans('file.Completed')
        : ((int) $lims_sale_data->sale_status === 2 ? trans('file.Pending') : trans('file.Draft'));
    $logoUrl = \App\Support\SiteBrand::logoUrl($general_setting ?? null);
    $qr = \App\Support\SaleInvoiceQr::dataUri($lims_sale_data, true);
@endphp
<div class="center"><img class="logo" src="{{ $logoUrl }}" alt=""></div>
<p class="brand">{{ $general_setting->site_title }}</p>
@if(!empty($lims_biller_data->phone_number))
    <p class="sub">{{ $lims_biller_data->phone_number }}</p>
@endif
<p class="sub" style="font-weight:bold;">Sales Invoice</p>
<div class="rule"></div>
<div class="meta">
    <div><strong>{{ trans('file.reference') }}:</strong> {{ $lims_sale_data->reference_no }}</div>
    <div><strong>{{ trans('file.Date') }}:</strong> {{ $lims_sale_data->created_at->format('d-m-Y H:i') }}</div>
    <div><strong>{{ trans('file.Sale Status') }}:</strong> {{ $saleStatus }}</div>
    <div><strong>{{ trans('file.customer') }}:</strong> {{ @$lims_customer_data->name }}</div>
    <div><strong>{{ trans('file.Phone Number') }}:</strong> {{ @$lims_customer_data->phone_number }}</div>
    <div><strong>{{ trans('file.City') }}:</strong> {{ @$lims_customer_data->city }}</div>
</div>
<div class="rule"></div>
<table class="lines">
    @foreach($lims_product_sale_data as $product_sale_data)
        @php
            $product = \App\Product::find($product_sale_data->product_id);
            $productName = $product->name ?? 'Item';
            $lineTotal = (float) ($product_sale_data->total ?? 0);
            $qty = (float) ($product_sale_data->qty ?: 0);
            $unit = $qty ? ($lineTotal / $qty) : 0;
            $lineTax = (float) ($product_sale_data->tax ?? 0);
            $lineTaxRate = (float) ($product_sale_data->tax_rate ?? 0);
        @endphp
        <tr><td class="name" colspan="2">{{ $productName }}</td></tr>
        <tr>
            <td>{{ $qty + 0 }} × {{ \App\Support\Money::format($unit) }}</td>
            <td class="amt">{{ \App\Support\Money::format($lineTotal) }}</td>
        </tr>
        <tr>
            <td>{{ trans('file.Tax') }}@if($lineTaxRate > 0) ({{ rtrim(rtrim(number_format($lineTaxRate, 2), '0'), '.') }}%)@endif</td>
            <td class="amt">{{ \App\Support\Money::format($lineTax) }}</td>
        </tr>
    @endforeach
</table>
<div class="rule"></div>
<table class="lines">
    <tr><td>{{ trans('file.Discount') }}</td><td class="amt">{{ \App\Support\Money::format($orderDiscount + $couponDiscount) }}</td></tr>
    <tr><td>{{ trans('file.Tax') }}</td><td class="amt">{{ \App\Support\Money::format($taxTotal) }}</td></tr>
    <tr><td><strong>{{ trans('file.Total') }}</strong></td><td class="amt"><strong>{{ \App\Support\Money::format($lims_sale_data->grand_total) }}</strong></td></tr>
    <tr><td>{{ trans('file.Paying Amount') }}</td><td class="amt">{{ \App\Support\Money::format($paidAmount) }}</td></tr>
    <tr><td>{{ trans('file.Pending Amount') }}</td><td class="amt">{{ \App\Support\Money::format($dueAmount) }}</td></tr>
    @foreach($lims_payment_data as $payment)
        <tr><td>{{ $payment->paying_method }}</td><td class="amt">{{ \App\Support\Money::format($payment->amount) }}</td></tr>
    @endforeach
    <tr>
        <td>{{ trans('file.Payment Status') }}</td>
        <td class="amt">
            @if($lims_sale_data->payment_status == 1) {{ trans('file.Pending') }}
            @elseif($lims_sale_data->payment_status == 2) {{ trans('file.Due') }}
            @elseif($lims_sale_data->payment_status == 3) {{ trans('file.Partial') }}
            @else {{ trans('file.Paid') }}
            @endif
        </td>
    </tr>
</table>
<div class="meta" style="margin-top:6pt;">
    <strong>{{ trans('file.In Words') }}:</strong>
    {{ ($currency->code ?? '') }} {{ str_replace('-', ' ', $numberInWords) }}
</div>
<p class="thanks">{{ trans('file.Thank you for shopping with us. Please come again') }}</p>
<table class="lines">
    <tr>
        <td style="width:58%;">
            @include('pdf.partials._created_by_signature', [
                'createdUser' => $lims_sale_data->user ?? null,
                'stampDate' => $lims_sale_data->created_at ?? null,
                'createdClass' => 'created-by',
            ])
        </td>
        <td class="center" style="width:42%;"><img src="{{ $qr }}" alt="qrcode" style="width:68pt;height:68pt;"></td>
    </tr>
</table>
<div class="bar">
    <?php echo '<img src="data:image/png;base64,'.DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128').'" alt="barcode">'; ?>
</div>
</body>
</html>
