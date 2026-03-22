<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Birthday Member</title>

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

<body style="margin:0; padding:0;  font-family: Arial, Helvetica, sans-serif; color:#f4eee3;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <!-- Container -->
                <table cellpadding="45" cellspacing="0" border="0"
                    style="background-color:#b83b3a; width: 100%; padding-top: 40px; padding-bottom: 40px;">
                    <tr>
                        <td colspan="1" style="position: relative; font-size: 22px; font-style: italic; align-content: baseline; padding-right: 0;">
                            <strong style="font-size:38px; font-family: 'BNBoop';">UD MEMBER</strong> <br><br>
                            <strong> Hai Kak {{$data['name']}},</strong> <br> <br>
                            <span> Selamat ulang tahun Kak {{$data['name']}}.</span><br>
                            <span> Semoga doa baik selalu menyertaimu!</span> <br> <br>
                            <strong> Sebagai kado ulang tahun dari kami, kamu bisa tunjukan email ini dan dapatkan</strong> <br>
                            <strong>special treatment di seluruh kedai UD. Djaya.</strong> <br> <br>
                            <span> Terima kasih telah merayakan momen</span> <br>
                            <span> spesial bersama kami.</span> <br>
                        </td>
                        <td colspan="1" style="text-align: right; vertical-align: middle; border-bottom-right-radius: 12px; padding-top: 0px">
                            <img width="350" src="http://pos-udjay.test/img/cake.png" alt="Logo Red" />
                        </td>
                    </tr>
                </table>
            </td>
            <td>

            </td>
        </tr>

    </table>

    {{-- Footer --}}
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <table cellpadding="45" cellspacing="0" border="0"
                    style="width: 100%; background-color:#b83b3a; margin-left: auto; margin-right: auto;">
                    <tr style="background-color: #f4eee3;">
                        <td
                            style="padding-top:30px; font-size:24px; color:#b83b3a; background-color: #f4eee3; ">
                            <i>Salam hangat,</i><br />
                            <i>UD. Djaya Coffee</i><br />
                            <img src="{{ env('APP_URL') }}/img/instagram_icon.png"
                                alt="Instagram" width="30" style="vertical-align:middle; margin-right: 5px;" />
                            <span style="font-size: 24px"> : @ud.djaya</span>
                        </td>
                        <!-- Kolom kanan: Logo Red -->
                        <td style="text-align: right; vertical-align: middle;">
                            <img width="240" src="{{ env('APP_URL') }}/img/Logo%20Red.png" alt="Logo Red" />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>



</body>

</html>
