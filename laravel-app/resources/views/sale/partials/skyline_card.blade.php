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
    if ($cashierPhone === '' && ! empty($lims_biller_data->phone_number)) {
        $cashierPhone = preg_replace('/\D+/', '', (string) $lims_biller_data->phone_number);
    }
    $cashierName = '';
    if (optional($lims_sale_data->user)->name) {
        $cashierName = trim((string) $lims_sale_data->user->name);
    } elseif ($viewer && ! empty($viewer->name)) {
        $cashierName = trim((string) $viewer->name);
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
    $qr = \App\Support\SaleInvoiceQr::dataUri($lims_sale_data, true);
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
        $unit = $qty ? ($lineTotal / $qty) : 0;
        $lines[] = ['name' => $name, 'qty' => $qty, 'unit' => $unit, 'total' => $lineTotal];
    }
    $customerPhone = preg_replace('/\D+/', '', (string) (@$lims_customer_data->phone_number ?? ''));
@endphp
<style>
    .sky-slip { width: 80mm; max-width: 100%; margin: 12px auto 24px; background: #fff; color: #111; font-family: Arial, Helvetica, sans-serif; padding: 6px 8px 10px; }
    .sky-slip * { box-sizing: border-box; }
    .sky-slip .sky-logo { display: block; height: 22mm; width: auto; max-width: 36mm; margin: 0 auto 1mm; object-fit: contain; }
    .sky-slip .sky-phone { margin: 0 0 2mm; text-align: center; font-weight: 800; font-size: 18px; line-height: 1.1; }
    .sky-slip .sky-rule { border: 0; border-top: 3px solid #111; margin: 0; height: 0; }
    .sky-slip .sky-title { margin: 1.2mm 0; text-align: center; font-weight: 900; font-size: 30px; letter-spacing: -0.8px; line-height: 1; }
    .sky-slip .sky-box { background: #ececec; border-radius: 8px; padding: 8px 10px; margin: 3mm 0; font-size: 13px; line-height: 1.45; }
    .sky-slip table.sky-grid { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12.5px; margin: 0 0 2mm; }
    .sky-slip table.sky-grid th { background: #e6e6e6; font-weight: 800; padding: 7px 5px; text-align: left; }
    .sky-slip table.sky-grid th:first-child { border-radius: 8px 0 0 8px; }
    .sky-slip table.sky-grid th:last-child { border-radius: 0 8px 8px 0; }
    .sky-slip table.sky-grid td { padding: 7px 5px; vertical-align: top; }
    .sky-slip .c { text-align: center; }
    .sky-slip .r { text-align: right; white-space: nowrap; }
    .sky-slip table.sky-extra { width: 100%; border-collapse: collapse; font-size: 12.5px; margin: 0 0 1.5mm; }
    .sky-slip table.sky-extra td { padding: 2px 5px; font-weight: 700; }
    .sky-slip table.sky-total { width: 100%; border-collapse: separate; border-spacing: 0; margin: 1mm 0 3mm; }
    .sky-slip table.sky-total td { background: #e6e6e6; font-weight: 800; font-size: 14px; padding: 8px 8px; }
    .sky-slip table.sky-total td:first-child { border-radius: 8px 0 0 8px; }
    .sky-slip table.sky-total td:last-child { border-radius: 0 8px 8px 0; text-align: right; }
    .sky-slip .sky-badge { display: block; width: 64px; margin: 1mm auto 3mm; background: #111; color: #fff; text-align: center; font-weight: 800; font-size: 12px; letter-spacing: .06em; border-radius: 7px; padding: 4px 0 3px; }
    .sky-slip .sky-line { border: 0; border-top: 1px solid #111; margin: 1mm 0 10mm; }
    .sky-slip .sky-sign { text-align: center; margin: 0 0 4mm; }
    .sky-slip .sky-sign-line { width: 62mm; border-top: 1px solid #111; margin: 0 auto 2mm; }
    .sky-slip .sky-sign-name { font-size: 13px; }
    .sky-slip .sky-qr { text-align: center; margin: 0 0 3mm; }
    .sky-slip .sky-qr img { width: 32mm; height: 32mm; }
    .sky-slip .sky-bar { text-align: center; }
    .sky-slip .sky-bar img { width: 100%; height: 14mm; }
    @media print {
        .sky-slip { width: 80mm; margin: 0; }
    }
</style>
<div class="sky-slip">
    <img class="sky-logo" src="{{ $logoUrl }}" alt="">
    @if($cashierPhone !== '')
        <div class="sky-phone">{{ $cashierPhone }}</div>
    @endif
    <hr class="sky-rule">
    <div class="sky-title">SALES INVOICE</div>
    <hr class="sky-rule">
    <div class="sky-box">
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
    <hr class="sky-line">
    @if($cashierName !== '')
        <div class="sky-sign">
            <div class="sky-sign-line"></div>
            <div class="sky-sign-name">{{ $cashierName }}</div>
        </div>
    @endif
    <div class="sky-qr">
        <img src="{{ $qr }}" alt="">
    </div>
    <div class="sky-bar">
        <?php echo '<img src="data:image/png;base64,'.DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128').'" alt="">'; ?>
    </div>
</div>
