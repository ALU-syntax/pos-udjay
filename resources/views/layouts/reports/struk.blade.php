<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:title" content="Struk pembelian Udjaya" />
    <meta property="og:description" content="Rincian pembelian anda di Udjaya" />
    <meta property="og:url" content="https://instagram.com/udjaya" />
    <meta property="og:image" content="/assets/img/struk.jpg" />
    <title>Struk Penjualan - UD.JAYA</title>
    <link rel="stylesheet" href="{{ asset('css/struk.css') }}">
    <link rel="icon" href="{{ asset('img/Logo Red.png') }}">
    {{-- <link rel="stylesheet" href="/assets/extensions/%40fortawesome/fontawesome-free/css/all.min.css" /> --}}
</head>

<body>

    <div class="container" id="DivIdToPrint">
        <div class="receipt_header">
            <center>
                <img src="{{ asset('img/Logo Red.png') }}" alt="" class="circle-image">
            </center>
            <h1 style="color: white">{{ $data->outlet->name }}</h1>
            <p style="color: white">{{ $data->outlet->address }}</p>
            <p style="color: white">{{ $data->outlet->phone }}</p>
        </div>

        <div class="receipt_body">
            <div class="card" style="padding: 15px;">
                <div class="card-title" style="text-align: center;">
                    {{ formatRupiah(strval($data->total), 'Rp. ') }}
                </div>
            </div>

            <div class="card" style="padding:10px; margin-top: 20px;">
                <div class="items" style="margin-top: 0px; !important">
                    <table>
                        <thead>
                            <tr>
                                <td>{{ $data->tanggal_beli }}</td>
                                <td></td>
                                <td align="right">{{ $data->waktu_beli }}</td>
                            </tr>
                            <tr>
                                <td>Receipt Number</td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Collected By</td>
                                <td></td>
                                <td align="right">{{ $data->user->name }}</td>
                            </tr>
                        </thead>
                    </table>

                    <table>
                        <thead>
                            <th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
                            <th>Dine In</th>
                            <th></th>
                        </thead>
                    </table>
                </div>

                <div class="">
                    <table>
                        <tbody>
                            @foreach ($data->itemTransaction as $transaction)
                                <tr>
                                    <td style="font-weight: normal;">{{ $transaction->product->name == $transaction->variant->name? $transaction->product->name : $transaction->product->name . ' - ' . $transaction->variant->name }}</td>
                                    <td>{{ $transaction->total_count }}</td>
                                    <td>{{ formatRupiah(strval(($transaction->variant->harga * $transaction->total_count)), "Rp. ") }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Subtotal</td>
                                <td></td>
                                <td>Rp.123123</td>
                            </tr>

                            {{-- List Pajak --}}
                            <tr>
                                <td>Pembayaran</td>
                                <td></td>
                                <td>tipe pembayaran</td>
                            </tr>

                            <tr>
                                <td style="padding-top: 10px; font-weight: bold;">Total</td>
                                <td></td>
                                <td>Rp.120893123</td>
                            </tr>

                            <tr>
                                <td style="padding-top: 10px;">Payment Type</td>
                                <td></td>
                                <td>Rp.120893123</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <script src="/assets/extensions/jquery/jquery.min.js"></script>

</body>

</html>
