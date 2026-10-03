<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Meja {{ $diningTable->code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: Arial, sans-serif; background: #eef1f5; }
        .qr-sheet { width: 105mm; min-height: 148mm; margin: 16px auto; padding: 14mm 10mm; text-align: center; background: #fff; border: 1px solid #d8dde6; }
        .eyebrow { margin: 0 0 6px; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #5d6b82; }
        h1 { margin: 0; font-size: 30px; }
        .outlet { margin: 8px 0 22px; font-size: 16px; }
        .qr { display: inline-block; padding: 12px; border: 2px solid #172033; border-radius: 12px; }
        .qr svg { display: block; width: 62mm; height: 62mm; }
        .instruction { margin: 22px 0 5px; font-size: 18px; font-weight: 700; }
        .url { font-size: 10px; color: #6a7485; overflow-wrap: anywhere; }
        .no-print { margin: 16px auto; text-align: center; }
        button { padding: 10px 18px; cursor: pointer; }
        @media print {
            @page { size: A6 portrait; margin: 0; }
            body { background: #fff; }
            .qr-sheet { width: 105mm; min-height: 148mm; margin: 0; border: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button type="button" onclick="window.print()">Cetak QR</button></div>
    <main class="qr-sheet">
        <p class="eyebrow">Order dari Meja</p>
        <h1>{{ $diningTable->name }}</h1>
        <p class="outlet">{{ $diningTable->outlet?->name }} &middot; {{ $diningTable->code }}</p>
        <div class="qr">{!! $qrSvg !!}</div>
        <p class="instruction">Scan untuk mulai memesan</p>
        <p class="url">{{ $qrUrl }}</p>
    </main>
</body>
</html>
