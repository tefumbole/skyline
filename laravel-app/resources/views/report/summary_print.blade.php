<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $heading }}</title>
    @php
        $letter = $print === 'letter';
        $lh = \App\Support\Letterhead::viewVars();
        $logo = ! empty($general_setting->site_logo) ? url('public/logo/'.$general_setting->site_logo) : url('public/branding/skyline-logo.png');
    @endphp
    <style>
        body { margin: 0; color: #1a1a1a; font-family: Helvetica, Arial, sans-serif; }
        .toolbar { display: flex; gap: 8px; padding: 10px; }
        .toolbar a, .toolbar button { min-height: 40px; padding: 8px 16px; border-radius: 8px; border: 0; background: #0c1830; color: #fff; font-weight: 700; text-decoration: none; }
        .toolbar a { background: #fff; color: #0c1830; border: 1px solid #0c1830; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; vertical-align: top; }
        @if ($letter)
        @page { size: A4 portrait; margin: 8mm; }
        .sheet { max-width: 190mm; margin: 0 auto; }
        h1 { text-align: center; font-size: 18px; }
        .period { text-align: center; }
        th, td { padding: 4px 6px; font-size: 12px; border-bottom: 1px solid #e6e8ee; }
        thead th { background: #f4f6f8; }
        @else
        @page { size: 72mm auto; margin: 2mm; }
        body { background: #f4f1ea; }
        .sheet { width: 72mm; margin: 12px auto; padding: 3mm; background: #fff; }
        h1, .period { text-align: center; font-size: 12px; }
        .logo { display: block; height: 14mm; margin: 0 auto 2mm; }
        th, td { padding: 1mm 0; font-size: 10px; }
        @endif
        @media print { .toolbar { display: none !important; } body { background: #fff; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ $back }}">Back</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>
    <div class="sheet">
        @if ($letter)
            @if (! empty($lh['quotationHeaderUrl']))
                <img src="{{ $lh['quotationHeaderUrl'] }}" alt="" style="width:100%;height:auto;">
            @endif
        @else
            <img class="logo" src="{{ $logo }}" alt="">
        @endif
        <h1>{{ $heading }}</h1>
        @if (! empty($period))
            <p class="period">{{ $period }}</p>
        @endif
        <table>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        @foreach ($line as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($letter && ! empty($lh['quotationFooterUrl']))
            <img src="{{ $lh['quotationFooterUrl'] }}" alt="" style="width:100%;height:auto;">
        @endif
    </div>
</body>
</html>
