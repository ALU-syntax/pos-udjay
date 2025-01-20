<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login V15</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" href="{{ asset('login-kasir-asset/images/icons/favicon.ico') }}" />
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{ asset('login-kasir-asset/vendor/bootstrap/css/bootstrap.min.css') }}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css"
        href="{{ asset('login-kasir-asset/fonts/font-awesome-4.7.0/css/font-awesome.min.css') }}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css"
        href="{{ asset('login-kasir-asset/fonts/Linearicons-Free-v1.0.0/icon-font.min.css') }}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{ asset('login-kasir-asset/vendor/animate/animate.css') }}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css"
        href="{{ asset('login-kasir-asset/vendor/css-hamburgers/hamburgers.min.css') }}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css"
        href="{{ asset('login-kasir-asset/vendor/animsition/css/animsition.min.css') }}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{ asset('login-kasir-asset/vendor/select2/select2.min.css') }}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css"
        href="{{ asset('login-kasir-asset/vendor/daterangepicker/daterangepicker.css') }}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{ asset('login-kasir-asset/css/util.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('login-kasir-asset/css/main.css') }}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css"
        href="{{ asset('login-kasir-asset/vendor/pincode/bootstrap-pincode-input.css') }}">

    <style>
        /* Latar belakang semi-transparan */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            /* Background semi-transparan */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1050;
            /* Tetap di atas elemen lainnya */
        }
    </style>
</head>

<body>

    <div class="limiter">
        <div class="container-login100" style="background-color: #d03c3c">
            <div id="preloader">
                <img src="{{ asset('img/Logo Red.png') }}" alt="Loading" class="spinner" height="100">
            </div>
            <div class="wrap-login100">
                <div class="login100-form-title"
                    style="background-image: url({{ asset('img/logo.jpeg') }}); 
                background-size: contain;  
                background-position: center;">
                    <span class="login100-form-title-1">
                        Login Kasir
                    </span>
                </div>

                <form class="login100-form validate-form" method="POST" action="{{ route('auth/kasir') }}">
                    @csrf
                    <div class="wrap-input100 validate-input m-b-26" data-validate="Username is required"
                        style="border-bottom: 0; !important">
                        <span class="label-input100">Outlet</span>
                        {{-- <input class="input100" type="text" name="username" placeholder="Enter username"> --}}
                        <select name="outlet_id" id="outlet_id" class="custom-select w-100" required>
                            <option selected disabled>Pilih Outlet</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        {{-- <span class="focus-input100"></span> --}}
                    </div>

                    <div class="wrap-input100 validate-input m-b-26" data-validate="Username is required"
                        style="border-bottom: 0; !important">
                        <span class="label-input100">Akun</span>
                        {{-- <input class="input100" type="text" name="username" placeholder="Enter username"> --}}
                        <select name="email" id="akun" class="custom-select w-100" required>
                            <option selected disabled>Pilih Akun</option>
                        </select>
                        {{-- <span class="focus-input100"></span> --}}
                    </div>

                    <div class="wrap-input100 validate-input m-b-18" data-validate = "Password is required">
                        <span class="label-input100">Pin</span>
                        {{-- <input class="input100" type="password" name="pint" placeholder="Enter Pin"> --}}
                        <input type="number" name="pin" id="pincode-input7" value="" required>
                        <span class="focus-input100"></span>
                        @error('pin')
                            <div style="color:red">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- <div class="flex-sb-m w-full p-b-30">
						<div class="contact100-form-checkbox">
							<input class="input-checkbox100" id="ckb1" type="checkbox" name="remember-me">
							<label class="label-checkbox100" for="ckb1">
								Remember me
							</label>
						</div>

						<div>
							<a href="#" class="txt1">
								Forgot Password?
							</a>
						</div>
					</div> --}}

                    <div class="container-login100-form-btn">
                        <button type="submit" id="login-btn" class="login100-form-btn">
                            Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--===============================================================================================-->
    <script src="{{ asset('login-kasir-asset/vendor/jquery/jquery-3.2.1.min.js') }}"></script>
    <!--===============================================================================================-->
    <script src="{{ asset('login-kasir-asset/vendor/animsition/js/animsition.min.js') }}"></script>
    <!--===============================================================================================-->
    <script src="{{ asset('login-kasir-asset/vendor/bootstrap/js/popper.js') }}"></script>
    <script src="{{ asset('login-kasir-asset/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <!--===============================================================================================-->
    <script src="{{ asset('login-kasir-asset/vendor/select2/select2.min.js') }}"></script>
    <!--===============================================================================================-->
    <script src="{{ asset('login-kasir-asset/vendor/daterangepicker/moment.min.js') }}"></script>
    <script src="{{ asset('login-kasir-asset/vendor/daterangepicker/daterangepicker.js') }}"></script>
    <!--===============================================================================================-->
    <script src="{{ asset('login-kasir-asset/vendor/daterangepicker/daterangepicker.js') }}"></script>
    <!--===============================================================================================-->
    <script src="{{ asset('login-kasir-asset/js/main.js') }}"></script>
    <!--===============================================================================================-->
    <script src="{{ asset('login-kasir-asset/vendor/pincode/bootstrap-pincode-input.js') }}"></script>

    <script>
        $(".select2").select2();

        $('#pincode-input7').pincodeInput({
            hidedigits: false,
            inputs: 6,
            inputclass: 'form-control-lg',
            complete: function(value, e, errorElement) {
                console.log(value)
                // $("#pincode-callback").html("Complete callback from 6-digit test: Current value: " + value);

                // $(errorElement).html("I'm sorry, but the code not correct");
            }
        });

        function showLoader(show = true) {
            console.log("masok")
            const preloader = $("#preloader");

            if (show) {
                preloader.css({
                    opacity: 1,
                    visibility: "visible",
                });
            } else {
                preloader.css({
                    opacity: 0,
                    visibility: "hidden",
                });
            }
        }

        $(document).ready(function() {
            showLoader(false)
            $('#outlet_id').change(function() {
                var outletId = $(this).val(); // Ambil nilai outlet yang dipilih  

                console.log(outletId);
                if (outletId) {
                    $.ajax({
                        url: '/get-akun/' + outletId, // URL untuk mengambil akun  
                        type: 'GET',
                        // dataType: 'json',
                        success: function(data) {
                            console.log(data);
                            $('#akun').empty(); // Kosongkan dropdown akun  
                            $('#akun').append(
                                '<option selected disabled>Pilih Akun</option>'
                                ); // Tambahkan opsi default  

                            // Tambahkan opsi akun ke dropdown  
                            $.each(data, function(key, value) {
                                $('#akun').append('<option value="' + value.email +
                                    '">' +
                                    value.name + '</option>');
                            });
                        },
                        error: function() {
                            console.log('Error fetching data');
                        }
                    });
                } else {
                    $('#akun').empty(); // Kosongkan dropdown akun jika tidak ada outlet yang dipilih  
                    $('#akun').append('<option selected disabled>Pilih Akun</option>');
                }
            });

            $("#login-btn").on('click', function(){
                showLoader();
            });
        });
    </script>

</body>

</html>
