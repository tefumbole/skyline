@php
    $skyMoney = function ($amount) {
        $amount = (float) $amount;
        if (abs($amount - round($amount)) < 0.001) {
            return number_format($amount, 0);
        }

        return number_format($amount, 2);
    };
    $cashierPhone = '(+237) 694-677-541';
    $saleUser = $lims_sale_data->user;
    $cashierName = $saleUser && $saleUser->name ? trim((string) $saleUser->name) : '';
    $signatureSrc = '';
    if ($saleUser && ! empty($saleUser->sign)) {
        $sign = \App\Support\LetterSignature::invoiceSignatureForUser($saleUser, $lims_sale_data->created_at);
        if (($sign['via'] ?? '') === 'user' && ! empty($sign['src'])) {
            $signatureSrc = $sign['src'];
        }
    }
    $paidAmount = (float) ($lims_sale_data->paid_amount ?? 0);
    $grand = (float) $lims_sale_data->grand_total;
    $dueAmount = max(0, $grand - $paidAmount);
    $payStatus = (int) $lims_sale_data->payment_status;
    if ($payStatus === 4 || ($grand > 0 && $dueAmount < 0.01)) {
        $statusLabel = 'PAID';
    } elseif ($payStatus === 3) {
        $statusLabel = 'PARTIAL';
    } elseif ($payStatus === 1) {
        $statusLabel = 'PENDING';
    } else {
        $statusLabel = 'DUE';
    }
    $logoUrl = \App\Support\SiteBrand::logoUrl($general_setting ?? null);
    $qr = \App\Support\SaleInvoiceQr::dataUri($lims_sale_data, true, \App\Support\SalePayQr::payUrl($lims_sale_data));
    $orderDiscount = (float) ($lims_sale_data->order_discount ?? 0) + (float) ($lims_sale_data->coupon_discount ?? 0);
    $shippingCost = (float) ($lims_sale_data->shipping_cost ?? 0);
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
        if ($lineTotal <= 0.009) {
            continue;
        }
        $unit = $qty ? ($lineTotal / $qty) : 0;
        $lines[] = ['name' => $name, 'qty' => $qty, 'unit' => $unit, 'total' => $lineTotal];
    }
    $customerPhone = preg_replace('/\D+/', '', (string) (@$lims_customer_data->phone_number ?? ''));
