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
                url('https://backoffice.uddjaya.com/fonts/BNBoop.otf') format('opentype');
            ;
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
                <table width="750" cellpadding="20" cellspacing="0" border="0"
                    style="background-color:#b83b3a; border-radius:12px; margin:20px auto;">
                    <!-- Header -->
                    <tr>
                        <td style="text-align:left;">
                            <strong style="font-size:18px; font-family: 'BNBoop';">UD MEMBER</strong>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" style="font-size:44px; font-weight:400; padding-bottom:10px;">
                            <i>Selamat hari ini kamu naik</i> <br>
                            <i>level jadi {{ $data['levelMembershipNow'] }}</i>
                        </td>
                    </tr>

                    {{-- logo naik level --}}
                    <tr>
                        <td colspan="2">
                            <img src="" alt="">
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" style="font-size:28px; line-height:1.4; padding-bottom:20px;">
                            <i>Sebagai reward kamu telah mengumpulkan </i> <br>
                            <i>EXP {{ $data['exp'] }}, kamu berhak mendapatkan</i> <br>
                            @foreach ($data['reward'] as $reward)
                                {{ '•' . $reward->name }} <br>
                            @endforeach
                            <br>
                            <i>tunjukin e-mail ini ke kasir dan klaim rewardmu!</i>
                        </td>
                    </tr>

                    <!-- Spacer sebagai baris tabel -->
                    <tr>
                        <td colspan="3" style="padding: 0; margin: 20px 20px; text-align: center;">
                            <div style="width: 95%; border-top: 1px solid #f4eee3; margin: 5px auto;"></div>
                        </td>
                    </tr>
                    <tr><br></tr>

                    <tr>
                        <td colspan="2" style="font-size: 18px">
                            <i>Ayo kumpulkan poin dan EXP-mu lebih banyak lagi agar bisa</i><br>
                            <i>naik level ke {{$data['nextMember']}}</i>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" style="padding-top:10px; ">
                            <table cellpadding="0" cellspacing="0" border="0" width="100%"
                                style="text-align:center; font-weight:bold; font-size:13px; border: 1px solid #f4eee3; color: #b83b3a;">
                                <tr>
                                    <td
                                        style="border:1px solid #b83b3a; padding:10px; @if($data['nominalExp'] < 6000) background-color:#b83b3a; color:#f4eee3; @else background-color: #f4eee3; @endif">
                                        <b style="font-weight: 1000">WARGA</b><br />0 - 6.000 EXP
                                    </td>
                                    <td style="border:1px solid #b83b3a; padding:10px; @if($data['nominalExp'] >= 6000 && $data['nominalExp'] < 15000) background-color:#b83b3a; color:#f4eee3; @else background-color: #f4eee3; @endif">
                                        <b style="font-weight: 1000">DUTA</b><br />6.000 - 15.000 EXP
                                    </td>
                                    <td style="border:1px solid #b83b3a; padding:10px; @if($data['nominalExp'] >= 15000 && $data['nominalExp'] < 45000) background-color:#b83b3a; color:#f4eee3; @else background-color: #f4eee3; @endif">
                                        <b style="font-weight: 1000">OWNER</b><br />15.000 - 45.000 EXP
                                    </td>
                                    <td style="border:1px solid #b83b3a; padding:10px; @if($data['nominalExp'] >= 45000) background-color:#b83b3a; color:#f4eee3; @else background-color: #f4eee3; @endif">
                                        <b style="font-weight: 1000">KOMISARIS</b><br />45.000 EXP
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" style="font-size:18px; line-height:1.4; padding-top:20px; color:#f4eee3;">
                            <i>Segala kebaruan informasi yang berkaitan dengan member akan kami</i><br />
                            <i>informasikan melalui email.</i>
                            <br />
                            <br />
                            <i>Terima kasih telah menjadi bagian dari Warga UD. Djaya.</i>
                        </td>
                    </tr>


                    <!-- Footer -->
                    <tr style="background-color: #f4eee3;">
                        <td
                            style="padding-top:30px; font-weight:bold; font-size:14px; color:#b83b3a; background-color: #f4eee3; border-bottom-left-radius: 12px; ">
                            Salam hangat,<br />
                            UD. Djaya Coffee<br />
                            <img src="https://upload.wikimedia.org/wikipedia/commons/9/9e/Instagram_logo_2016.svg"
                                alt="Instagram" width="20" style="vertical-align:middle; margin-right: 5px;" />
                            @ud.djaya
                        </td>
                        <!-- Kolom kanan: Logo Red -->
                        <td style="text-align: right; vertical-align: middle; border-bottom-right-radius: 12px;">
                            <img width="140" src="https://backoffice.uddjaya.com/img/Logo%20Red.png" alt="Logo Red" />
                        </td>
                    </tr>


                </table>
            </td>
        </tr>
    </table>



</body>

</html>
