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
                            <i>Jumlah Poin &amp; EXP Warga</i> <br>
                            <i> telah diperbarui!</i>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:24px; line-height:1.4; padding-bottom:20px;">
                            <i><strong style="font-weight: 400"> Hai Kak {{$data['name']}}</strong></i>,<br />
                            <i>Poin & EXP Warga telah diperbarui!</i>
                        </td>
                    </tr>

                    <!-- Tabel utama -->
                    <tr>
                        <td align="center" colspan="2">
                            <table>
                                <tr>
                                    <td style=" border: 10px solid #f4eee3; border-radius: 20px;">
                                        <table cellpadding="0" cellspacing="0" border="0" width="100%"
                                            style="background-color:#f4eee3; border:2px solid #b83b3a; border-radius:12px; color:#b83b3a; font-size:14px; color: #b83b3a;">
                                            <tr style="color: #b83b3a" align="center">


                                                <!-- Kolom vertikal, lebar sangat kecil -->
                                                {{-- <td
                                        style="writing-mode: sideways-lr; text-orientation: mixed; font-weight:bold; font-size:14px; text-align:center; letter-spacing: 0.3rem; border-right:2px solid #b83b3a;">
                                        TABEL INFORMASI<br>MEMBER
                                    </td> --}}

                                                <td
                                                    style="border-right:2px solid #b83b3a; padding:5px; color: #b83b3a;">
                                                    <svg width="35" height="210"
                                                        xmlns="http://www.w3.org/2000/svg" style="color: #b83b3a">
                                                        <text x="15" y="110" font-family="Arial" font-size="14"
                                                            font-weight="bold" style="color: #b83b3a"
                                                            text-anchor="middle" transform="rotate(-90 15,110)"
                                                            letter-spacing="3">
                                                            <tspan x="22.5" dy="0" fill="#b83b3a">TABEL
                                                                INFORMASI</tspan>
                                                            <tspan x="22.5" dy="1.2em" fill="#b83b3a">MEMBER</tspan>
                                                        </text>
                                                    </svg>
                                                </td>

                                                <!-- Kolom besar dengan colspan -->
                                                <td colspan="3" style="padding:15px 20px;">
                                                    <table cellpadding="0" cellspacing="0" border="0" width="100%"
                                                        style="font-family: Arial, Helvetica, sans-serif;">
                                                        <tr>
                                                            <td style="padding-bottom:15px; width:50%;">
                                                                <strong>Total Poin</strong><br />
                                                                <span style="font-size:18px; font-weight:bold;">{{$data['point']}}
                                                                    poin</span>
                                                            </td>
                                                            <td style="padding-bottom:15px; width:50%;">
                                                                <strong>Total EXP</strong><br />
                                                                <span style="font-size:18px; font-weight:bold;">{{$data['exp']}} /
                                                                    45.000
                                                                    EXP</span><br />
                                                                <small>Dapatkan gratis 1 Kopi Susu setiap kelipatan
                                                                    5.000
                                                                    EXP</small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-bottom:15px;">
                                                                <strong>Masa Aktif</strong><br />
                                                                <span style="font-weight:bold;">{{$data['expired']}}</span>
                                                            </td>
                                                            <td style="padding-bottom:15px;">
                                                                <strong>Level Warga</strong><br />
                                                                <span style="font-weight:bold;">{{$data['levelMembership']}}</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2"
                                                                style="border-top:1px solid #b83b3a; padding-top:10px; font-size:12px;">
                                                                Ayo kumpulin terus Poin & EXP Warga agar bisa dapetin
                                                                hadiah menarik
                                                                di tiap levelnya!
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" style="padding-top:10px;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%"
                                                                    style="text-align:center; font-weight:bold; font-size:13px;">
                                                                    <tr>
                                                                        <td
                                                                            style="border:1px solid #b83b3a; padding:10px; @if($data['nominalExp'] < 6000) background-color:#b83b3a; color:#f4eee3; @endif">
                                                                            <b style="font-weight: 1000">WARGA</b><br />0 - 6.000 EXP</td>
                                                                        <td
                                                                            style="border:1px solid #b83b3a; padding:10px; @if($data['nominalExp'] >= 6000 && $data['nominalExp'] < 15000) background-color:#b83b3a; color:#f4eee3; @endif">
                                                                            <b style="font-weight: 1000">DUTA</b><br />6.000 - 15.000 EXP</td>
                                                                        <td
                                                                            style="border:1px solid #b83b3a; padding:10px; @if($data['nominalExp'] >= 15000 && $data['nominalExp'] < 45000) background-color:#b83b3a; color:#f4eee3; @endif">
                                                                            <b style="font-weight: 1000">OWNER</b><br />15.000 - 45.000 EXP</td>
                                                                        <td
                                                                            style="border:1px solid #b83b3a; padding:10px; @if($data['nominalExp'] >= 45000) background-color:#b83b3a; color:#f4eee3; @endif">
                                                                            <b style="font-weight: 1000">KOMISARIS</b><br />45.000 EXP</td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Spacer sebagai baris tabel -->
                    <tr>
                        <td colspan="3" style="padding: 0; margin: 20px 20px; text-align: center;">
                            <div style="width: 90%; border-top: 1px solid #f4eee3; margin: 20px auto;"></div>
                        </td>
                    </tr>
                    <tr><br></tr>


                    <!-- Footer -->
                    <tr>
                        <td colspan="2" style="font-size:12px; line-height:1.4; padding-top:20px; color:#f4eee3;">
                            Pengertian Poin, Exp, Level :<br />
                            <strong>Poin</strong> : Nilai dari tiap transaksi dan jumlah bisa berkurang jika
                            ditukarkan.<br />
                            <strong>EXP</strong> : Akumulasi nilai dari seluruh transaksi dan tidak akan
                            berkurang.<br />
                            <strong>Level</strong> : Kenaikan status membership saat EXP sudah mencapai nominal
                            tertentu.
                        </td>
                    </tr>
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
                            <img width="140" src="{{ asset('img/Logo Red.png') }}" alt="Logo Red" />
                        </td>
                    </tr>


                </table>
            </td>
        </tr>
    </table>



</body>

</html>
