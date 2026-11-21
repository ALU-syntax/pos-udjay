{{--
    Email reminder tagihan B2B (gabungan per customer per hari). Layout tabel + gaya inline supaya aman di aplikasi email.
    Data: $customer, $items (invoice + offset hari ke jatuh tempo), $profile (B2bSettings::invoiceProfile()), $outstanding.
--}}
@php
    $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $date = fn ($value) => $value?->translatedFormat('d M Y');
    $when = fn (int $offset) => match (true) {
        $offset === 0 => 'Jatuh tempo hari ini',
        $offset < 0 => abs($offset).' hari lagi',
        default => 'Lewat '.$offset.' hari',
    };
    $hasOverdue = $items->contains(fn ($item) => $item['offset'] > 0);
    // Palet logo Budiman: marun sebagai aksen, peach sebagai pendamping; teks cokelat tua, tanpa hitam.
    $c = [
        'brand' => '#911b1d', 'peach' => '#fcc49d', 'peachSoft' => '#fdf0e6', 'page' => '#faf5f0',
        'text' => '#3f2a25', 'muted' => '#8a6f66', 'line' => '#f0e2d6', 'late' => '#c2410c',
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>Pengingat Tagihan</title>
</head>
<body style="margin:0; padding:0; background:{{ $c['page'] }}; font-family:Arial, Helvetica, sans-serif; color:{{ $c['text'] }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $c['page'] }}; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background:#ffffff; border:1px solid {{ $c['line'] }}; border-radius:10px; overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px; background:{{ $c['brand'] }}; color:#ffffff;">
                            <p style="margin:0; font-size:20px; font-weight:bold; letter-spacing:.3px;">{{ $profile['brand_name'] }}</p>
                            <p style="margin:4px 0 0; font-size:13px; color:{{ $c['peach'] }};">Pengingat Tagihan</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px; line-height:4px; font-size:0; background:{{ $c['peach'] }};">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px 8px; font-size:14px; line-height:1.6;">
                            <p style="margin:0 0 12px;">Yth. {{ $customer->contact_name ?: $customer->name }},</p>
                            <p style="margin:0 0 12px;">
                                @if ($hasOverdue)
                                    Kami ingin mengingatkan bahwa terdapat tagihan <strong style="color:{{ $c['brand'] }};">{{ $customer->name }}</strong> yang telah melewati jatuh tempo. Mohon segera melakukan pembayaran.
                                @else
                                    Kami ingin mengingatkan tagihan <strong style="color:{{ $c['brand'] }};">{{ $customer->name }}</strong> yang akan atau sudah jatuh tempo berikut ini.
                                @endif
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px;">
                                <tr style="background:{{ $c['peachSoft'] }}; color:{{ $c['brand'] }};">
                                    <th align="left" style="padding:10px 8px; border-bottom:1px solid {{ $c['line'] }};">No. Invoice</th>
                                    <th align="left" style="padding:10px 8px; border-bottom:1px solid {{ $c['line'] }};">Jatuh Tempo</th>
                                    <th align="right" style="padding:10px 8px; border-bottom:1px solid {{ $c['line'] }};">Sisa Tagihan</th>
                                </tr>
                                @foreach ($items as $item)
                                    @php $invoice = $item['invoice']; @endphp
                                    <tr>
                                        <td style="padding:10px 8px; border-bottom:1px solid {{ $c['line'] }};">
                                            <strong style="color:{{ $c['brand'] }};">{{ $invoice->invoice_number }}</strong><br>
                                            <span style="color:{{ $c['muted'] }}; font-size:12px;">Tanggal invoice {{ $date($invoice->issue_date) }}</span>
                                        </td>
                                        <td style="padding:10px 8px; border-bottom:1px solid {{ $c['line'] }};">
                                            {{ $date($invoice->due_date) }}<br>
                                            <span style="font-size:12px; color:{{ $item['offset'] > 0 ? $c['late'] : $c['muted'] }};{{ $item['offset'] > 0 ? ' font-weight:bold;' : '' }}">{{ $when($item['offset']) }}</span>
                                        </td>
                                        <td align="right" style="padding:10px 8px; border-bottom:1px solid {{ $c['line'] }}; white-space:nowrap;">
                                            {{ $rupiah($invoice->outstandingAmount()) }}
                                            @if ($invoice->paid_amount > 0)
                                                <br><span style="color:{{ $c['muted'] }}; font-size:12px;">dari {{ $rupiah($invoice->total_to_pay) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <tr style="background:{{ $c['peachSoft'] }}; color:{{ $c['brand'] }};">
                                    <td colspan="2" style="padding:12px 8px; font-weight:bold;">Total sisa tagihan</td>
                                    <td align="right" style="padding:12px 8px; font-weight:bold; font-size:15px; white-space:nowrap;">{{ $rupiah($outstanding) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 8px; font-size:14px; line-height:1.6;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $c['peachSoft'] }}; border-left:4px solid {{ $c['brand'] }}; border-radius:6px;">
                                <tr>
                                    <td style="padding:14px 16px; font-size:13px; line-height:1.6;">
                                        Pembayaran dapat ditransfer ke:<br>
                                        <strong style="color:{{ $c['brand'] }};">{{ $profile['bank_name'] }} {{ $profile['bank_account_number'] }}</strong> a.n. {{ $profile['bank_account_name'] }}<br>
                                        Mohon cantumkan nomor invoice pada berita transfer.
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:16px 0 0;">Invoice terlampir dalam format PDF. Abaikan email ini apabila pembayaran sudah dilakukan.</p>
                            <p style="margin:12px 0 0;">Terima kasih,<br><strong style="color:{{ $c['brand'] }};">{{ $profile['brand_name'] }}</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 24px; font-size:12px; color:{{ $c['muted'] }}; border-top:1px solid {{ $c['line'] }}; background:{{ $c['page'] }};">
                            Pertanyaan seputar tagihan: balas email ini{{ $profile['phone'] ? ' atau hubungi '.$profile['phone'] : '' }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
