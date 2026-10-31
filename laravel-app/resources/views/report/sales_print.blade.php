<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $heading }} — {{ $general_setting->site_title ?? 'Skyline Hotel' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $letter = $print === 'letter';
        $lh = \App\Support\Letterhead::viewVars();
        $qtyTotalText = abs($qty_total - round($qty_total)) < 0.001 ? (string) (int) round($qty_total) : number_format($qty_total, 2, '.', ',');
        $money = number_format((float) $total, 0, '.', ',');
        $logo = ! empty($general_setting->site_logo) ? url('public/logo/'.$general_setting->site_logo) : url('public/branding/skyline-logo.png');
        $phone = '';
        if (! empty($general_setting->phone)) {
            $phone = $general_setting->phone;
        }
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #1a1a1a; font-family: Helvetica, Arial, sans-serif; background: #fff; }
        .toolbar { padding: 10px 12px; }
        .toolbar button {
            min-height: 40px; padding: 8px 16px; border: 0; border-radius: 8px;
            background: #0c1830; color: #fff; font-weight: 700; cursor: pointer;
        }
        table.lines { width: 100%; border-collapse: collapse; }
        table.lines th, table.lines td { text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        @if ($letter)
        @page { size: A4 portrait; margin: 8mm; }
        .sheet { max-width: 190mm; margin: 0 auto; }
        .letter-header, .letter-footer { display: block; width: 100%; height: auto; }
        h1 { margin: 8px 0 2px; font-size: 18px; text-align: center; }
        .period { margin: 0 0 10px; text-align: center; font-size: 13px; }
        table.lines th, table.lines td { padding: 4px 6px; font-size: 12px; border-bottom: 1px solid #e6e8ee; }
        table.lines th { background: #f4f6f8; }
        table.lines tfoot th { background: #0c1830; color: #fff; }
        @else
        @page { size: 72mm auto; margin: 2mm; }
        body { background: #f4f1ea; }
        .sheet { width: 72mm; max-width: 72mm; margin: 12px auto; padding: 3mm; background: #fff; }
        .logo { display: block; height: 14mm; width: auto; max-width: 28mm; margin: 0 auto 2mm; object-fit: contain; }
        h1 { margin: 0; text-align: center; font-size: 13px; }
        .sub, .period { margin: 1mm 0 0; text-align: center; font-size: 10px; }
        hr { border: 0; border-top: 1px dashed #222; margin: 2mm 0; }
        table.lines th, table.lines td { padding: 0.7mm 0; font-size: 10.5px; vertical-align: top; }
        table.lines tfoot th { border-top: 1px solid #222; font-size: 11px; }
        @media print {
            body { background: #fff !important; }
            .sheet { width: 72mm !important; max-width: 72mm !important; margin: 0 !important; padding: 1mm 2mm !important; }
        }
        @endif
        @media print { .toolbar { display: none !important; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
    </div>
    <div class="sheet">
        @if ($letter)
            @if (! empty($lh['quotationHeaderUrl']))
                <img class="letter-header" src="{{ $lh['quotationHeaderUrl'] }}" alt="">
            @endif
            <h1>{{ $heading }}</h1>
            <p class="period">{{ $period }}</p>
        @else
            <img class="logo" src="{{ $logo }}" alt="">
            <h1>{{ $general_setting->site_title ?? 'Skyline Hotel' }}</h1>
            @if ($phone !== '')
                <p class="sub">{{ $phone }}</p>
            @endif
            <p class="period">{{ $heading }}<br>{{ $period }}</p>
            <hr>
        @endif
        <table class="lines">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="num">Total</th>
                    <th class="num">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $row)
                    @php
                        $qty = (float) $row['qty'];
                        $qtyText = abs($qty - round($qty)) < 0.001 ? (string) (int) round($qty) : number_format($qty, 2, '.', ',');
                    @endphp
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format((float) $row['total'], 0, '.', ',') }}</td>
                        <td class="num">{{ $qtyText }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>Summary</th>
                    <th class="num">{{ $money }}</th>
                    <th class="num">{{ $qtyTotalText }}</th>
                </tr>
            </tfoot>
        </table>
        @if ($letter && ! empty($lh['quotationFooterUrl']))
            <img class="letter-footer" src="{{ $lh['quotationFooterUrl'] }}" alt="">
        @endif
    </div>
</body>
</html>