@endphp
<style>
        .sky-roll { width: 80mm; margin: 8px auto 12px; }
    .sky-slip { width: 72mm; max-width: 100%; margin: 0 auto; zoom: 1; background: #fff; color: #111; font-family: Arial, Helvetica, sans-serif; padding: 2px 3px 4px; }
    .sky-pdf .sky-roll { width: auto; margin: 0; }
    .sky-pdf .sky-slip { width: 72mm; zoom: 1; }
    .sky-slip * { box-sizing: border-box; }
    .sky-slip .sky-logo { display: block; height: 14mm; width: auto; max-width: 28mm; margin: 0 auto 0.5mm; object-fit: contain; }
    .sky-slip .sky-phone { margin: 0 0 1mm; text-align: center; font-weight: 800; font-size: 12px; line-height: 1.1; letter-spacing: 0; }
    .sky-slip .sky-rule { border: 0; border-top: 2px solid #111; margin: 0; height: 0; }
    .sky-slip .sky-title { margin: 0.6mm 0; text-align: center; font-weight: 900; font-size: 18px; letter-spacing: -0.4px; line-height: 1.05; }
    .sky-slip .sky-box { background: #ececec; border-radius: 6px; padding: 4px 6px; margin: 1.5mm 0; font-size: 11px; line-height: 1.3; }
    .sky-slip table.sky-grid { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 11px; margin: 0 0 1mm; }
    .sky-slip table.sky-grid th { background: #e6e6e6; font-weight: 800; padding: 3px 2px; }
    .sky-slip table.sky-grid td { padding: 3px 2px; vertical-align: top; }
    .sky-slip table.sky-grid th:nth-child(1),
    .sky-slip table.sky-grid td:nth-child(1) { width: 46%; text-align: left; }
    .sky-slip table.sky-grid th:nth-child(2),
    .sky-slip table.sky-grid td:nth-child(2) { width: 14%; text-align: center; }
    .sky-slip table.sky-grid th:nth-child(3),
    .sky-slip table.sky-grid td:nth-child(3),
    .sky-slip table.sky-grid th:nth-child(4),
    .sky-slip table.sky-grid td:nth-child(4) { width: 20%; text-align: right; }
    .sky-slip .c { text-align: center; }
    .sky-slip .r { text-align: right; white-space: nowrap; }
    .sky-slip table.sky-extra { width: 100%; border-collapse: collapse; font-size: 11px; margin: 0 0 1mm; }
    .sky-slip table.sky-extra td { padding: 1px 3px; font-weight: 700; }
    .sky-slip table.sky-total { width: 100%; border-collapse: separate; border-spacing: 0; margin: 1mm 0 1.5mm; }
    .sky-slip table.sky-total td { background: #e6e6e6; font-weight: 800; font-size: 12px; padding: 4px 6px; }
    .sky-slip table.sky-total td:first-child { border-radius: 6px 0 0 6px; }
    .sky-slip table.sky-total td:last-child { border-radius: 0 6px 6px 0; text-align: right; }
    .sky-slip .sky-badge { display: block; width: 52px; margin: 1mm 0 0; background: #111; color: #fff; text-align: center; font-weight: 800; font-size: 11px; letter-spacing: .06em; border-radius: 6px; padding: 2px 0 1px; }
    .sky-slip .sky-sign { text-align: center; margin: 2mm 6mm 1mm; }
    .sky-slip .sky-sign img { display: block; height: 10mm; width: auto; max-width: 36mm; margin: 0 auto -4mm; }
    .sky-slip .sky-sign-line { border-bottom: 1px solid #111; height: 0; margin: 0 0 1mm; }
    .sky-slip .sky-sign-name { font-size: 11px; margin-top: 0.5mm; }
    .sky-slip .sky-qr { text-align: center; margin: 1mm 0; }
    .sky-slip .sky-qr img { width: 22mm; height: 22mm; }
    .sky-slip .sky-bar { text-align: center; }
    .sky-slip .sky-bar img { width: 70%; height: 8mm; }
    @media print {
        .sky-roll { width: 80mm; margin: 0; }
        .sky-slip { width: 72mm; margin: 0 auto; zoom: 1; }
        .sky-pdf .sky-slip { width: 88mm; zoom: 1; }
    }
</style>
<div class="sky-roll">
<div class="sky-slip">
    <img class="sky-logo" src="{{ $logoUrl }}" alt="">
    @if($cashierPhone !== '')
        <div class="sky-phone">{{ $cashierPhone }}</div>
    @endif
    <hr class="sky-rule">
    <div class="sky-title">SALES INVOICE</div>
    <hr class="sky-rule">
    <div class="sky-box">
        <div><strong>Invoice number:</strong> {{ $lims_sale_data->reference_no }}</div>
        <div><strong>Date:</strong> {{ $lims_sale_data->created_at ? $lims_sale_data->created_at->format('d-m-Y H:i') : '' }}</div>
        <div><strong>Customer:</strong> {{ @$lims_customer_data->name }}</div>
        <div><strong>Phone:</strong> {{ $customerPhone }}</div>
    </div>
    <table class="sky-grid">
        <thead>
            <tr>
                <th>Product</th>
                <th class="c">Qty</th>
                <th class="r">Price</th>
                <th class="r">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
                <tr>
                    <td>{{ $line['name'] }}</td>
                    <td class="c">{{ $line['qty'] + 0 }}</td>
                    <td class="r">{{ $skyMoney($line['unit']) }}</td>
                    <td class="r">{{ $skyMoney($line['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if($orderDiscount > 0.009)
        <table class="sky-extra">
            <tr><td>Discount</td><td class="r">{{ $skyMoney($orderDiscount) }}</td></tr>
        </table>
    @endif
    @if($shippingCost > 0.009)
        <table class="sky-extra">
            <tr><td>Shipping</td><td class="r">{{ $skyMoney($shippingCost) }}</td></tr>
        </table>
    @endif
    <table class="sky-total">
        <tr>
            <td>TOTAL (XAF)</td>
            <td>{{ $skyMoney($grand) }}</td>
        </tr>
    </table>
    <div class="sky-badge">{{ $statusLabel }}</div>
    @if($cashierName !== '' || $signatureSrc !== '')
        <div class="sky-sign">
            @if($signatureSrc !== '')
                <img src="{{ $signatureSrc }}" alt="">
            @endif
            <div class="sky-sign-line"></div>
            @if($cashierName !== '')
                <div class="sky-sign-name">{{ $cashierName }}</div>
            @endif
        </div>
    @endif
    <div class="sky-qr">
        <img src="{{ $qr }}" alt="">
    </div>
    <div class="sky-bar">
        <?php echo '<img src="data:image/png;base64,'.DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128').'" alt="">'; ?>
    </div>
</div>
</div>
