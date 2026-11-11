{{--
    Kerangka halaman auth di luar login (lupa password, atur ulang password).
    Memakai kartu yang sama dengan halaman login (css/login.css), tetapi kartunya langsung terbuka.
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('app.name') }}</title>

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    @include('auth.partials.password-toggle-assets')

    {{-- Penyesuaian ditaruh inline agar tidak tertahan cache login.css --}}
    <style>
        /* Latar tidak perlu bisa di-scroll seperti di halaman login */
        .container {
            height: 100vh;
        }

        /* Kartu ikut bisa di-scroll bila layar lebih pendek dari kartu (mis. keyboard HP terbuka) */
        .modal.is-open {
            justify-content: flex-start;
            padding: 24px 0;
            overflow-y: auto;
        }

        .modal.is-open .modal-container {
            position: relative;
            margin: auto;
        }

        .modal-left {
            padding-top: 40px;
            padding-bottom: 30px;
        }

        .modal-desc {
            font-size: 14px;
            line-height: 1.5;
        }

        .auth-alert {
            margin: 0 0 20px;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.5;
        }

        .auth-alert--success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .auth-alert--error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .auth-alert a {
            color: inherit;
            font-weight: 700;
        }

        .input-block.is-invalid {
            border-color: #b91c1c;
            margin-bottom: 6px;
        }

        .auth-error {
            margin: 0 0 16px;
            color: #b91c1c;
            font-size: 13px;
        }

        .auth-hint {
            margin: -14px 0 16px;
            color: rgba(0, 0, 0, 0.5);
            font-size: 12px;
        }

        .input-block input[readonly] {
            color: rgba(0, 0, 0, 0.5);
        }

        .auth-submit {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
        }

        .auth-submit[disabled] {
            opacity: 0.7;
            cursor: default;
        }

        .auth-links {
            margin: 18px 0 0;
            text-align: center;
            font-size: 14px;
        }

        .auth-links a {
            color: #8c7569;
        }

        .auth-links a:hover {
            color: #55311c;
        }
    </style>
</head>

<body>
    <div class="container"></div>
    <div class="modal is-open">
        <div class="modal-container">
            <div class="modal-left">
                @yield('content')
            </div>
            <div class="modal-right">
                <img src="{{ asset('img/Logo Cream.png') }}" alt="" style="height:450px; width:350px; object-fit: contain">
            </div>
        </div>
    </div>

    <script>
        // Cegah kirim ganda: tombol dimatikan setelah form dikirim
        document.querySelectorAll("form[data-auth-form]").forEach((form) => {
            form.addEventListener("submit", () => {
                const button = form.querySelector("button[type=submit]");
                if (!button) return;
                button.disabled = true;
                button.textContent = button.dataset.loadingText || "Memproses...";
            });
        });
    </script>
</body>

</html>
