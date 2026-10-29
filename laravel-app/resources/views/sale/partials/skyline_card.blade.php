@php
    $skyMoney = function ($amount) {
        $amount = (float) $amount;
        if (abs($amount - round($amount)) < 0.001) {
            return number_format($amount, 0);
        }

        return number_format($amount, 2);
    };
    $cashierPhone = '';
    $viewer = auth()->user();
    if ($viewer && ! empty($viewer->phone)) {
        $cashierPhone = preg_replace('/\D+/', '', (string) $viewer->phone);
    }
    if ($cashierPhone === '' && ! empty($lims_sale_data->user_id)) {
        $saleUser = $lims_sale_data->user;
        if ($saleUser && ! empty($saleUser->phone)) {
            $cashierPhone = preg_replace('/\D+/', '', (string) $saleUser->phone);
        }
    }
    $paidAmount = (float) ($lims_sale_data->paid_amount ?? 0);
    $grand = (float) $lims_sale_data->grand_total;
    $dueAmount = max(0, $grand - $paidAmount);
    $isPaid = (int) $lims_sale_data->payment_status === 4 || ($grand > 0 && $dueAmount < 0.01);
    $saleStatus = (int) $lims_sale_data->sale_status === 1
        ? trans('file.Completed')
        : ((int) $lims_sale_data->sale_status === 2 ? trans('file.Pending') : trans('file.Draft'));
    $saleWhen = $lims_sale_data->created_at;
    $saleWhenText = $saleWhen instanceof \DateTimeInterface
        ? $saleWhen->format('j M Y H:i')
        : date('j M Y H:i', strtotime((string) $saleWhen));
    $logoUrl = \App\Support\SiteBrand::logoUrl($general_setting ?? null);
    $qr = \App\Support\SaleInvoiceQr::dataUri($lims_sale_data, true);
    $lines = [];
    foreach ($lims_product_sale_data as $product_sale_data) {
        $product = \App\Product::find($product_sale_data->product_id);
        $name = $product ? $product->name : 'Item';
        if ($product_sale_data->variant_id) {
            $variant = \App\Variant::find($product_sale_data->variant_id);
            if ($variant && $variant->name !== '') {
                $name .= ' ['.$variant->name.']';
            }
        }
        $lineTotal = (float) ($product_sale_data->total ?? 0);
        $qty = (float) ($product_sale_data->qty ?: 0);
        $unit = $qty ? ($lineTotal / $qty) : 0;
        $lines[] = ['name' => $name, 'qty' => $qty, 'unit' => $unit, 'total' => $lineTotal];
    }
