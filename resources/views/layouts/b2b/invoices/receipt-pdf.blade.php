<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    @php
        // Versi PDF (dompdf) dari receipt.blade.php. dompdf tidak mendukung flex/grid, jadi layout memakai tabel.
        $receiptNumber = \App\Support\B2b\InvoiceDocument::receiptNumber($invoice);
        $customer = $invoice->customer;
        $payer = trim((string) $customer?->contact_name) ?: $customer?->name;
        $receivedAt = $invoice->paid_at ?? $invoice->issue_date;
        $payments = $invoice->allocations->pluck('payment')->filter()->where('status', 'posted')->unique('id')->values();
        $longDate = fn ($value) => $value?->locale('id')->translatedFormat('d F Y');
        $amount = (int) $invoice->paid_amount;
    @endphp
    <title>Kwitansi {{ $receiptNumber }}</title>
    <style>
        @page { size: A4; margin: 18mm 18mm 24mm; }
        body { margin: 0; color: #2b1d19; font-family: "DejaVu Sans", sans-serif; font-size: 10.5px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .topbar { position: fixed; top: -18mm; left: -18mm; right: -18mm; height: 2.6mm; background: #911b1d; }
        .watermark { position: fixed; right: -16mm; bottom: 4mm; width: 100mm; opacity: .22; z-index: -1; }
        .brand { margin: 0 0 2px; font-family: "DejaVu Serif", serif; font-size: 21px; font-weight: bold; line-height: 1.1; }
        .address { margin: 0; font-size: 8.5px; color: #7a645c; }
        .doc-title { margin: 0; font-size: 21px; font-weight: bold; letter-spacing: 3px; color: #911b1d; }
        .doc-subtitle { margin: 2px 0 0; font-size: 7.5px; font-weight: bold; letter-spacing: 1.5px; color: #7a645c; }
        .head { border-bottom: 1px solid #ecdcd0; }
        .head td { padding-bottom: 5mm; vertical-align: middle; }
        .meta { margin: 5mm 0 3mm; border-collapse: separate; border-spacing: 2.5mm 0; }
        .meta-wrap { margin-left: -2.5mm; margin-right: -2.5mm; }
        .meta td { width: 33.3%; padding: 6px 9px; background: #fdf0e6; border-radius: 6px; }
        .label { display: block; font-size: 7.5px; font-weight: bold; letter-spacing: .8px; color: #7a645c; }
        .meta strong { font-size: 10.5px; }
        .rows td { padding: 8px 0; border-bottom: 1px dashed #ecdcd0; }
        .rows .key { width: 42mm; color: #7a645c; font-size: 10px; }
        .rows .value { font-size: 11px; font-weight: bold; }
        .sub { display: block; font-size: 9px; font-weight: normal; color: #7a645c; }
        .amount { margin-top: 6mm; background: #fdf0e6; border-left: 4px solid #911b1d; }
        .amount td { padding: 5mm 6mm; vertical-align: middle; }
        .words { margin: 3px 0 0; font-size: 10.5px; font-style: italic; }
        .value-big { font-size: 21px; font-weight: bold; color: #911b1d; text-align: right; white-space: nowrap; }
        .sign { margin-top: 10mm; }
        .sign td { vertical-align: bottom; }
        .stamp { display: inline-block; padding: 5px 16px 4px; border: 2.5px solid #15803d; border-radius: 8px; color: #15803d; text-align: center; transform: rotate(-8deg); }
        .stamp-word { display: block; font-size: 21px; font-weight: bold; letter-spacing: 6px; line-height: 1.1; }
        .stamp-date { display: block; font-size: 8px; font-weight: bold; letter-spacing: .6px; }
        .sign-box { width: 58mm; text-align: center; font-size: 10px; }
        .sign-space { height: 20mm; }
        .sign-name { padding-top: 4px; border-top: 1px solid #2b1d19; font-weight: bold; }
        .thanks { margin: 8mm 0 0; font-family: "DejaVu Serif", serif; font-size: 13.5px; font-weight: bold; color: #911b1d; }
        .thanks-note { margin: 2px 0 0; font-size: 9px; color: #7a645c; }
        .foot { position: fixed; bottom: -14mm; left: 0; right: 0; padding-top: 3mm; border-top: 1px solid #ecdcd0; font-size: 9.5px; color: #7a645c; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="topbar"></div>
    <img class="watermark" src="{{ public_path('img/b2b/pdf/budiman-logogram-peach.png') }}" alt="">

    <table class="foot">
        <tr>
            <td>{{ $profile['instagram'] }}</td>
            <td style="text-align: center;">{{ $profile['phone'] }}</td>
            <td class="right">{{ $profile['email'] }}</td>
        </tr>
    </table>

    <table class="head">
        <tr>
            <td style="width: 25mm;"><img src="{{ public_path('img/b2b/pdf/budiman-logo-pt.png') }}" style="width: 21mm;" alt=""></td>
            <td>
                <p class="brand">{{ $profile['brand_name'] }}</p>
                <p class="address">{{ $profile['address'] }}</p>
            </td>
            <td class="right" style="width: 44mm;">
                <p class="doc-title">KWITANSI</p>
                <p class="doc-subtitle">BUKTI PEMBAYARAN</p>
            </td>
        </tr>
    </table>

    <div class="meta-wrap">
        <table class="meta">
            <tr>
                <td><span class="label">NO. KWITANSI</span><strong>{{ $receiptNumber }}</strong></td>
                <td><span class="label">TANGGAL</span><strong>{{ $longDate($receivedAt) }}</strong></td>
                <td><span class="label">NO. INVOICE</span><strong>{{ $invoice->invoice_number }}</strong></td>
            </tr>
        </table>
    </div>

    <table class="rows">
        <tr>
            <td class="key">Telah diterima dari</td>
            <td class="value">{{ $payer }}@if ($payer !== $customer?->name)<span class="sub">{{ $customer?->name }}</span>@endif</td>
        </tr>
        <tr>
            <td class="key">Untuk pembayaran</td>
            <td class="value">Pembayaran Invoice {{ $invoice->invoice_number }} – Pesanan {{ $invoice->order?->order_number }}</td>
        </tr>
        @if ($payments->isNotEmpty())
            <tr>
                <td class="key">Metode pembayaran</td>
                <td class="value">
                    @foreach ($payments as $payment)
                        <span @class(['sub' => ! $loop->first])>{{ $payment->method_name }} · {{ $payment->paid_at?->format('d/m/Y') }}{{ $payment->reference ? ' · Ref. '.$payment->reference : '' }}</span>
                    @endforeach
                </td>
            </tr>
        @endif
    </table>

    <table class="amount">
        <tr>
            <td>
                <span class="label">TERBILANG</span>
                <p class="words">{{ \App\Support\B2b\Terbilang::rupiah($amount) }}</p>
            </td>
            <td class="value-big">Rp {{ number_format($amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="sign">
        <tr>
            <td style="padding-left: 3mm;">
                <div class="stamp">
                    <span class="stamp-word">LUNAS</span>
                    <span class="stamp-date">{{ strtoupper($receivedAt?->format('d M Y')) }}</span>
                </div>
            </td>
            <td class="sign-box">
                <div>Diterima oleh,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $profile['brand_name'] }}</div>
            </td>
        </tr>
    </table>

    <p class="thanks">Terima kasih atas pembayaran Anda.</p>
    <p class="thanks-note">Kwitansi ini sah sebagai bukti pembayaran invoice di atas.</p>
</body>
</html>
