<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kitchen {{ $table }}</title>
    <style>
        body { margin: 0; background: #e8edf5; color: #0c1830; font-family: Arial, Helvetica, sans-serif; }
        main { max-width: 420px; margin: 24px auto; padding: 18px 16px 20px; background: #fff; border-radius: 16px; }
        h1 { margin: 0; font-size: 28px; }
        .kicker { margin: 0 0 8px; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .line { margin-top: 14px; padding-top: 12px; border-top: 1px solid #e6ebf2; }
        .kind { margin: 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .dish { margin: 2px 0 0; font-size: 18px; font-weight: 700; }
        .note, .who { margin: 6px 0 0; color: #243044; font-size: 15px; }
        .tools { margin-top: 16px; }
        button { min-height: 44px; padding: 0 16px; border: 0; border-radius: 12px; background: #0c1830; color: #fff; font-weight: 700; }
        #kitchen-status { min-height: 20px; margin: 10px 0 0; font-size: 14px; }
    </style>
</head>
<body>
<main>
    <p class="kicker">Kitchen</p>
    <h1>{{ $table }}</h1>
    @foreach($lines as $line)
        <div class="line">
            <p class="kind">{{ $line['kind'] }}</p>
            <p class="dish">{{ $line['name'] }} × {{ $line['qty'] }}</p>
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
    <div class="tools">
        <button type="button" id="kitchen-print">Print</button>
        <p id="kitchen-status"></p>
    </div>
</main>
<script src="{{ asset('public/vendor/sunmi/bundle.umd.js') }}"></script>
<script src="{{ asset('public/js/sunmi-receipt.js') }}"></script>
@php
    $printItems = [];
    $printNotes = [];
    foreach ($lines as $line) {
        $printItems[] = ['name' => $line['kind'].'  '.$line['name'].' x '.($line['qty'] + 0)];
        if ($line['note'] !== '') {
            $printNotes[] = 'Comment: '.$line['note'];
        }
    }
    foreach ($comments as $comment) {
        if (! in_array('Comment: '.$comment, $printNotes, true) && ! in_array($comment, $printNotes, true)) {
            $printNotes[] = 'Comment: '.$comment;
        }
    }
    $kitchenReceipt = [
        'paperMm' => $paper,
        'hotel' => $hotel,
        'title' => 'Kitchen',
        'table' => $table,
        'placed' => $placed,
        'items' => $printItems,
        'notes' => $printNotes,
    ];
@endphp
<script>
    var kitchenReceipt = @json($kitchenReceipt);
    var kitchenBusy = false;
    function kitchenHooks() {
        return {
            onBusy: function (isBusy) {
                kitchenBusy = isBusy;
                var button = document.getElementById('kitchen-print');
                if (button) button.disabled = isBusy;
            },
            onStatus: function (message, tone) {
                var status = document.getElementById('kitchen-status');
                if (!status) return;
                status.textContent = message;
                status.style.color = tone === 'error' ? '#a32020' : '#1a2744';
            }
        };
    }
    function printKitchen() {
        if (kitchenBusy) return;
        if (!window.SkylineSunmi) {
            kitchenHooks().onStatus('The printer script did not load.', 'error');
            return;
        }
        window.SkylineSunmi.printReceipt(kitchenReceipt, kitchenHooks());
    }
    document.getElementById('kitchen-print').addEventListener('click', printKitchen);
    if (window.location.search.indexOf('autoprint=1') !== -1) {
        printKitchen();
    }
</script>
</body>
</html>
