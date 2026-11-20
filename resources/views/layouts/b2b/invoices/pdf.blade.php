<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    @php
        // Versi PDF (dompdf) dari print.blade.php untuk lampiran reminder. dompdf tidak mendukung flex/grid, jadi layout memakai tabel.
        extract(\App\Support\B2b\InvoiceDocument::data($invoice)); // $order, $immediate, $void, $paid, $payments, $termDays, $lines, $showBalance, $emptyLines
        $rupiah = fn ($value) => 'Rp. '.number_format((int) $value, 0, ',', '.');
        $date = fn ($value) => $value?->format('d M Y');
    @endphp
    <style>
        @page { size: A4; margin: 18mm 19mm 16mm; }
        body { margin: 0; color: #111; font-family: "DejaVu Sans", sans-serif; font-size: 11px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .watermark { position: fixed; top: 78mm; left: 30mm; width: 112mm; opacity: .45; z-index: -1; }
        .brand { margin: 0 0 3px; font-family: "DejaVu Serif", serif; font-size: 26px; font-weight: bold; line-height: 1.1; }
        .address { margin: 0; font-size: 9px; color: #333; }
        .meta { margin: 8mm 0 5mm; font-size: 10.5px; }
        .meta td { padding: 1px 0; }
        .right { text-align: right; }
        .parties { border: 1.2px solid #111; }
        .parties td { width: 50%; padding: 6mm 8mm; }
        .parties h2 { margin: 0 0 6px; font-size: 12px; }
        .parties p { margin: 0 0 3px; font-size: 11.5px; }
        .small { font-size: 9.5px; color: #555; }
        .transfer-note { margin-top: 6px; padding-top: 5px; border-top: 1px dashed #d4d4d4; font-size: 9px; color: #333; }
        .items { margin-top: 8mm; font-size: 11.5px; }
        .items th { padding: 0 0 8px; text-align: left; border-bottom: 1px solid #d4d4d4; }
        .items td { height: 30px; padding: 7px 0; border-bottom: 1px solid #d4d4d4; }
        .items .desc { padding-left: 10px; }
        .c-price { width: 26%; text-align: center; }
        .c-qty { width: 14%; text-align: center; }
        .c-total { width: 22%; text-align: right; }
        .items th.c-price, .items th.c-qty { text-align: center; }
        .items th.c-total { text-align: right; }
        .note { display: block; margin-top: 2px; font-size: 9px; color: #555; }
        .totals td { height: 30px; padding: 7px 0; border-bottom: 1px solid #d4d4d4; font-size: 11.5px; }
        .totals .sub td { height: 22px; font-size: 10px; color: #555; }
        .closing { margin-top: 6mm; }
        .terms h3 { margin: 0 0 4px; font-size: 12px; }
        .terms p { margin: 0; font-size: 11.5px; }
        .thanks { margin-top: 5mm !important; font-weight: bold; }
        .grand { margin-top: 14px; font-size: 13px; font-weight: bold; }
        .stamp { display: inline-block; margin-top: 12px; padding: 4px 12px; border: 2.5px solid; border-radius: 6px; font-size: 17px; font-weight: bold; letter-spacing: 2px; }
        .stamp--paid { color: #15803d; border-color: #15803d; }
        .stamp--void { color: #b91c1c; border-color: #b91c1c; }
        .foot { position: fixed; bottom: 0; left: 0; right: 0; padding-top: 4mm; border-top: 1px solid #d4d4d4; font-size: 10px; }
    </style>
</head>
<body>
    <img class="watermark" src="{{ public_path('img/b2b/pdf/budiman-logogram-peach.png') }}" alt="">

    <table>
        <tr>
            <td style="width: 30mm; vertical-align: middle;"><img src="{{ public_path('img/b2b/pdf/budiman-logo-pt.png') }}" style="width: 26mm;" alt=""></td>
            <td style="vertical-align: middle;">
                <p class="brand">{{ $profile['brand_name'] }}</p>
                <p class="address">{{ $profile['address'] }}</p>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td><strong>Invoice #{{ $invoice->invoice_number }}</strong></td>
            @if ($immediate)
                <td class="right"><strong>Order No:</strong> {{ $order->order_number }}</td>
            @else
                <td class="right"><strong>Due Date:</strong> {{ $date($invoice->due_date) }}</td>
            @endif
        </tr>
        <tr>
            <td><strong>Invoice Date:</strong> {{ $date($invoice->issue_date) }}</td>
            <td class="right">@unless ($immediate)<strong>Order No:</strong> {{ $order->order_number }}@endunless</td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <h2>Invoice to:</h2>
                <p>{{ $invoice->customer?->name }}</p>
                @if (!empty($invoice->billing_address_snapshot['full_address']))
                    <p class="small">{{ $invoice->billing_address_snapshot['full_address'] }}</p>
                @endif
            </td>
            <td>
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
            </td>
        </tr>
    </table>

    <table class="items">
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
                    <td class="desc">{{ $line['description'] }}@if ($line['note'])<span class="note">{{ $line['note'] }}</span>@endif</td>
                    <td class="c-price">{{ $rupiah($line['price']) }}</td>
                    <td class="c-qty">{{ number_format($line['qty'], 0, ',', '.') }}</td>
                    <td class="c-total">{{ $rupiah($line['total']) }}</td>
                </tr>
            @endforeach
            @for ($i = 0; $i < $emptyLines; $i++)
                <tr><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    <table>
        <tr>
            <td style="width: 61%;"></td>
            <td>
                <table class="totals">
                    <tr><td>Total</td><td class="right">{{ $rupiah($invoice->total_to_pay) }}</td></tr>
                    @if ($showBalance)
                        <tr class="sub"><td>Paid</td><td class="right">{{ $rupiah($invoice->paid_amount) }}</td></tr>
                        <tr class="sub"><td>Balance due</td><td class="right">{{ $rupiah($invoice->outstandingAmount()) }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="closing">
        <tr>
            <td class="terms" style="width: 58%; padding-right: 8mm;">
                <h3>Payment Terms :</h3>
                @if ($void)
                    <p>This invoice has been voided{{ $order->cancel_reason ? ' (order cancelled: '.$order->cancel_reason.')' : '' }}.</p>
                @elseif ($immediate || $paid)
                    <p>Paid in full{{ $invoice->paid_at ? ' on '.$date($invoice->paid_at) : '' }}.</p>
                @else
                    <p>Payment must be made within {{ $termDays }} calendar {{ $termDays === 1 ? 'day' : 'days' }} from the date of invoice issuance.</p>
                @endif
                @if ($order->notes)
                    <p class="small" style="margin-top: 4mm;">Notes: {{ $order->notes }}</p>
                @endif
                <p class="thanks">Thank you for your business</p>
            </td>
            <td>
                <table class="grand"><tr><td>GRAND TOTAL</td><td class="right">{{ $rupiah($invoice->total_to_pay) }}</td></tr></table>
                @if ($paid)
                    <span class="stamp stamp--paid">PAID</span>
                @elseif ($void)
                    <span class="stamp stamp--void">VOID</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="foot">
        <tr>
            <td>{{ $profile['instagram'] }}</td>
            <td style="text-align: center;">{{ $profile['phone'] }}</td>
            <td class="right">{{ $profile['email'] }}</td>
        </tr>
    </table>
</body>
</html>
