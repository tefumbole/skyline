<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kitchen {{ $table }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e8edf5; color: #111; font-family: Arial, Helvetica, sans-serif; }
        main { max-width: 420px; margin: 24px auto; padding: 22px 18px 18px; background: #fff; border-radius: 16px; }
        .kicker { margin: 0 0 4px; color: #111; font-size: 13px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0; color: #111; font-size: 40px; font-weight: 900; line-height: 1.05; letter-spacing: -.03em; }
        .line { margin-top: 16px; padding-top: 14px; border-top: 2px solid #111; }
        .kind { margin: 0; color: #111; font-size: 13px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .dish { margin: 4px 0 0; color: #111; font-size: 22px; font-weight: 900; line-height: 1.2; }
        .figures { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .figures td { padding: 4px 0; color: #111; font-size: 16px; font-weight: 700; }
        .figures td:last-child { font-weight: 900; text-align: right; }
        .note, .who { margin: 8px 0 0; color: #111; font-size: 16px; font-weight: 700; }
        .tools { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 18px; }
        .tools a, .tools button { display: block; width: 100%; min-height: 48px; border: 0; border-radius: 12px; font-size: 16px; font-weight: 800; text-align: center; text-decoration: none; line-height: 48px; cursor: pointer; }
        .print { background: #0c1830; color: #fff; }
        .back { background: #e1b368; color: #0c1830; }
        @page { size: 88mm auto; margin: 4mm; }
        @media print {
            body { background: #fff; }
            main { max-width: none; margin: 0; padding: 0; border-radius: 0; }
            h1 { font-size: 28pt; }
            .dish { font-size: 16pt; }
            .figures td, .note, .who { font-size: 12pt; }
            .tools { display: none !important; }
        }
    </style>
</head>
<body>
<main>
    <p class="kicker">Kitchen</p>
    <h1>{{ $table }}</h1>
    @foreach($lines as $line)
        <div class="line">
            <p class="kind">{{ $line['kind'] }}</p>
            <p class="dish">{{ $line['name'] }}</p>
            <table class="figures">
                <tr>
                    <td>Quantity</td>
                    <td>{{ $line['qty'] }}</td>
                </tr>
                <tr>
                    <td>Price</td>
                    <td>{{ \App\Support\TableBoard::money($line['price']) }} XAF</td>
                </tr>
                <tr>
                    <td>Amount</td>
                    <td>{{ \App\Support\TableBoard::money($line['amount']) }} XAF</td>
                </tr>
            </table>
            @if($line['note'] !== '')
                <p class="note">Comment: {{ $line['note'] }}</p>
            @endif
        </div>
    @endforeach
    @if(count($comments))
        <div class="line">
            <p class="kind">Comment</p>
            @foreach($comments as $comment)
                <p class="note">{{ $comment }}</p>
            @endforeach
        </div>
    @endif
    <p class="who">Placed by {{ $placed }}</p>
    <div class="tools" id="kitchen-tools">
        <button type="button" class="print" id="kitchen-print">Print</button>
        <a class="back" id="kitchen-close" href="{{ url('pos') }}">Close</a>
    </div>
</main>
<script>
    var closeUrl = @json(url('pos'));
    document.getElementById('kitchen-print').addEventListener('click', function () {
        var closeBtn = document.getElementById('kitchen-close');
        if (closeBtn) closeBtn.style.display = 'none';
        window.print();
    });
    document.getElementById('kitchen-close').addEventListener('click', function (event) {
        event.preventDefault();
        var printBtn = document.getElementById('kitchen-print');
        if (printBtn) printBtn.style.display = 'none';
        window.location.href = closeUrl;
    });
    window.onafterprint = function () {
        var closeBtn = document.getElementById('kitchen-close');
        if (closeBtn) closeBtn.style.display = '';
    };
</script>
</body>
</html>
