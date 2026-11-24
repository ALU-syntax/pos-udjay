<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        // Kwitansi invoice lunas. Identitas penjual sama dengan invoice (B2bSettings::invoiceProfile()), palet mengikuti logo Budiman.
        $receiptNumber = \App\Support\B2b\InvoiceDocument::receiptNumber($invoice);
        $customer = $invoice->customer;
        $payer = trim((string) $customer?->contact_name) ?: $customer?->name;
        $receivedAt = $invoice->paid_at ?? $invoice->issue_date;
        $payments = $invoice->allocations->pluck('payment')->filter()->where('status', 'posted')->unique('id')->values();
        $longDate = fn ($value) => $value?->locale('id')->translatedFormat('d F Y');
        $amount = (int) $invoice->paid_amount;
    @endphp
    <title>Kwitansi {{ $receiptNumber }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #911b1d; --peach: #fcc49d; --peach-soft: #fdf0e6; --ink: #2b1d19; --muted: #7a645c; --line: #ecdcd0; --paid: #15803d; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: var(--ink); font: 15px/1.5 "Inter", -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: .5rem; padding: 12px 16px; }
        .toolbar button, .toolbar a { padding: 10px 18px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111; font: inherit; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .toolbar button.primary { background: #1572e8; border-color: #1572e8; color: #fff; }

        .sheet { position: relative; display: flex; flex-direction: column; width: 210mm; max-width: 100%; min-height: 297mm; margin: 0 auto 24px; padding: 18mm 20mm 12mm; overflow: hidden; background: #fff; border-top: 8px solid var(--brand); box-shadow: 0 4px 24px rgba(15, 23, 42, .1); }
        .sheet > * { position: relative; z-index: 1; }
        .watermark { position: absolute; z-index: 0; right: -18mm; bottom: 26mm; width: 110mm; opacity: .22; pointer-events: none; }

        /* Kepala surat sama dengan invoice */
        .head { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding-bottom: 7mm; border-bottom: 1px solid var(--line); }
        .head__id { display: flex; align-items: center; gap: 16px; min-width: 0; }
        .head__logo { width: 27mm; flex-shrink: 0; }
        .head__brand { margin: 0 0 3px; font-family: "Alegreya", Georgia, serif; font-size: 30px; font-weight: 800; line-height: 1.05; }
        .head__address { margin: 0; max-width: 92mm; font-size: 11.5px; line-height: 1.45; color: var(--muted); }
        .head__doc { flex-shrink: 0; text-align: right; }
        .head__title { margin: 0; font-size: 30px; font-weight: 700; line-height: 1; letter-spacing: .18em; color: var(--brand); }
        .head__subtitle { margin: 4px 0 0; font-size: 11px; font-weight: 600; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); }

        .meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 7mm 0; }
        .meta div { padding: 9px 12px; border-radius: 8px; background: var(--peach-soft); }
        .meta dt { font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .meta dd { margin: 2px 0 0; font-size: 14px; font-weight: 600; white-space: nowrap; }

        .rows { margin: 0; }
        .rows div { display: grid; grid-template-columns: 46mm 1fr; gap: 12px; padding: 11px 0; border-bottom: 1px dashed var(--line); }
        .rows dt { font-size: 13.5px; color: var(--muted); }
        .rows dd { margin: 0; font-size: 15.5px; font-weight: 600; }
        .rows .sub { display: block; font-size: 12.5px; font-weight: 400; color: var(--muted); }

        .amount { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 8mm; padding: 6mm 7mm; border-left: 6px solid var(--brand); border-radius: 4px 10px 10px 4px; background: var(--peach-soft); }
        .amount__label { margin: 0; font-size: 11px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        .amount__words { margin: 4px 0 0; font-size: 14px; font-style: italic; }
        .amount__value { margin: 0; font-size: 30px; font-weight: 700; line-height: 1.1; white-space: nowrap; color: var(--brand); }

        .sign { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; margin-top: 12mm; }
        .stamp { display: inline-block; padding: 8px 22px 6px; border: 3px solid var(--paid); border-radius: 10px; color: var(--paid); text-align: center; transform: rotate(-8deg); opacity: .9; }
        .stamp__word { display: block; font-size: 30px; font-weight: 700; letter-spacing: .3em; padding-left: .3em; line-height: 1.1; }
        .stamp__date { display: block; font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
        .sign__box { width: 62mm; text-align: center; font-size: 13.5px; }
        .sign__space { height: 24mm; }
        .sign__name { padding-top: 6px; border-top: 1px solid var(--ink); font-weight: 600; }

        .thanks { margin: 10mm 0 0; font-family: "Alegreya", Georgia, serif; font-size: 19px; font-weight: 700; color: var(--brand); }
        .thanks span { display: block; font-family: "Inter", sans-serif; font-size: 12.5px; font-weight: 400; color: var(--muted); }

        .spacer { flex: 1 0 8mm; }
        .foot { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 6px 16px; padding-top: 5mm; border-top: 1px solid var(--line); font-size: 13px; color: var(--muted); }

        @media screen and (max-width: 760px) {
            .sheet { min-height: 0; padding: 20px; }
            .head, .head__id { flex-direction: column; align-items: flex-start; }
            .head__doc { text-align: left; }
            .meta { grid-template-columns: 1fr; }
            .rows div { grid-template-columns: 1fr; gap: 2px; }
            .amount { flex-direction: column-reverse; align-items: flex-start; }
            .amount__value { font-size: 24px; }
            .sign { flex-direction: column; align-items: center; }
        }
        @media print {
            @page { size: A4; margin: 0; }
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: 210mm; min-height: 297mm; margin: 0; box-shadow: none; }
            .sheet, .sheet * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <button type="button" onclick="window.close()">Tutup</button>
    </div>

    <main class="sheet">
        <img class="watermark" src="{{ asset('img/b2b/budiman-logogram-peach.png') }}" alt="">

        <header class="head">
            <div class="head__id">
                <img class="head__logo" src="{{ asset('img/b2b/budiman-logo-pt.png') }}" alt="Logo {{ $profile['brand_name'] }}">
                <div>
                    <h1 class="head__brand">{{ $profile['brand_name'] }}</h1>
                    <p class="head__address">{{ $profile['address'] }}</p>
                </div>
            </div>
            <div class="head__doc">
                <h2 class="head__title">KWITANSI</h2>
                <p class="head__subtitle">Bukti Pembayaran</p>
            </div>
        </header>

        <dl class="meta">
            <div><dt>No. Kwitansi</dt><dd>{{ $receiptNumber }}</dd></div>
            <div><dt>Tanggal</dt><dd>{{ $longDate($receivedAt) }}</dd></div>
            <div><dt>No. Invoice</dt><dd>{{ $invoice->invoice_number }}</dd></div>
        </dl>

        <dl class="rows">
            <div>
                <dt>Telah diterima dari</dt>
                <dd>{{ $payer }}@if ($payer !== $customer?->name)<span class="sub">{{ $customer?->name }}</span>@endif</dd>
            </div>
            <div>
                <dt>Untuk pembayaran</dt>
                <dd>Pembayaran Invoice {{ $invoice->invoice_number }} – Pesanan {{ $invoice->order?->order_number }}</dd>
            </div>
            @if ($payments->isNotEmpty())
                <div>
                    <dt>Metode pembayaran</dt>
                    <dd>
                        @foreach ($payments as $payment)
                            <span @class(['sub' => ! $loop->first])>{{ $payment->method_name }} · {{ $payment->paid_at?->format('d/m/Y') }}{{ $payment->reference ? ' · Ref. '.$payment->reference : '' }}</span>
                        @endforeach
                    </dd>
                </div>
            @endif
        </dl>

        <section class="amount">
            <div>
                <p class="amount__label">Terbilang</p>
                <p class="amount__words">{{ \App\Support\B2b\Terbilang::rupiah($amount) }}</p>
            </div>
            <p class="amount__value">Rp {{ number_format($amount, 0, ',', '.') }}</p>
        </section>

        <section class="sign">
            <div class="stamp">
                <span class="stamp__word">LUNAS</span>
                <span class="stamp__date">{{ $receivedAt?->format('d M Y') }}</span>
            </div>
            <div class="sign__box">
                <div>Diterima oleh,</div>
                <div class="sign__space"></div>
                <div class="sign__name">{{ $profile['brand_name'] }}</div>
            </div>
        </section>

        <p class="thanks">Terima kasih atas pembayaran Anda.<span>Kwitansi ini sah sebagai bukti pembayaran invoice di atas.</span></p>

        <div class="spacer"></div>

        <footer class="foot">
            <span>{{ $profile['instagram'] }}</span>
            @if ($profile['phone'])
                <span>{{ $profile['phone'] }}</span>
            @endif
            <span>{{ $profile['email'] }}</span>
        </footer>
    </main>
</body>
</html>
