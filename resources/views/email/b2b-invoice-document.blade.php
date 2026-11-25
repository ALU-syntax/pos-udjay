{{--
    Email dokumen satu invoice B2B: invoice pesanan tempo, atau kwitansi invoice lunas ($receipt = true).
    Layout tabel + gaya inline (sama dengan email reminder) supaya aman di aplikasi email.
    Data: $customer, $invoice (dengan order + allocations.payment), $receipt, $receiptNumber, $payments, $profile.
--}}
@php
    $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $date = fn ($value) => $value?->translatedFormat('d M Y');
    $order = $invoice->order;
    // Palet logo Budiman: marun sebagai aksen, peach sebagai pendamping; teks cokelat tua, tanpa hitam.
    $c = [
        'brand' => '#911b1d', 'peach' => '#fcc49d', 'peachSoft' => '#fdf0e6', 'page' => '#faf5f0',
        'text' => '#3f2a25', 'muted' => '#8a6f66', 'line' => '#f0e2d6',
    ];
    $rows = $receipt
        ? array_filter([
            ['No. Kwitansi', $receiptNumber],
            ['No. Invoice', $invoice->invoice_number],
            ['No. Pesanan', $order?->order_number],
            ['Tanggal bayar', $date($invoice->paid_at ?? $invoice->issue_date)],
            ['Metode', $payments->pluck('method_name')->unique()->implode(', ') ?: null],
        ], fn ($row) => filled($row[1]))
        : array_filter([
            ['No. Invoice', $invoice->invoice_number],
            ['No. Pesanan', $order?->order_number],
            ['Tanggal invoice', $date($invoice->issue_date)],
            ['Jatuh tempo', $date($invoice->due_date)],
            ['Total invoice', $invoice->paid_amount > 0 ? $rupiah($invoice->total_to_pay) : null],
            ['Sudah dibayar', $invoice->paid_amount > 0 ? $rupiah($invoice->paid_amount) : null],
        ], fn ($row) => filled($row[1]));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>{{ $receipt ? 'Kwitansi Pembayaran' : 'Invoice' }}</title>
</head>
<body style="margin:0; padding:0; background:{{ $c['page'] }}; font-family:Arial, Helvetica, sans-serif; color:{{ $c['text'] }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $c['page'] }}; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background:#ffffff; border:1px solid {{ $c['line'] }}; border-radius:10px; overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px; background:{{ $c['brand'] }}; color:#ffffff;">
                            <p style="margin:0; font-size:20px; font-weight:bold; letter-spacing:.3px;">{{ $profile['brand_name'] }}</p>
                            <p style="margin:4px 0 0; font-size:13px; color:{{ $c['peach'] }};">{{ $receipt ? 'Kwitansi Pembayaran' : 'Invoice' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px; line-height:4px; font-size:0; background:{{ $c['peach'] }};">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px 8px; font-size:14px; line-height:1.6;">
                            <p style="margin:0 0 12px;">Yth. {{ $customer->contact_name ?: $customer->name }},</p>
                            <p style="margin:0 0 12px;">
                                @if ($receipt)
                                    Terima kasih. Pembayaran <strong style="color:{{ $c['brand'] }};">{{ $customer->name }}</strong> untuk pesanan berikut telah kami terima.
                                @else
                                    Terima kasih atas pesanan <strong style="color:{{ $c['brand'] }};">{{ $customer->name }}</strong>. Berikut invoice untuk pesanan Anda.
                                @endif
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px;">
                                @foreach ($rows as [$label, $value])
                                    <tr>
                                        <td style="padding:9px 8px; border-bottom:1px solid {{ $c['line'] }}; color:{{ $c['muted'] }}; width:40%;">{{ $label }}</td>
                                        <td style="padding:9px 8px; border-bottom:1px solid {{ $c['line'] }};{{ $loop->first ? ' font-weight:bold; color:'.$c['brand'].';' : '' }}">{{ $value }}</td>
                                    </tr>
                                @endforeach
                                <tr style="background:{{ $c['peachSoft'] }}; color:{{ $c['brand'] }};">
                                    <td style="padding:12px 8px; font-weight:bold;">{{ $receipt ? 'Jumlah dibayar' : ($invoice->paid_amount > 0 ? 'Sisa tagihan' : 'Total tagihan') }}</td>
                                    <td style="padding:12px 8px; font-weight:bold; font-size:15px; white-space:nowrap;">{{ $rupiah($receipt ? $invoice->paid_amount : $invoice->outstandingAmount()) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 8px; font-size:14px; line-height:1.6;">
                            @unless ($receipt)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $c['peachSoft'] }}; border-left:4px solid {{ $c['brand'] }}; border-radius:6px;">
                                    <tr>
                                        <td style="padding:14px 16px; font-size:13px; line-height:1.6;">
                                            Pembayaran dapat ditransfer ke:<br>
                                            <strong style="color:{{ $c['brand'] }};">{{ $profile['bank_name'] }} {{ $profile['bank_account_number'] }}</strong> a.n. {{ $profile['bank_account_name'] }}<br>
                                            Mohon cantumkan nomor invoice pada berita transfer.
                                        </td>
                                    </tr>
                                </table>
                            @endunless
                            <p style="margin:16px 0 0;">{{ $receipt ? 'Kwitansi' : 'Invoice' }} terlampir dalam format PDF.</p>
                            <p style="margin:12px 0 0;">Terima kasih,<br><strong style="color:{{ $c['brand'] }};">{{ $profile['brand_name'] }}</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 24px; font-size:12px; color:{{ $c['muted'] }}; border-top:1px solid {{ $c['line'] }}; background:{{ $c['page'] }};">
                            Pertanyaan seputar {{ $receipt ? 'pembayaran' : 'tagihan' }}: balas email ini{{ $profile['phone'] ? ' atau hubungi '.$profile['phone'] : '' }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
