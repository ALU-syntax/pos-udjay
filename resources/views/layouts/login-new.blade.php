<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iziToast.min.css') }}">
</head>

<body>
    <div class="scroll-down">SCROLL DOWN
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
            <path
                d="M16 3C8.832031 3 3 8.832031 3 16s5.832031 13 13 13 13-5.832031 13-13S23.167969 3 16 3zm0 2c6.085938 0 11 4.914063 11 11 0 6.085938-4.914062 11-11 11-6.085937 0-11-4.914062-11-11C5 9.914063 9.914063 5 16 5zm-1 4v10.28125l-4-4-1.40625 1.4375L16 23.125l6.40625-6.40625L21 15.28125l-4 4V9z" />
        </svg>
    </div>
    <div class="container"></div>
    <div class="modal">
        <div class="modal-container">
            <div class="modal-left">
                <h1 class="modal-title">Welcome!</h1>
                <p class="modal-desc">POS UDJAYA</p>
                {{-- Alert global untuk pesan gagal --}}
                <div id="login-alert" class="alert alert-danger d-none" role="alert"></div>
                {{-- <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf
                    <div class="input-block">
                        <label for="email" :value="__('Email')" class="input-label">Email</label>
                        <input type="email" name="email" id="email" placeholder="Email">
                    </div>
                    <div class="input-block">
                        <label for="password" :value="__('Password')" class="input-label">Password</label>
                        <input type="password" name="password" id="password" placeholder="Password">
                    </div>
                    @if (Route::has('password.request'))
                        <div class="modal-buttons">
                            <a href="" class="">Forgot your password?</a>
                            <button type="submit" class="input-button">Login</button>
                        </div>
                        <p class="sign-up">Don't have an account? <a href="#">Sign up now</a></p>
                    @endif
                </form> --}}
                <form id="login-form" action="{{ route('login') }}" method="POST" autocomplete="on">
                    @csrf
                    <div class="input-block">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" class="form-control"
                            value="{{ old('email') }}" required>
                        <div class="invalid-feedback" id="email-error"></div>
                    </div>

                    <div class="input-block">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" name="password" type="password" class="form-control" required>
                        <div class="invalid-feedback" id="password-error"></div>
                    </div>

                    {{-- <div class="form-check input-block">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div> --}}

                    <button id="btn-login" type="submit" class="btn btn-primary w-100 input-button">
                        Masuk
                    </button>
                </form>
            </div>
            <div class="modal-right">
                {{-- <img src="https://images.unsplash.com/photo-1512486130939-2c4f79935e4f?ixlib=rb-0.3.5&ixid=eyJhcHBfaWQiOjEyMDd9&s=dfd2ec5a01006fd8c4d7592a381d3776&auto=format&fit=crop&w=1000&q=80"
                    alt=""> --}}
                <img src="{{ asset('img/Logo Cream.png') }}" style="height:450px; width:350px; object-fit: contain">
            </div>
            <button class="icon-button close-button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50">
                    <path
                        d="M 25 3 C 12.86158 3 3 12.86158 3 25 C 3 37.13842 12.86158 47 25 47 C 37.13842 47 47 37.13842 47 25 C 47 12.86158 37.13842 3 25 3 z M 25 5 C 36.05754 5 45 13.94246 45 25 C 45 36.05754 36.05754 45 25 45 C 13.94246 45 5 36.05754 5 25 C 5 13.94246 13.94246 5 25 5 z M 16.990234 15.990234 A 1.0001 1.0001 0 0 0 16.292969 17.707031 L 23.585938 25 L 16.292969 32.292969 A 1.0001 1.0001 0 1 0 17.707031 33.707031 L 25 26.414062 L 32.292969 33.707031 A 1.0001 1.0001 0 1 0 33.707031 32.292969 L 26.414062 25 L 33.707031 17.707031 A 1.0001 1.0001 0 0 0 32.980469 15.990234 A 1.0001 1.0001 0 0 0 32.292969 16.292969 L 25 23.585938 L 17.707031 16.292969 A 1.0001 1.0001 0 0 0 16.990234 15.990234 z">
                    </path>
                </svg>
            </button>
        </div>
        <button class="modal-button">Click here to login</button>
    </div>


    <script src="{{ asset('js/core/jquery-3.7.1.min.js') }}"></script>
    {{-- IZI TOAST --}}
    <script src="{{ asset('js/plugin/izitoast/iziToast.min.js') }}"></script>

    <script>
        const body = document.querySelector("body");
        const modal = document.querySelector(".modal");
        const modalButton = document.querySelector(".modal-button");
        const closeButton = document.querySelector(".close-button");
        const scrollDown = document.querySelector(".scroll-down");
        let isOpened = false;

        const openModal = () => {
            modal.classList.add("is-open");
            body.style.overflow = "hidden";
            modalButton.style.zIndex = "-1";
        };

        const closeModal = () => {
            modal.classList.remove("is-open");
            body.style.overflow = "initial";
        };

        window.addEventListener("scroll", () => {
            if (window.scrollY > window.innerHeight / 3 && !isOpened) {
                isOpened = true;
                scrollDown.style.display = "none";
                openModal();
            }
        });

        modalButton.addEventListener("click", openModal);
        closeButton.addEventListener("click", closeModal);

        document.onkeydown = evt => {
            evt = evt || window.event;
            evt.keyCode === 27 ? closeModal() : false;
        };

        $(function() {
            // setup CSRF untuk semua request jQuery
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            const $form = $('#login-form');
            const $btn = $('#btn-login');
            const $alert = $('#login-alert');
            const $email = $('#email');
            const $password = $('#password');

            function clearErrors() {
                $alert.addClass('d-none').text('');
                [$email, $password].forEach($el => $el.removeClass('is-invalid'));
                $('#email-error').text('');
                $('#password-error').text('');
            }

            function setLoading(state) {
                if (state) {
                    $btn.prop('disabled', true).data('orig', $btn.html()).html('Memproses...');
                } else {
                    $btn.prop('disabled', false).html($btn.data('orig') || 'Masuk');
                }
            }

            // Validasi ringan di client
            function clientValidate() {
                let ok = true;
                clearErrors();

                const emailVal = ($email.val() || '').trim();
                const passVal = ($password.val() || '').trim();

                if (!emailVal) {
                    $email.addClass('is-invalid');
                    $('#email-error').text('Email wajib diisi.');
                    ok = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
                    $email.addClass('is-invalid');
                    $('#email-error').text('Format email tidak valid.');
                    ok = false;
                }

                if (!passVal) {
                    $password.addClass('is-invalid');
                    $('#password-error').text('Password wajib diisi.');
                    ok = false;
                }

                return ok;
            }

            $form.on('submit', function(e) {
                e.preventDefault(); // <-- mencegah reload
                if (!clientValidate()) return;

                setLoading(true);

                $.post($form.attr('action'), $form.serialize())
                    .done(function(res) {
                        // sukses → redirect (pakai URL dari server atau fallback)
                        window.location.href = res.redirect;
                    })
                    .fail(function(xhr) {
                        // 422: validasi Laravel
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.email) {
                                $email.addClass('is-invalid');
                                $('#email-error').text(errors.email[0]);
                            }
                            if (errors.password) {
                                $password.addClass('is-invalid');
                                $('#password-error').text(errors.password[0]);
                            }
                            // tampilkan error pertama ke alert juga (opsional)
                            const first = Object.values(errors)[0][0];
                            $alert.removeClass('d-none').text(first);
                        }
                        // 401: kredensial salah
                        else if (xhr.status === 401) {
                            // $alert.removeClass('d-none').text(xhr.responseJSON?.message ||
                            //     'Email atau password salah.');

                            iziToast["error"]({
                                title: "Gagal",
                                message: "Email atau password salah",
                                position: 'topRight'
                            });

                            // (opsional) highlight password saja:
                            // $password.addClass('is-invalid');
                            // $('#password-error').text('Password salah.');
                        }
                        // 419: CSRF mismatch / session timeout
                        else if (xhr.status === 419) {
                            $alert.removeClass('d-none').text(
                                'Sesi kedaluwarsa. Silakan muat ulang halaman dan coba lagi.');
                        } else {
                            $alert.removeClass('d-none').text(
                                'Terjadi kesalahan. Coba lagi beberapa saat.');
                        }
                    })
                    .always(function() {
                        setLoading(false);
                    });
            });
        });
    </script>
</body>

</html>
