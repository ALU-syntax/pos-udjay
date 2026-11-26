{{--
    Email konfirmasi pendaftaran customer B2B lewat form umum. Layout tabel + gaya inline (sama dengan email invoice)
    supaya aman di aplikasi email. Data: $customer, $profile, $adminPhone (nullable), $adminWhatsAppUrl (nullable).
--}}
@php
    // Palet logo Budiman: marun sebagai aksen, peach sebagai pendamping; teks cokelat tua, tanpa hitam.
    $c = [
        'brand' => '#911b1d', 'peach' => '#fcc49d', 'peachSoft' => '#fdf0e6', 'page' => '#faf5f0',
        'text' => '#3f2a25', 'muted' => '#8a6f66', 'line' => '#f0e2d6',
    ];
    $rows = array_filter([
        ['Kode customer', $customer->code],
        ['Nama', $customer->name],
        ['PIC', $customer->contact_name !== $customer->name ? $customer->contact_name : null],
        ['Telepon', $customer->phone],
        ['Email', $customer->email],
    ], fn ($row) => filled($row[1]));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>Pendaftaran Customer</title>
</head>
<body style="margin:0; padding:0; background:{{ $c['page'] }}; font-family:Arial, Helvetica, sans-serif; color:{{ $c['text'] }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $c['page'] }}; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background:#ffffff; border:1px solid {{ $c['line'] }}; border-radius:10px; overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px; background:{{ $c['brand'] }}; color:#ffffff;">
                            <p style="margin:0; font-size:20px; font-weight:bold; letter-spacing:.3px;">{{ $profile['brand_name'] }}</p>
                            <p style="margin:4px 0 0; font-size:13px; color:{{ $c['peach'] }};">Pendaftaran Customer</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px; line-height:4px; font-size:0; background:{{ $c['peach'] }};">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px 8px; font-size:14px; line-height:1.6;">
                            <p style="margin:0 0 12px;">Yth. {{ $customer->contact_name ?: $customer->name }},</p>
                            <p style="margin:0 0 12px;">
                                Terima kasih telah mendaftar sebagai customer <strong style="color:{{ $c['brand'] }};">{{ $profile['brand_name'] }}</strong>.
                                Pendaftaran Anda sudah kami terima dengan data berikut.
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
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 8px; font-size:14px; line-height:1.6;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $c['peachSoft'] }}; border-left:4px solid {{ $c['brand'] }}; border-radius:6px;">
                                <tr>
                                    <td style="padding:14px 16px; font-size:13px; line-height:1.6;">
                                        @if ($adminPhone)
                                            Untuk pemesanan, silakan hubungi admin kami di
                                            <strong style="color:{{ $c['brand'] }};">{{ $adminPhone }}</strong>.
                                            @if ($adminWhatsAppUrl)
                                                <br><a href="{{ $adminWhatsAppUrl }}" style="display:inline-block; margin-top:10px; padding:8px 16px; background:{{ $c['brand'] }}; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold;">Chat admin lewat WhatsApp</a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:16px 0 0;">Terima kasih,<br><strong style="color:{{ $c['brand'] }};">{{ $profile['brand_name'] }}</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 24px; font-size:12px; color:{{ $c['muted'] }}; border-top:1px solid {{ $c['line'] }}; background:{{ $c['page'] }};">
                            Jika Anda tidak merasa mendaftar, abaikan email ini.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
