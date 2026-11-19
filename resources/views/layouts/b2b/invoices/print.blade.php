<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    @php
        // Template invoice Budiman Roastery (bahasa Inggris). Identitas penjual dari B2bSettings::invoiceProfile().
        $order = $invoice->order;
        $rupiah = fn ($value) => 'Rp. '.number_format((int) $value, 0, ',', '.');
        $date = fn ($value) => $value?->format('d M Y');
        $immediate = $order->payment_type === \App\Models\B2b\Order::PAYMENT_IMMEDIATE;
        $void = $invoice->status === \App\Models\B2b\Invoice::STATUS_VOID;
        $paid = $invoice->status === \App\Models\B2b\Invoice::STATUS_PAID;
        $payments = $invoice->allocations->pluck('payment')->filter()->where('status', 'posted')->unique('id')->values();
        $termDays = $invoice->issue_date->diffInDays($invoice->due_date);
        // Baris tagihan: item pesanan, lalu ongkir dan kode unik kalau ada.
        $lines = $order->items->map(fn ($item) => [
            'description' => $item->displayName(),
            'note' => $item->notes,
            'price' => $item->final_price,
            'qty' => $item->qty,
            'total' => $item->line_total,
        ]);
        if ($order->shipping_cost) {
            $lines->push(['description' => 'Ongkir', 'note' => null, 'price' => $order->shipping_cost, 'qty' => 1, 'total' => $order->shipping_cost]);
        }
        if ($invoice->unique_code_amount) {
            $lines->push(['description' => 'Unique code', 'note' => null, 'price' => $invoice->unique_code_amount, 'qty' => 1, 'total' => $invoice->unique_code_amount]);
        }
        $showBalance = $invoice->paid_amount && ! $paid && ! $void;
        // Baris kosong pengisi seperti template; dikurangi kalau ada baris Paid/Balance supaya tetap satu halaman.
        $emptyLines = max(0, ($showBalance ? 2 : 3) - $lines->count());
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya:wght@800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #111; --muted: #555; --line: #d4d4d4; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: var(--ink); font: 15px/1.45 "Inter", -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: .5rem; padding: 12px 16px; }
        .toolbar button, .toolbar a { padding: 10px 18px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: var(--ink); font: inherit; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .toolbar button.primary { background: #1572e8; border-color: #1572e8; color: #fff; }

        .sheet { position: relative; width: 210mm; max-width: 100%; min-height: 297mm; margin: 0 auto 24px; padding: 20mm 21mm 14mm; overflow: hidden; background: #fff; box-shadow: 0 4px 24px rgba(15, 23, 42, .1); display: flex; flex-direction: column; }
        .sheet > * { position: relative; z-index: 1; }
        .watermark { position: absolute; z-index: 0; top: 50%; left: 50%; width: 112mm; transform: translate(-50%, -42%); opacity: .55; pointer-events: none; }

        .head { display: flex; align-items: center; gap: 18px; }
        .head__logo { width: 33mm; flex-shrink: 0; }
        .head__brand { margin: 0 0 4px; font-family: "Alegreya", Georgia, "Times New Roman", serif; font-size: 40px; font-weight: 800; line-height: 1.05; letter-spacing: .005em; }
        .head__address { margin: 0; max-width: 125mm; font-size: 12.5px; line-height: 1.45; color: #333; }

        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin: 9mm 0 6mm; font-size: 14px; }
        .meta strong { font-weight: 700; }
        .meta .is-right { text-align: right; }

        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding: 8mm 11mm; border: 1.5px solid var(--ink); }
        .parties h2 { margin: 0 0 10px; font-size: 16px; font-weight: 700; }
        .parties p { margin: 0 0 4px; font-size: 16px; line-height: 1.5; }
        .parties .small { font-size: 13px; color: var(--muted); }
        .parties .transfer-note { margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--line); font-size: 12.5px; line-height: 1.45; color: #333; }
        .parties .transfer-note strong { white-space: nowrap; }

        table { width: 100%; margin-top: 10mm; border-collapse: collapse; font-size: 16px; }
        th { padding: 0 0 12px; font-weight: 700; text-align: left; border-bottom: 1px solid var(--line); }
        td { height: 46px; padding: 9px 0; border-bottom: 1px solid var(--line); vertical-align: top; }
        th:first-child, td:first-child { padding-left: 0; }
        th.desc { padding-left: 14px; }
        .c-price { width: 26%; text-align: center; }
        .c-qty { width: 14%; text-align: center; }
        .c-total { width: 22%; text-align: right; padding-right: 0; }
        td .note { display: block; margin-top: 2px; font-size: 12px; color: var(--muted); }

        .totals { display: grid; grid-template-columns: 1fr 39%; margin-top: 0; }
        .totals__row { display: flex; justify-content: space-between; align-items: center; height: 46px; border-bottom: 1px solid var(--line); font-size: 16px; }
        .totals__row.is-sub { font-size: 14px; color: var(--muted); height: 34px; }

        .closing { display: grid; grid-template-columns: 1fr minmax(39%, auto); gap: 24px; margin-top: 7mm; align-items: start; }
        .terms h3 { margin: 0 0 6px; font-size: 16px; font-weight: 700; }
        .terms p { margin: 0; max-width: 72mm; font-size: 16px; line-height: 1.5; }
        .terms .thanks { margin-top: 6mm; font-weight: 700; }
        .terms .notes { margin-top: 5mm; font-size: 13px; color: var(--muted); }
        .grand { display: flex; justify-content: space-between; gap: 12px; margin-top: 22px; font-size: 18px; font-weight: 800; white-space: nowrap; }

        .stamp { display: inline-block; margin-top: 18px; padding: 6px 16px; border: 3px solid currentColor; border-radius: 8px; font-size: 22px; font-weight: 800; letter-spacing: .14em; transform: rotate(-8deg); }
        .stamp--paid { color: #15803d; }
        .stamp--void { color: #b91c1c; }
        .stamp-note { margin-top: 10px; font-size: 12px; color: var(--muted); white-space: normal; }

        .foot { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 6px 16px; margin-top: auto; padding-top: 6mm; border-top: 1px solid var(--line); font-size: 14px; }
        .spacer { flex: 1 0 8mm; }

        @media screen and (max-width: 760px) {
            .sheet { min-height: 0; padding: 20px; }
            .head { flex-direction: column; align-items: flex-start; }
            .head__brand { font-size: 30px; }
            .parties, .closing, .totals { grid-template-columns: 1fr; }
            .totals > :first-child { display: none; }
            table { font-size: 14px; }
        }
        @media print {
            @page { size: A4; margin: 0; }
            body { background: #fff; }
            .toolbar { display: none; }
            /* Minimal satu halaman A4; pesanan dengan banyak item lanjut ke halaman berikutnya, tidak terpotong. */
            .sheet { width: 210mm; min-height: 297mm; margin: 0; box-shadow: none; }
            .watermark { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route('b2b/invoices/excel', $invoice->id) }}">Unduh Excel</a>
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

        <section class="meta">
            <div><strong>Invoice #{{ $invoice->invoice_number }}</strong></div>
            @if ($immediate)
                <div class="is-right"><strong>Order No:</strong> {{ $order->order_number }}</div>
                <div><strong>Invoice Date:</strong> {{ $date($invoice->issue_date) }}</div>
            @else
                <div class="is-right"><strong>Due Date:</strong> {{ $date($invoice->due_date) }}</div>
                <div><strong>Invoice Date:</strong> {{ $date($invoice->issue_date) }}</div>
                <div class="is-right"><strong>Order No:</strong> {{ $order->order_number }}</div>
            @endif
        </section>

        <section class="parties">
            <div>
                <h2>Invoice to:</h2>
                <p>{{ $invoice->customer?->name }}</p>
                @if (!empty($invoice->billing_address_snapshot['full_address']))
                    <p class="small">{{ $invoice->billing_address_snapshot['full_address'] }}</p>
                @endif
            </div>
            <div>
                <h2>Payment Method:</h2>
                @if ($immediate && $payments->isNotEmpty())
                    @foreach ($payments as $payment)
                        <p>{{ $payment->method_name }}</p>
                        <p class="small">{{ $payment->payment_number }} · {{ $date($payment->paid_at) }}{{ $payment->reference ? ' · Ref. '.$payment->reference : '' }}</p>
                    @endforeach
                @else
                    <p>Bank Name: {{ $profile['bank_name'] }}</p>
                    <p>Account No: {{ $profile['bank_account_number'] }} ({{ $profile['bank_account_name'] }})</p>
                    @unless ($void || $paid)
                        <p class="transfer-note">Please write <strong>{{ $invoice->invoice_number }}</strong> in the transfer description so we can match your payment.</p>
                    @endunless
                @endif
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th class="desc">Description</th>
                    <th class="c-price">Price</th>
                    <th class="c-qty">QTY</th>
                    <th class="c-total">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td>{{ $line['description'] }}@if ($line['note'])<span class="note">{{ $line['note'] }}</span>@endif</td>
                        <td class="c-price">{{ $rupiah($line['price']) }}</td>
                        <td class="c-qty">{{ number_format($line['qty'], 0, ',', '.') }}</td>
                        <td class="c-total">{{ $rupiah($line['total']) }}</td>
                    </tr>
                @endforeach
                @for ($i = 0; $i < $emptyLines; $i++)
                    <tr aria-hidden="true"><td></td><td></td><td></td><td></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="totals">
            <div></div>
            <div>
                <div class="totals__row"><span>Total</span><span>{{ $rupiah($invoice->total_to_pay) }}</span></div>
                @if ($showBalance)
                    <div class="totals__row is-sub"><span>Paid</span><span>{{ $rupiah($invoice->paid_amount) }}</span></div>
                    <div class="totals__row is-sub"><span>Balance due</span><span>{{ $rupiah($invoice->outstandingAmount()) }}</span></div>
                @endif
            </div>
        </div>

        <section class="closing">
            <div class="terms">
                <h3>Payment Terms :</h3>
                @if ($void)
                    <p>This invoice has been voided{{ $order->cancel_reason ? ' (order cancelled: '.$order->cancel_reason.')' : '' }}.</p>
                @elseif ($immediate || $paid)
                    <p>Paid in full{{ $invoice->paid_at ? ' on '.$date($invoice->paid_at) : '' }}.</p>
                @else
                    <p>Payment must be made within {{ $termDays }} calendar {{ $termDays === 1 ? 'day' : 'days' }} from the date of invoice issuance.</p>
                @endif
                @if ($order->notes)
                    <p class="notes">Notes: {{ $order->notes }}</p>
                @endif
                <p class="thanks">Thank you for your business</p>
            </div>
            <div>
                <div class="grand"><span>GRAND TOTAL</span><span>{{ $rupiah($invoice->total_to_pay) }}</span></div>
                @if ($paid)
                    <span class="stamp stamp--paid">PAID</span>
                    @if (! $immediate && $payments->isNotEmpty())
                        <div class="stamp-note">{{ $payments->map(fn ($payment) => $payment->payment_number.' · '.$payment->method_name)->implode(', ') }}</div>
                    @endif
                @elseif ($void)
                    <span class="stamp stamp--void">VOID</span>
                @endif
            </div>
        </section>

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
