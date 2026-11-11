{{--
    Email ulang tahun member. Layout tabel + gaya inline supaya aman di berbagai aplikasi email.
    Desain: "Temp - Ulang Tahun" (merah #c63b3b, krem #edebde). Ilustrasi kue dan badge "UD MEMBER"
    berupa PNG karena aplikasi email (Gmail, Outlook) tidak merender SVG.
    Gambar diambil dari domain production (sama seperti email lain) agar tetap tampil dari mana pun
    email dikirim.
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="color-scheme" content="light only" />
    <meta name="supported-color-schemes" content="light only" />
    <title>Selamat Ulang Tahun</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,800;1,800&display=swap');
    </style>

    <style>
        body,
        p,
        td {
            font-family: 'Plus Jakarta Sans', Arial, Helvetica, sans-serif;
        }

        /* Di layar sempit, kue turun ke bawah teks */
        @media only screen and (max-width: 520px) {
            .stack {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .text-cell {
                padding: 32px 24px 8px 24px !important;
            }

            .cake-cell {
                padding: 8px 24px 32px 24px !important;
            }

            .footer-text {
                padding-left: 24px !important;
            }

            .footer-logo {
                padding-right: 24px !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#ffffff;">

    {{-- Teks pratinjau di daftar email --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; font-size:1px; line-height:1px; color:#ffffff;">
        Selamat ulang tahun! Ada special treatment untukmu di seluruh kedai UD. Djaya.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%; max-width:600px;">

                    {{-- Bagian merah: ucapan + ilustrasi kue --}}
                    <tr>
                        <td bgcolor="#c63b3b" style="background-color:#c63b3b;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="stack text-cell" width="335" valign="top"
                                        style="padding:38px 4px 44px 28px; color:#edebde; font-size:14px; line-height:18px;">
                                        <img src="https://backoffice.uddjaya.com/img/email/ud-member.png" alt="UD MEMBER"
                                            width="148" height="26"
                                            style="display:block; border:0; width:148px; height:26px; font-size:16px; font-weight:bold; color:#edebde;" />

                                        <p style="margin:38px 0 0 0; font-weight:800; color:#edebde;">
                                            Hai Kak {{ $data['name'] }},
                                        </p>

                                        <p style="margin:18px 0 0 0; font-weight:400; color:#edebde;">
                                            Selamat ulang tahun Kak {{ $data['name'] }}.<br />
                                            Semoga doa baik selalu menyertaimu!
                                        </p>

                                        <p style="margin:18px 0 0 0; font-weight:800; color:#edebde;">
                                            Sebagai kado ulang tahun dari kami, kamu bisa
                                            tunjukkan email ini dan dapatkan
                                            <i>special treatment</i> di seluruh kedai UD.&nbsp;Djaya.
                                        </p>

                                        <p style="margin:18px 0 0 0; font-weight:400; color:#edebde;">
                                            Terima kasih telah merayakan momen
                                            spesial bersama kami.
                                        </p>
                                    </td>

                                    <td class="stack cake-cell" width="265" valign="top" align="center"
                                        style="padding:16px 18px 28px 0;">
                                        <img src="https://backoffice.uddjaya.com/img/email/birthday-cake.png"
                                            alt="Kue ulang tahun" width="245"
                                            style="display:block; border:0; width:100%; max-width:245px; height:auto; margin:0 auto; color:#edebde;" />
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer krem --}}
                    <tr>
                        <td bgcolor="#edebde" style="background-color:#edebde;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="footer-text" valign="middle"
                                        style="padding:18px 0 18px 30px; color:#c63b3b; font-size:14px; line-height:19px; font-weight:800;">
                                        Salam hangat,<br />
                                        UD. Djaya Coffee<br />
                                        <img src="https://backoffice.uddjaya.com/img/instagram_icon.png" alt="Instagram"
                                            width="19" height="19"
                                            style="border:0; width:19px; height:19px; vertical-align:middle;" />
                                        <span style="vertical-align:middle;">&nbsp;:
                                            <a href="https://www.instagram.com/ud.djaya/"
                                                style="color:#c63b3b; text-decoration:none;">@ud.djaya</a></span>
                                    </td>

                                    <td class="footer-logo" valign="middle" align="right"
                                        style="padding:18px 30px 18px 12px;">
                                        <img src="https://backoffice.uddjaya.com/img/Logo%20Red.png" alt="UD. Djaya"
                                            width="127"
                                            style="display:block; border:0; width:127px; height:auto; font-size:14px; font-weight:bold; color:#c63b3b;" />
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>
