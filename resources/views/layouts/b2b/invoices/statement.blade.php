<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        // Rekap tagihan (Statement of Account) bergaya template invoice Budiman (bahasa Inggris, sama dengan invoice ke customer).
        /** @var \App\Support\B2b\CustomerStatement $statement */
        $customer = $statement->customer;
        $rupiah = fn ($value) => 'Rp. '.number_format((int) $value, 0, ',', '.');
        $date = fn ($value) => $value?->format('d M Y');
        $number = fn ($value) => number_format((int) $value, 0, ',', '.'); // angka tabel tanpa "Rp." supaya 8 kolom muat di A4
    @endphp
    <title>Statement of Account {{ $customer->name }} {{ $statement->date->format('Y-m-d') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya:wght@800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #111; --muted: #555; --line: #d4d4d4; --late: #b91c1c; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: var(--ink); font: 15px/1.45 "Inter", -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: .5rem; padding: 12px 16px; }
        .toolbar button, .toolbar a { padding: 10px 18px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: var(--ink); font: inherit; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .toolbar button.primary { background: #1572e8; border-color: #1572e8; color: #fff; }

        .sheet { position: relative; width: 210mm; max-width: 100%; min-height: 297mm; margin: 0 auto 24px; padding: 20mm 18mm 14mm; overflow: hidden; background: #fff; box-shadow: 0 4px 24px rgba(15, 23, 42, .1); display: flex; flex-direction: column; }
        .sheet > * { position: relative; z-index: 1; }
        .watermark { position: absolute; z-index: 0; top: 50%; left: 50%; width: 112mm; transform: translate(-50%, -42%); opacity: .55; pointer-events: none; }

        .head { display: flex; align-items: center; gap: 18px; }
        .head__logo { width: 33mm; flex-shrink: 0; }
        .head__brand { margin: 0 0 4px; font-family: "Alegreya", Georgia, "Times New Roman", serif; font-size: 40px; font-weight: 800; line-height: 1.05; letter-spacing: .005em; }
        .head__address { margin: 0; max-width: 125mm; font-size: 12.5px; line-height: 1.45; color: #333; }

        .title { margin: 9mm 0 1mm; font-size: 22px; font-weight: 800; letter-spacing: .02em; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin: 0 0 6mm; font-size: 14px; }
        .meta strong { font-weight: 700; }
        .meta .is-right { text-align: right; }

        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding: 7mm 9mm; border: 1.5px solid var(--ink); }
        .parties h2 { margin: 0 0 8px; font-size: 15px; font-weight: 700; }
        .parties p { margin: 0 0 3px; font-size: 15px; line-height: 1.5; }
        .parties .small { font-size: 12.5px; color: var(--muted); }
        .parties .transfer-note { margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--line); font-size: 12.5px; line-height: 1.45; color: #333; }

        table { width: 100%; margin-top: 8mm; border-collapse: collapse; font-size: 12px; font-variant-numeric: tabular-nums; }
        th { padding: 0 4px 10px; font-weight: 700; text-align: left; border-bottom: 1px solid var(--ink); white-space: nowrap; }
        td { padding: 9px 4px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th:first-child, td:first-child { padding-left: 0; }
        th:last-child, td:last-child { padding-right: 0; }
        .c-no { width: 4%; }
        .c-date { white-space: nowrap; }
        .c-money { text-align: right; white-space: nowrap; }
        .c-late { text-align: right; white-space: nowrap; }
        td .sub { display: block; margin-top: 1px; font-size: 10.5px; color: var(--muted); white-space: nowrap; }
        td.is-late { color: var(--late); font-weight: 600; }
        .empty { padding: 14mm 0; text-align: center; color: var(--muted); font-size: 15px; }

        .summary { display: grid; grid-template-columns: 1fr minmax(44%, auto); gap: 24px; margin-top: 6mm; align-items: start; }
        .terms h3 { margin: 0 0 6px; font-size: 15px; font-weight: 700; }
        .terms p { margin: 0 0 6px; font-size: 13px; line-height: 1.5; color: #333; }
        .terms .thanks { margin-top: 5mm; font-size: 15px; font-weight: 700; color: var(--ink); }
        .totals__row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--line); font-size: 14px; }
        .totals__row.is-late { color: var(--late); }
        .grand { display: flex; justify-content: space-between; gap: 12px; margin-top: 14px; font-size: 18px; font-weight: 800; white-space: nowrap; }

        .foot { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 6px 16px; margin-top: auto; padding-top: 6mm; border-top: 1px solid var(--line); font-size: 14px; }
        .spacer { flex: 1 0 8mm; }

        @media screen and (max-width: 760px) {
            .sheet { min-height: 0; padding: 20px; }
            .head { flex-direction: column; align-items: flex-start; }
            .head__brand { font-size: 30px; }
            .parties, .summary, .meta { grid-template-columns: 1fr; }
            .meta .is-right { text-align: left; }
            .table-wrap { overflow-x: auto; }
        }
        @media print {
            @page { size: A4; margin: 0; }
            body { background: #fff; }
            .toolbar { display: none; }
            /* Minimal satu halaman A4; daftar panjang lanjut ke halaman berikutnya, baris tidak terpotong. */
            .sheet { width: 210mm; min-height: 297mm; margin: 0; box-shadow: none; }
            tr { break-inside: avoid; }
            thead { display: table-header-group; }
            .watermark { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
            <img class="head__logo" src="{{ asset('img/b2b/budiman-logo-pt.png') }}" alt="Logo {{ $profile['brand_name'] }}">
            <div>
                <h1 class="head__brand">{{ $profile['brand_name'] }}</h1>
                <p class="head__address">{{ $profile['address'] }}</p>
            </div>
        </header>

        <h2 class="title">Statement of Account</h2>
        <section class="meta">
            <div><strong>Statement Date:</strong> {{ $date($statement->date) }}</div>
            <div class="is-right"><strong>Customer Code:</strong> {{ $customer->code }}</div>
            <div><strong>Unpaid Invoices:</strong> {{ $statement->rows->count() }}</div>
        </section>

        <section class="parties">
            <div>
                <h2>Statement to:</h2>
                <p>{{ $customer->name }}</p>
                @if ($customer->contact_name)
                    <p class="small">Attn. {{ $customer->contact_name }}</p>
                @endif
                @if ($customer->hasAddress())
                    <p class="small">{{ $customer->fullAddress() }}</p>
                @endif
            </div>
            <div>
                <h2>Payment Method:</h2>
                <p>Bank Name: {{ $profile['bank_name'] }}</p>
                <p>Account No: {{ $profile['bank_account_number'] }} ({{ $profile['bank_account_name'] }})</p>
                @if ($statement->rows->isNotEmpty())
                    <p class="transfer-note">Each invoice is paid separately. Please write the <strong>invoice number(s)</strong> you are paying in the transfer description so we can match your payment.</p>
                @endif
            </div>
        </section>

        @if ($statement->rows->isEmpty())
            <p class="empty">There are no unpaid invoices as of {{ $date($statement->date) }}. Thank you.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="c-no">No</th>
                            <th>Invoice No.</th>
                            <th class="c-date">Invoice Date</th>
                            <th class="c-date">Due Date</th>
                            <th class="c-money">Total (Rp)</th>
                            <th class="c-money">Paid (Rp)</th>
                            <th class="c-money">Balance (Rp)</th>
                            <th class="c-late">Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($statement->rows as $index => $row)
                            @php($invoice = $row['invoice'])
                            <tr>
                                <td class="c-no">{{ $index + 1 }}</td>
                                <td>{{ $invoice->invoice_number }}<span class="sub">Order {{ $invoice->order?->order_number }}</span></td>
                                <td class="c-date">{{ $date($invoice->issue_date) }}</td>
                                <td class="c-date {{ $row['overdue_days'] ? 'is-late' : '' }}">{{ $date($invoice->due_date) }}</td>
                                <td class="c-money">{{ $number($invoice->total_to_pay) }}</td>
                                <td class="c-money">{{ $invoice->paid_amount ? $number($invoice->paid_amount) : '-' }}</td>
                                <td class="c-money"><strong>{{ $number($row['outstanding']) }}</strong></td>
                                <td class="c-late {{ $row['overdue_days'] ? 'is-late' : '' }}">{{ $row['overdue_days'] ? $row['overdue_days'].' '.($row['overdue_days'] === 1 ? 'day' : 'days') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <section class="summary">
                <div class="terms">
                    <h3>Notes :</h3>
                    <p>This statement lists all unpaid invoices as of {{ $date($statement->date) }}. Payments made after this date may not be reflected yet.</p>
                    <p>If you have already paid, please disregard this statement or send us the proof of payment.</p>
                    <p class="thanks">Thank you for your business</p>
                </div>
                <div>
                    <div class="totals__row"><span>Total invoices</span><span>{{ $rupiah($statement->total()) }}</span></div>
                    <div class="totals__row"><span>Paid</span><span>{{ $rupiah($statement->paid()) }}</span></div>
                    @if ($statement->overdueCount())
                        <div class="totals__row is-late"><span>Overdue ({{ $statement->overdueCount() }} {{ $statement->overdueCount() === 1 ? 'invoice' : 'invoices' }})</span><span>{{ $rupiah($statement->overdueBalance()) }}</span></div>
                    @endif
                    <div class="grand"><span>TOTAL BALANCE DUE</span><span>{{ $rupiah($statement->balance()) }}</span></div>
                </div>
            </section>
        @endif

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
