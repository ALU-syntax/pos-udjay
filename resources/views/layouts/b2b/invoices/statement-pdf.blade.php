<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php
        // Versi PDF (dompdf) dari statement.blade.php. dompdf tidak mendukung flex/grid, jadi layout memakai tabel.
        /** @var \App\Support\B2b\CustomerStatement $statement */
        $customer = $statement->customer;
        $rupiah = fn ($value) => 'Rp. '.number_format((int) $value, 0, ',', '.');
        $number = fn ($value) => number_format((int) $value, 0, ',', '.');
        $date = fn ($value) => $value?->format('d M Y');
    @endphp
    <title>Statement of Account {{ $customer->name }}</title>
    <style>
        /* Margin bawah lebih lebar: footer tetap (position: fixed) berada di area margin, tidak menimpa tabel panjang. */
        @page { size: A4; margin: 18mm 17mm 24mm; }
        body { margin: 0; color: #111; font-family: "DejaVu Sans", sans-serif; font-size: 10.5px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .watermark { position: fixed; top: 78mm; left: 32mm; width: 112mm; opacity: .45; z-index: -1; }
        .brand { margin: 0 0 3px; font-family: "DejaVu Serif", serif; font-size: 26px; font-weight: bold; line-height: 1.1; }
        .address { margin: 0; font-size: 9px; color: #333; }
        .title { margin: 7mm 0 1mm; font-size: 16px; font-weight: bold; }
        .meta { margin: 0 0 5mm; font-size: 10.5px; }
        .meta td { padding: 1px 0; }
        .right { text-align: right; }
        .parties { border: 1.2px solid #111; }
        .parties td { width: 50%; padding: 5mm 7mm; }
        .parties h2 { margin: 0 0 5px; font-size: 11.5px; }
        .parties p { margin: 0 0 2px; font-size: 11px; }
        .small { font-size: 9px; color: #555; }
        .transfer-note { margin-top: 6px; padding-top: 5px; border-top: 1px dashed #d4d4d4; font-size: 9px; color: #333; }
        .rows { margin-top: 7mm; font-size: 9.5px; }
        .rows th { padding: 0 3px 7px; text-align: left; border-bottom: 1px solid #111; white-space: nowrap; }
        .rows td { padding: 6px 3px; border-bottom: 1px solid #d4d4d4; }
        .rows th:first-child, .rows td:first-child { padding-left: 0; }
        .rows th:last-child, .rows td:last-child { padding-right: 0; }
        .rows tr { page-break-inside: avoid; }
        .rows .num { text-align: right; white-space: nowrap; }
        .rows .nowrap { white-space: nowrap; }
        .rows .sub { display: block; font-size: 8px; color: #555; }
        .late { color: #b91c1c; font-weight: bold; }
        .empty { padding: 12mm 0; text-align: center; color: #555; font-size: 11px; }
        .summary { margin-top: 5mm; page-break-inside: avoid; }
        .terms h3 { margin: 0 0 4px; font-size: 11.5px; }
        .terms p { margin: 0 0 4px; font-size: 9.5px; color: #333; }
        .thanks { margin-top: 4mm !important; font-size: 11px !important; font-weight: bold; color: #111 !important; }
        .totals td { padding: 5px 0; border-bottom: 1px solid #d4d4d4; font-size: 10.5px; }
        .totals .late td { color: #b91c1c; }
        .grand td { padding-top: 9px; font-size: 13px; font-weight: bold; white-space: nowrap; }
        .foot { position: fixed; bottom: -14mm; left: 0; right: 0; padding-top: 3mm; border-top: 1px solid #d4d4d4; font-size: 9.5px; }
    </style>
</head>
<body>
    <img class="watermark" src="{{ public_path('img/b2b/pdf/budiman-logogram-peach.png') }}" alt="">

    <table class="foot">
        <tr>
            <td>{{ $profile['instagram'] }}</td>
            <td style="text-align: center;">{{ $profile['phone'] }}</td>
            <td class="right">{{ $profile['email'] }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width: 30mm; vertical-align: middle;"><img src="{{ public_path('img/b2b/pdf/budiman-logo-pt.png') }}" style="width: 26mm;" alt=""></td>
            <td style="vertical-align: middle;">
                <p class="brand">{{ $profile['brand_name'] }}</p>
                <p class="address">{{ $profile['address'] }}</p>
            </td>
        </tr>
    </table>

    <p class="title">Statement of Account</p>
    <table class="meta">
        <tr>
            <td><strong>Statement Date:</strong> {{ $date($statement->date) }}</td>
            <td class="right"><strong>Customer Code:</strong> {{ $customer->code }}</td>
        </tr>
        <tr>
            <td><strong>Unpaid Invoices:</strong> {{ $statement->rows->count() }}</td>
            <td></td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <h2>Statement to:</h2>
                <p>{{ $customer->name }}</p>
                @if ($customer->contact_name)
                    <p class="small">Attn. {{ $customer->contact_name }}</p>
                @endif
                @if ($customer->hasAddress())
                    <p class="small">{{ $customer->fullAddress() }}</p>
                @endif
            </td>
            <td>
                <h2>Payment Method:</h2>
                <p>Bank Name: {{ $profile['bank_name'] }}</p>
                <p>Account No: {{ $profile['bank_account_number'] }} ({{ $profile['bank_account_name'] }})</p>
                @if ($statement->rows->isNotEmpty())
                    <p class="transfer-note">Each invoice is paid separately. Please write the <strong>invoice number(s)</strong> you are paying in the transfer description so we can match your payment.</p>
                @endif
            </td>
        </tr>
    </table>

    @if ($statement->rows->isEmpty())
        <p class="empty">There are no unpaid invoices as of {{ $date($statement->date) }}. Thank you.</p>
    @else
        <table class="rows">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th>Invoice No.</th>
                    <th>Invoice Date</th>
                    <th>Due Date</th>
                    <th class="num">Total (Rp)</th>
                    <th class="num">Paid (Rp)</th>
                    <th class="num">Balance (Rp)</th>
                    <th class="num">Overdue</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($statement->rows as $index => $row)
                    @php($invoice = $row['invoice'])
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="nowrap">{{ $invoice->invoice_number }}<span class="sub">Order {{ $invoice->order?->order_number }}</span></td>
                        <td class="nowrap">{{ $date($invoice->issue_date) }}</td>
                        <td class="nowrap {{ $row['overdue_days'] ? 'late' : '' }}">{{ $date($invoice->due_date) }}</td>
                        <td class="num">{{ $number($invoice->total_to_pay) }}</td>
                        <td class="num">{{ $invoice->paid_amount ? $number($invoice->paid_amount) : '-' }}</td>
                        <td class="num"><strong>{{ $number($row['outstanding']) }}</strong></td>
                        <td class="num {{ $row['overdue_days'] ? 'late' : '' }}">{{ $row['overdue_days'] ? $row['overdue_days'].' '.($row['overdue_days'] === 1 ? 'day' : 'days') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td class="terms" style="width: 52%; padding-right: 8mm;">
                    <h3>Notes :</h3>
                    <p>This statement lists all unpaid invoices as of {{ $date($statement->date) }}. Payments made after this date may not be reflected yet.</p>
                    <p>If you have already paid, please disregard this statement or send us the proof of payment.</p>
                    <p class="thanks">Thank you for your business</p>
                </td>
                <td>
                    <table class="totals">
                        <tr><td>Total invoices</td><td class="right">{{ $rupiah($statement->total()) }}</td></tr>
                        <tr><td>Paid</td><td class="right">{{ $rupiah($statement->paid()) }}</td></tr>
                        @if ($statement->overdueCount())
                            <tr class="late"><td>Overdue ({{ $statement->overdueCount() }} {{ $statement->overdueCount() === 1 ? 'invoice' : 'invoices' }})</td><td class="right">{{ $rupiah($statement->overdueBalance()) }}</td></tr>
                        @endif
                    </table>
                    <table class="grand"><tr><td>TOTAL BALANCE DUE</td><td class="right">{{ $rupiah($statement->balance()) }}</td></tr></table>
                </td>
            </tr>
        </table>
    @endif
</body>
</html>
