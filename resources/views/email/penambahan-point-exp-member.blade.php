<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Email Template - Member Info</title>

    {{-- <style>
        @font-face {
            font-family: 'PlusJakartaSans-ExtraLight';
            src: url('http://pos-udjay.test/fonts/plus-jakarta-sans/static/PlusJakartaSans-ExtraLightt.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }



        body,
        p,
        td {
            font-family: 'PlusJakartaSans-ExtraLight', Arial, sans-serif;
        }
    </style> --}}

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;400;600;800;1000&display=swap');

        @font-face {
            font-family: 'BNBoop';
            src: url('http://pos-udjay.test/fonts/BNBoop.otf') format('opentype'),
            url('https://backoffice.uddjaya.com/fonts/BNBoop.otf') format('opentype');;
            font-weight: normal;
            font-style: normal;
        }


        body,
        p,
        td {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            font-weight: 200;
            /* Extra light */
        }
    </style>

</head>

<body style="margin:0px 0px; padding:0;  font-family: Arial, Helvetica, sans-serif; color:#b83b3a;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <!-- Container -->
                <table width="750" cellpadding="20" cellspacing="0" border="0"
                    style="background-color:#f4eee3;; border-radius:12px; margin:0 auto; ">
                    <!-- Header -->
                    <tr style="padding: 0px 10px;">
                        <td style="text-align:left;">
                            <strong style="font-size:18px; font-family: 'BNBoop';">UD MEMBER</strong>
                        </td>
                    </tr>
                    <tr style="padding: 0px 10px;">
                        <td style="font-size:24px; line-height:1.4; padding-bottom:0px;">
                            <i><strong style="font-weight: 400"> Hai Kak {{$data['name']}}</strong></i>,<br />
                            <i>Kamu telah <b>mendapatkan</b> poin sebanyak</i>
                        </td>
                    </tr>

                    <!-- Tabel utama -->
                    <tr style="padding: 0px 10px;">
                        <td colspan="2" style="font-size:64px; line-height:0; padding-top:0; padding-bottom:0; color:#0d9c51; font-family: 'BNBoop';">
                            <h1>+ {{$data['pointDidapat']}}</h1> <br>
                        </td>
                    </tr>

                    {{-- informasi --}}
                    <tr style="padding: 0px 10px;">
                        <td colspan="2" style="font-size:32px; line-height:0.2; padding-top: 0; padding-bottom: 0; color:#b83b3a">
                            <p><i>Ayo Kumpulkan Poinmu lebih banyak lagi</i> </p>
                            <p><i>agar bisa ditukar dengan berbagai promo</i> </p>
                            <p><i>dan voucher yang menarik lainnya!</i></p>

                        </td>
                    </tr>

                    <!-- Spacer sebagai baris tabel -->
                    <tr style="padding: 0px 10px;">
                        <td colspan="3" style="padding: 0; margin: 20px 20px; text-align: center;">
                            <div style="width: 95%; border-top: 1px solid #b83b3a; margin: 20px auto;"></div>
                        </td>
                    </tr>
                    <tr><br></tr>


                    <!-- Footer -->
                    <tr >
                        <td colspan="2" style="font-size:20px; line-height:1.4; padding-top:0px; color:#b83b3a;">
                            <i>Segala kebaruan informasi yang berkaitan dengan member akan kami</i><br />
                            <i>informasikan melalui email</i>
                            <br>
                            <br>
                            <i>Terima kasih telah menjadi bagian dari Warga UD. Djaya</i>
                        </td>
                    </tr>
                    <tr style="background-color: #b83b3a;">
                        <td
                            style="padding-top:30px; font-weight:bold; font-size:14px; color:#f4eee3; background-color: #b83b3a; border-bottom-left-radius: 12px; ">
                            Salam hangat,<br />
                            UD. Djaya Coffee<br />
                            <img src="https://upload.wikimedia.org/wikipedia/commons/9/9e/Instagram_logo_2016.svg"
                                alt="Instagram" width="20" style="vertical-align:middle; margin-right: 5px;" />
                            @ud.djaya
                        </td>
                        <!-- Kolom kanan: Logo Red -->
                        <td style="text-align: right; vertical-align: middle; border-bottom-right-radius: 12px;">
                            <img width="140" src="https://backoffice.uddjaya.com/img/Logo%20Cream.png" alt="Logo Red" />
                        </td>
                    </tr>


                </table>
            </td>
        </tr>
    </table>



</body>

</html>
