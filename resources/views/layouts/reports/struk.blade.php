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
    <link rel="stylesheet" href="{{asset('css/struk.css')}}">
    <link rel="icon" href="{{asset('img/Logo Red.png')}}">
    {{-- <link rel="stylesheet" href="/assets/extensions/%40fortawesome/fontawesome-free/css/all.min.css" /> --}}
</head>

<body>

    <div class="container" id="DivIdToPrint">
        <div class="receipt_header">
            <center>
                <img src="{{asset('img/Logo Red.png')}}" alt=""  class="circle-image">
            </center>
            <h1>Struk Penjualan <span>Nama Toko</span></h1>
            
        </div>

        <div class="receipt_body">
            <div class="card" style="padding: 15px;">
                <div class="card-title" style="text-align: center;">
                    Total                
                </div>
            </div>

            <div class="card" style="padding:10px; margin-top: 20px;">
                <div class="items" style="margin-top: 0px; !important">
                    <table>
    
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Barang</td>
                                <td>Total Harga</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Customer</td>
                                <td></td>
                                <td>Nama Customer</td>
                            </tr>
    
                            <tr>
                                <td>Pembayaran</td>
                                <td></td>
                                <td>tipe pembayaran</td>
                            </tr>
                        </tfoot>
                        <tfoot>
                            <tr>
                                <td style="font-weight: normal;">Harga</td>
                                <td></td>
                                <td style="font-weight: normal;">Rp. Sub total</td>
                            </tr>
                            <tr>
                                <td>Discount</td>
                                <td></td>
                                <td>Rp.Discount</td>
                            </tr>
                            <tr>
                                <td>PPN</td>
                                <td></td>
                                <td>Rp. PPN</td>
                            </tr>
                            <tr>
                                <td>Biaya Layanan</td>
                                <td></td>
                                <td>Rp. pajak</td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold;">Total</td>
                                <td></td>
                                <td style="font-weight: bold;">Rp. TOtal</td>
                            </tr>
                        </tfoot>
    
                    </table>
                </div>                
                
                <div class="items">
                    <table>
                        <thead>
                            <th>QTY</th>
                            <th>ITEM</th>
                            <th>TOTAL</th>
                        </thead>
                        <hr>
                        <br>
    
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Barang</td>
                                <td>Total Harga</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Customer</td>
                                <td></td>
                                <td>Nama Customer</td>
                            </tr>
    
                            <tr>
                                <td>Pembayaran</td>
                                <td></td>
                                <td>tipe pembayaran</td>
                            </tr>
                        </tfoot>
                        <tfoot>
                            <tr>
                                <td style="font-weight: normal;">Harga</td>
                                <td></td>
                                <td style="font-weight: normal;">Rp. Sub total</td>
                            </tr>
                            <tr>
                                <td>Discount</td>
                                <td></td>
                                <td>Rp.Discount</td>
                            </tr>
                            <tr>
                                <td>PPN</td>
                                <td></td>
                                <td>Rp. PPN</td>
                            </tr>
                            <tr>
                                <td>Biaya Layanan</td>
                                <td></td>
                                <td>Rp. pajak</td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold;">Total</td>
                                <td></td>
                                <td style="font-weight: bold;">Rp. TOtal</td>
                            </tr>
                        </tfoot>
    
                    </table>
                </div>                
            </div>

        </div>

        <h3>Terimakasih!</h3>

    </div>

    <script src="/assets/extensions/jquery/jquery.min.js"></script>

</body>

</html>
