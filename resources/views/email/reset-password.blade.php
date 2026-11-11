{{--
    Email "lupa password". Layout tabel + gaya inline supaya aman di berbagai aplikasi email.
    Warna mengikuti email membership: merah #b83b3a dengan teks krem #f4eee3.
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Atur Ulang Password</title>
</head>

<body style="margin:0; padding:0; font-family:Arial, Helvetica, sans-serif; color:#f4eee3;">
    {{-- Teks pratinjau di daftar email --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        Link untuk mengatur ulang password akun {{ config('app.name') }} Anda. Berlaku {{ $expireMinutes }} menit.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:20px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:520px; background-color:#b83b3a; border-radius:12px;">
                    <tr>
                        {{-- Logo diambil dari domain production (sama seperti email membership) agar tetap tampil
                             dari mana pun email dikirim. Gaya teks di <img> dipakai untuk alt bila gambar diblokir. --}}
                        <td style="padding:28px 32px 0;">
                            <img src="https://backoffice.uddjaya.com/img/Logo%20Cream.png" alt="UD. Djaya" width="200"
                                style="display:block; width:200px; max-width:100%; height:auto; border:0; font-size:16px; font-weight:bold; letter-spacing:1px; color:#f4eee3;" />
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px 32px; color:#f4eee3;">
                            <h1 style="margin:0 0 20px; font-size:26px; line-height:1.3; font-weight:normal; color:#f4eee3;">
                                Atur ulang password
                            </h1>

                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#f4eee3;">Halo {{ $user->name }},</p>

                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#f4eee3;">
                                Kami menerima permintaan untuk mengatur ulang password akun Anda.
                                Klik tombol di bawah untuk membuat password baru.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                                <tr>
                                    <td style="border-radius:6px; background-color:#f4eee3;">
                                        <a href="{{ $url }}" target="_blank"
                                            style="display:inline-block; padding:12px 28px; font-size:15px; font-weight:bold; color:#b83b3a; text-decoration:none;">
                                            Atur Ulang Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#f4eee3;">
                                Link ini berlaku selama {{ $expireMinutes }} menit dan hanya bisa dipakai satu kali.
                            </p>

                            <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#f4eee3;">
                                Jika Anda tidak meminta pengaturan ulang password, abaikan email ini.
                                Password Anda tidak akan berubah.
                            </p>

                            <p style="margin:0; padding-top:16px; border-top:1px solid #f4eee3; font-size:12px; line-height:1.6; color:#f4eee3;">
                                Tombol tidak bisa diklik? Salin alamat berikut ke browser Anda:<br>
                                <a href="{{ $url }}" style="color:#f4eee3; text-decoration:underline; word-break:break-all;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
