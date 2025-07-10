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
    <link rel="icon" href="https://backoffice.uddjaya.com/public/img/Logo%20Red.png">
    {{-- <link rel="stylesheet" href="/assets/extensions/%40fortawesome/fontawesome-free/css/all.min.css" /> --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Source+Sans+Pro&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Source Sans Pro', sans-serif;
        }

        .container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            /* padding: 25px; */
            background-color: #ececec;
            /* max-height: 100vh; */
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .receipt_header {
            padding-left: 0;
            padding-right: 0;
            padding-bottom: 40px;
            border-bottom: 1px dashed #000;
            text-align: center;
            background-color: #d03c3c;
        }

        .receipt_header h1 {
            font-size: 20px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .receipt_header h1 span {
            display: block;
            font-size: 25px;
        }

        .receipt_header h2 {
            font-size: 14px;
            color: #727070;
            font-weight: 300;
        }

        .receipt_header h2 span {
            display: block;
        }

        .receipt_body {
            /* margin-top: 25px; */
            padding: 15px;
            border-radius: 10;
        }

        table {
            width: 100%;
        }

        thead,
        tfoot {
            position: relative;
        }

        thead th:not(:last-child) {
            text-align: left;
        }

        thead th:last-child {
            text-align: right;
        }

        thead::after {
            content: '';
            width: 100%;
            border-bottom: 1px dashed #000;
            display: block;
            position: absolute;
        }

        tbody td:not(:last-child),
        tfoot td:not(:last-child) {
            text-align: left;
        }

        tbody td:last-child,
        tfoot td:last-child {
            text-align: right;
        }

        tbody tr:first-child td {
            padding-top: 15px;
        }

        tbody tr:last-child td {
            padding-bottom: 15px;
        }

        tfoot tr:first-child td {
            padding-top: 15px;
        }

        tfoot::before {
            content: '';
            width: 100%;
            border-top: 1px dashed #000;
            display: block;
            position: absolute;
        }

        tfoot tr:first-child td:first-child,
        tfoot tr:first-child td:last-child {
            /* font-weight: bold; */
            /* font-size: 20px; */
        }

        .date_time_con {
            display: flex;
            justify-content: center;
            column-gap: 25px;
        }

        .items {
            margin-top: 25px;
        }

        h3 {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 25px;
            text-align: center;
            text-transform: uppercase;
        }

        .circle-image {
            width: 150px;
            /* Atur lebar gambar */
            height: 150px;
            /* Atur tinggi gambar */
            border-radius: 50%;
            /* Membuat gambar menjadi lingkaran */
            object-fit: contain;
            /* Memastikan gambar tidak terdistorsi */
            padding: 5px;
            border: 2px solid #ccc;
            /* Opsional: menambahkan border */
            background-color: white;
        }

        .card {
            background-color: #fff;
            /* Warna latar belakang card */
            border-radius: 8px;
            /* Sudut melengkung */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            /* Bayangan card */
            overflow: hidden;
            /* Menghindari konten melampaui batas card */
            width: 100%;
            /* Lebar card */
            transition: transform 0.3s;
            /* Efek transisi saat hover */
        }

        .card:hover {
            transform: scale(1.05);
            /* Membesarkan card saat hover */
        }

        .card-image {
            width: 100%;
            /* Gambar memenuhi lebar card */
            height: auto;
            /* Tinggi otomatis untuk menjaga rasio aspek */
        }

        .card-content {
            padding: 15px;
            /* Ruang di dalam card */
        }

        .card-title {
            font-size: 1.5em;
            /* Ukuran font untuk judul */
            margin: 0;
            /* Menghapus margin default */
        }

        .card-description {
            font-size: 1em;
            /* Ukuran font untuk deskripsi */
            color: #666;
            /* Warna teks deskripsi */
            margin: 10px 0;
            /* Margin atas dan bawah */
        }

        .card-button {
            display: inline-block;
            /* Mengatur tombol sebagai blok inline */
            padding: 10px 15px;
            /* Ruang di dalam tombol */
            background-color: #007bff;
            /* Warna latar belakang tombol */
            color: #fff;
            /* Warna teks tombol */
            text-decoration: none;
            /* Menghapus garis bawah */
            border-radius: 5px;
            /* Sudut melengkung tombol */
            transition: background-color 0.3s;
            /* Efek transisi saat hover */
        }

        .card-button:hover {
            background-color: #0056b3;
            /* Warna latar belakang tombol saat hover */
        }
    </style>
</head>

<body>

    <div class="container" id="DivIdToPrint">
        <div class="receipt_header">
            <center>
                <img src="https://backoffice.uddjaya.com/public/img/Logo%20Red.png" alt="" class="circle-image"
                    style="margin-top: 10px;">
            </center>
            <h1 style="color: white; font-size: 25px;">{{ $data->outlet->name }}</h1>

        </div>

        <div class="receipt_body">
            <div class="card" style="padding: 15px;">
                <div class="card-title" style="text-align: center;">
                    {{ formatRupiah(strval($data->total), 'Rp. ') }}
                </div>
            </div>

            <div class="card" style="padding:10px; margin-top: 20px; margin-bottom: 50px;">
                <div class="items" style="margin-top: 0px; !important">
                    <table>
                        <thead>
                            <tr>
                                <td>{{ $data->tanggal_beli }}</td>
                                <td></td>
                                <td align="right">{{ $data->waktu_beli }}</td>
                            </tr>
                            {{-- <tr>
                                <td>Receipt Number</td>
                                <td></td>
                                <td></td>
                            </tr> --}}
                            <tr>
                                <td>Customer</td>
                                <td></td>
                                <td align="right">{{ isset($data->customer) ? $data->customer->name : '-' }}</td>
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
                                    <td style="font-weight: normal;">
                                        {{ $transaction->product ? ($transaction->product->name == $transaction->variant->name ? $transaction->product->name : $transaction->product->name . ' - ' . $transaction->variant->name) : 'custom' }}
                                    </td>
                                    <td>x{{ $transaction->total_count }}</td>
                                    <td>{{ $transaction->variant ? formatRupiah(strval($transaction->variant->harga * $transaction->total_count), 'Rp. ') : ($transaction->harga ? formatRupiah(strval($transaction->harga), 'Rp. ') : '') }}
                                    </td>
                                </tr>

                                @if (count(json_decode($transaction->modifier_id)))
                                    @foreach (json_decode($transaction->modifier_id) as $modifier)
                                        <tr>
                                            <td style="font-weight: small; color:rgb(179, 180, 181)">
                                                {{ $modifier->nama }}
                                            </td>
                                            <td></td>
                                            <td>{{ formatRupiah(strval($modifier->harga * $transaction->total_count), 'Rp. ') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Subtotal</td>
                                <td></td>
                                <td>{{ formatRupiah(strval($data->sub_total), 'Rp. ') }}</td>
                            </tr>

                            @if ($data->total_nominal_diskon > 0)
                                <tr>
                                    <td>Discount</td>
                                    <td></td>
                                    <td>- {{ formatRupiah(strval($data->total_nominal_diskon), 'Rp. ') }}</td>
                                </tr>
                            @endif

                            {{-- List Pajak --}}
                            @if ($data->total_nominal_pajak > 0)
                                @foreach ($data->total_pajak as $pajak)
                                    <tr>
                                        <td>{{ $pajak->name }} ({{ $pajak->amount }}%)</td>
                                        <td></td>
                                        <td>{{ formatRupiah(strval($pajak->total), 'Rp. ') }}</td>
                                    </tr>
                                @endforeach
                            @endif

                            <tr>
                                <td style="padding-top: 10px; font-weight: bold;">Total</td>
                                <td></td>
                                <td>{{ formatRupiah(strval($data->total), 'Rp. ') }}</td>
                            </tr>

                            <tr>
                                <td style="padding-top: 10px;">Payment Type</td>
                                <td></td>
                                <td>{{ $data->nama_tipe_pembayaran }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <hr>
                <br>
                <br>

                <center>
                    <p>Visit Us At</p>
                    <p style="color: rgb(121, 117, 117); margin-top: 10px;">{{ $data->outlet->address }}</p>
                    <p style="color: rgb(121, 117, 117); ">{{ $data->outlet->phone }}</p>
                    <a href="https://www.instagram.com/ud.djaya" target="_blank" style="text-decoration:none; ">
                        <img src="https://cdn-icons-png.flaticon.com/512/174/174855.png" alt="Instagram" width="24"
                            height="24" style="display:inline-block; border:0; margin-top: 20px;" />
                    </a>

                </center>
            </div>

            <center>
                <small style="color: rgb(121, 117, 117); ">Copyright ©2025 Djaya Abadi Sejahtera<sup>®</sup>. All rights
                    reserved. </small>
            </center>
        </div>

    </div>

</body>

</html>