@endphp
<style>
    .sky-inv { max-width: 420px; margin: 18px auto 28px; background: #fff; color: #1a2744; font-family: DejaVu Serif, Georgia, "Times New Roman", serif; padding: 28px 26px 22px; }
    .sky-inv * { box-sizing: border-box; }
    .sky-inv .sky-logo { display: block; height: 92px; width: auto; margin: 0 auto 8px; }
    .sky-inv .sky-brand { margin: 0; text-align: center; font-size: 28px; line-height: 1.15; color: #1a2744; font-weight: 700; }
    .sky-inv .sky-phone { margin: 4px 0 0; text-align: center; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 14px; color: #5c6b80; letter-spacing: .04em; }
    .sky-inv .sky-kicker { margin: 16px 0 14px; text-align: center; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 800; letter-spacing: .08em; }
    .sky-inv table.sky-meta { width: 100%; border-collapse: collapse; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 13px; margin-bottom: 16px; }
    .sky-inv table.sky-meta td { padding: 3px 0; vertical-align: middle; }
    .sky-inv table.sky-meta .lab { width: 38%; font-weight: 800; letter-spacing: .04em; font-size: 12px; }
    .sky-inv .sky-pill { display: inline-block; background: #d9f5e5; color: #178a45; border-radius: 999px; padding: 3px 10px; font-weight: 700; font-size: 12px; }
    .sky-inv table.sky-items { width: 100%; border-collapse: collapse; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 14px; margin-bottom: 12px; }
    .sky-inv table.sky-items th { background: #0c1830; color: #fff; text-align: left; padding: 10px 12px; font-size: 12px; letter-spacing: .06em; }
    .sky-inv table.sky-items th.amt, .sky-inv table.sky-items td.amt { text-align: right; }
    .sky-inv table.sky-items td { padding: 12px; border-bottom: 1px solid #e6ebf2; vertical-align: top; }
    .sky-inv .sky-sub { display: block; color: #8a93a3; font-size: 12px; margin-top: 2px; }
    .sky-inv table.sky-sum { width: 100%; border-collapse: collapse; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; margin-bottom: 8px; }
    .sky-inv table.sky-sum td { padding: 12px 14px; font-weight: 800; }
    .sky-inv table.sky-sum .amt { text-align: right; }
    .sky-inv .sky-total td { background: #e7f2fb; color: #1a2744; }
    .sky-inv .sky-paid td { background: #d9f5e5; color: #178a45; }
    .sky-inv .sky-due td { background: #fff4e5; color: #8a5a1f; }
    .sky-inv .sky-thanks { width: 100%; border-collapse: collapse; margin-top: 18px; }
    .sky-inv .sky-thanks td { vertical-align: bottom; }
    .sky-inv .sky-script { font-family: DejaVu Serif, Georgia, "Times New Roman", serif; font-style: italic; font-size: 32px; line-height: 1; color: #1a2744; margin: 0; }
    .sky-inv .sky-script-sub { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 13px; color: #1a2744; margin: 4px 0 0; }
    .sky-inv .sky-qr { text-align: right; }
    .sky-inv .sky-qr img { width: 92px; height: 92px; }
    .sky-inv .sky-scan { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 10px; color: #8a93a3; text-align: right; margin: 2px 0 0; }
    .sky-inv .sky-dash { border: 0; border-top: 1px dashed #c5ceda; margin: 16px 0 10px; }
    .sky-inv .sky-bar { text-align: center; }
    .sky-inv .sky-bar img { width: 100%; max-width: 340px; height: 54px; }
    .sky-inv .sky-ref { text-align: center; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 12px; color: #5c6b80; margin: 4px 0 0; }
</style>
<div class="sky-inv">
    <img class="sky-logo" src="{{ $logoUrl }}" alt="{{ $general_setting->site_title }}">
    <h1 class="sky-brand">{{ $general_setting->site_title }}</h1>
    @if($cashierPhone !== '')
        <p class="sky-phone">{{ $cashierPhone }}</p>
    @endif
    <div class="sky-kicker">SALES INVOICE</div>
    <table class="sky-meta">
        <tr><td class="lab">REFERENCE:</td><td>{{ $lims_sale_data->reference_no }}</td></tr>
        <tr><td class="lab">DATE:</td><td>{{ $saleWhenText }}</td></tr>
        <tr><td class="lab">CUSTOMER:</td><td>{{ @$lims_customer_data->name }}</td></tr>
        <tr><td class="lab">PHONE:</td><td>{{ @$lims_customer_data->phone_number }}</td></tr>
        <tr><td class="lab">CITY:</td><td>{{ @$lims_customer_data->city }}</td></tr>
        <tr>
            <td class="lab">STATUS:</td>
            <td>@if((int) $lims_sale_data->sale_status === 1)<span class="sky-pill">&#10003; {{ $saleStatus }}</span>@else{{ $saleStatus }}@endif</td>
        </tr>
    </table>
    <table class="sky-items">
        <thead>
            <tr><th>ITEM</th><th class="amt">AMOUNT (XAF)</th></tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
                <tr>
                    <td>{{ $line['name'] }}<span class="sky-sub">{{ $line['qty'] + 0 }} &times; {{ $skyMoney($line['unit']) }}</span></td>
                    <td class="amt">{{ $skyMoney($line['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="sky-sum">
        <tr class="sky-total"><td>TOTAL</td><td class="amt">XAF {{ $skyMoney($grand) }}</td></tr>
    </table>
    @if($isPaid)
        <table class="sky-sum">
            <tr class="sky-paid"><td>PAID</td><td class="amt">XAF {{ $skyMoney($paidAmount) }}</td></tr>
        </table>
    @else
        <table class="sky-sum">
            <tr class="sky-paid"><td>PAID</td><td class="amt">XAF {{ $skyMoney($paidAmount) }}</td></tr>
        </table>
        @if($dueAmount > 0.009)
            <table class="sky-sum">
                <tr class="sky-due"><td>BALANCE</td><td class="amt">XAF {{ $skyMoney($dueAmount) }}</td></tr>
            </table>
        @endif
    @endif
    <table class="sky-thanks">
        <tr>
            <td>
                <p class="sky-script">Thank you</p>
                <p class="sky-script-sub">for shopping with us!</p>
            </td>
            <td class="sky-qr">
                <img src="{{ $qr }}" alt="Invoice QR">
                <p class="sky-scan">Scan for details</p>
            </td>
        </tr>
    </table>
    <hr class="sky-dash">
    <div class="sky-bar">
        <?php echo '<img src="data:image/png;base64,'.DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128').'" alt="barcode">'; ?>
        <p class="sky-ref">{{ $lims_sale_data->reference_no }}</p>
    </div>
</div>
