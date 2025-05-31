<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ config('app.name', 'POS UDJAYA') }}</title>

    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="{{ asset('img/kaiadmin/favicon.ico') }}" type="image/x-icon" />

    <meta name="csrf_token" content="{{ csrf_token() }}">
    <!-- Fonts and icons -->
    <script src="{{ asset('js/plugin/webfont/webfont.min.js') }}"></script>
    <script>
        WebFont.load({
            google: {
                "families": ["Public Sans:300,400,500,600,700"]
            },
            custom: {
                "families": ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands",
                    "simple-line-icons"
                ],
                urls: ['{{ asset('css/fonts.min.css') }}']
            },
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <style>
        .spinner {
            /* animation: spin 1s linear infinite; */
            animation: bounce 1.5s infinite;
        }

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

        /* Definisi keyframes untuk animasi bouncing */
        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
                /* Posisi awal */
            }

            50% {
                transform: translateY(-55px);
                /* Melompat ke atas */
            }
        }
    </style>

    <!-- CSS Files -->

    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/plugins.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaiadmin.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/iziToast.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css"
        integrity="sha512-HK5fgLBL+xu6dm/Ii3z4xhlSUyZgTT9tuc/hSrtw6uzJOvgRr2a9jyxxT1ely+B+xFAmJKVSTbpM/CuL7qxO8w=="
        crossorigin="anonymous" />

    <link rel="stylesheet" href="{{ asset('vendor/fancybox/fancybox.css') }}" />

    @stack('css')
</head>

<body>
    <div class="wrapper">
        <!-- Loading Animation -->
        <div id="preloader" style="visibility: hidden; opacity: 0;">
            <img src="{{ asset('img/Logo Red.png') }}" alt="Loading" class="spinner" height="100">
        </div>
        @include('layouts.sidebar')

        <div class="modal fade" id="modal_action" tabindex="-1" role="dialog" aria-hidden="true">
        </div>

        <div class="main-panel">
            @include('layouts.navigation')

            <div class="container">
                <div class="page-inner">
                    @yield('content')
                </div>
            </div>

            @include('layouts.footer')
        </div>
    </div>

    <!--   Core JS Files   -->
    <script src="{{ asset('js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/core/popper.min.js') }}"></script>
    <script src="{{ asset('js/core/bootstrap.min.js') }}"></script>

    <!-- jQuery Scrollbar -->
    <script src="{{ asset('js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>

    <!-- Chart JS -->
    <script src="{{ asset('js/plugin/chart.js/chart.min.js') }}"></script>

    <!-- jQuery Sparkline -->
    <script src="{{ asset('js/plugin/jquery.sparkline/jquery.sparkline.min.js') }}"></script>

    <!-- Chart Circle -->
    <script src="{{ asset('js/plugin/chart-circle/circles.min.js') }}"></script>

    <!-- Datatables -->
    <script src="{{ asset('js/plugin/datatables/datatables.min.js') }}"></script>

    <!-- Bootstrap Notify -->
    <script src="{{ asset('js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>

    <!-- jQuery Vector Maps -->
    <script src="{{ asset('js/plugin/jsvectormap/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('js/plugin/jsvectormap/world.js') }}"></script>

    <!-- Sweet Alert -->
    {{-- <script src="{{ asset('js/plugin/sweetalert/sweetalert.min.js') }}"></script> --}}
    <script src="{{ asset('js/plugin/sweetalert2/sweetalert2.js') }}"></script>

    {{-- SELECT 2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- IZI TOAST --}}
    <script src="{{ asset('js/plugin/izitoast/iziToast.min.js') }}"></script>

    {{-- Moment --}}
    <script type="text/javascript" src="{{ asset('vendor/moment/moment.min.js') }}"></script>

    {{-- DateRange Picker --}}
    <script type="text/javascript" src="{{ asset('vendor/daterangepicker/daterangepicker.min.js') }}"></script>

    {{-- FancyBox --}}
    <script src="{{ asset('vendor/fancybox/fancybox.js') }}"></script>

    <!-- Kaiadmin JS -->
    <script src="{{ asset('js/kaiadmin.min.js') }}"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name=csrf_token]').attr('content')
            }
        })

        var tmpDataProductModifier = [];
        $('.dropdown-custom').select2();
        $(".select2InsideModal").select2({
            dropdownParent: $("#modal_action")
        });

        // $('#offcanvasMenu').offcanvas('show');

        // // Close Offcanvas Programmatically
        // $('#offcanvasMenu').offcanvas('hide');

        function handleDelete(datatable, customMessage = false, onSuccessAction) {
            let message = customMessage ? customMessage : "You won't be able to revert this!";
            $('#' + datatable).on('click', '.delete', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleAjax(this.href, 'delete').onSuccess(function(res) {
                            onSuccessAction && onSuccessAction(res)
                            // showToast(res.status, res.message)
                            window.LaravelDataTables[datatable].ajax.reload(null, false)
                        }, false).excute();
                        console.log(result);
                        showToast('success', "Data berhasil dihapus");
                    }
                })

            });
        }

        function generateRandomID() {
            return 'temp-' + Date.now() + '-' + Math.floor(Math.random() * 10000);
        }


        function handleAjax(url, method = 'get') {

            function onSuccess(cb, runDefault = true) {
                this.onSuccessCallback = cb
                this.runDefaultSuccessCallback = runDefault

                return this
            }

            function excute() {
                $.ajax({
                    url,
                    method,
                    beforeSend: function() {
                        // showLoading()
                    },
                    complete: function() {
                        // hideLoading(false)
                    },
                    success: (res) => {
                        if (this.runDefaultSuccessCallback) {
                            const modal = $('#modal_action');
                            modal.html(res);
                            modal.modal('show');
                        }

                        this.onSuccessCallback && this.onSuccessCallback(res)
                    },
                    error: function(err) {
                        console.log(err);
                    }
                });
            }

            function onError(cb) {
                this.onErrorCallback = cb
                return this
            }

            return {
                excute,
                onSuccess,
                runDefaultSuccessCallback: true
            }

        }

        function handleAction(datatable, onShowAction, onSuccessAction) {
            $('.main-content').on('click', '.action', function(e) {
                e.preventDefault();
                handleAjax(this.href).onSuccess(function(res) {
                    onShowAction && onShowAction(res)
                    handleFormSubmit('#form_action')
                        .setDataTable(datatable)
                        .onSuccess(function(res) {
                            onSuccessAction && onSuccessAction(res)
                        })
                        .init();
                }).excute();
            });
        }

        function handleFormSubmit(selector) {
            function init() {
                const _this = this;
                $(selector).on('submit', function(e) {
                    e.preventDefault();
                    const _form = this
                    let data = new FormData(_form);
                    let dataForm;
                    let dataCustom = false;
                    data.forEach(function(item, index) {
                        if (index == "input-modifier-product") {
                            dataCustom = true;
                        }
                    });

                    if (dataCustom) {
                        const token = document.querySelector('meta[name="csrf_token"]').getAttribute('content');
                        dataForm = new FormData();
                        dataForm.append("_token", token);
                        dataForm.append("_method", 'put');
                        tmpDataProductModifier.forEach(function(item) {
                            dataForm.append("products[]", item);
                        });
                    } else {
                        dataForm = data;
                    }

                    $.ajax({
                        url: this.action,
                        method: this.method,
                        data: dataForm,
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            $(_form).find('.is-invalid').removeClass('is-invalid')
                            $(_form).find(".invalid-feedback").remove()
                            submitLoader().show()
                        },
                        success: (res) => {
                            if (_this.runDefaultSuccessCallback) {
                                $('#modal_action').modal('hide')
                                showToast(res.status, res.message)
                            }

                            _this.onSuccessCallback && _this.onSuccessCallback(res)
                            _this.dataTableId && window.LaravelDataTables[_this.dataTableId].ajax
                                .reload(null, false)

                        },
                        complete: function() {
                            submitLoader().hide()
                        },
                        error: function(err) {
                            const errors = err.responseJSON?.errors;

                            console.log(err);
                            console.log(err.responseJSON);
                            console.log(err.responseJSON?.errors);
                            if (errors) {
                                for (let [key, message] of Object.entries(errors)) {
                                    console.log(message);
                                    $(`[name=${key}]`).addClass('is-invalid')
                                        .parent()
                                        .append(
                                            `<div class="invalid-feedback">${message}</div>`
                                        )
                                }
                            }

                            showToast('error', err.responseJSON?.message)
                        }
                    })
                })
            }

            function onSuccess(cb, runDefault = true) {
                this.onSuccessCallback = cb
                this.runDefaultSuccessCallback = runDefault

                return this
            }

            function setDataTable(id) {
                this.dataTableId = id

                return this
            }

            return {
                init,
                runDefaultSuccessCallback: true,
                onSuccess,
                setDataTable
            }
        }

        function submitLoader(formId = '#form_action') {
            const button = $(formId).find('button[type="submit"]');

            function show() {
                button.addClass("btn-load").attr("disabled", true).html(
                    `<span class="d-flex align-items-center">
            <span class="spinner-border flex-shrink-0"></span><span class="flex-grow-1 ms-2"> Loading...  </span></span>`
                );

            }

            function hide(text = "Save") {
                button.removeClass("btn-load").removeAttr("disabled").text(text);
            }

            return {
                show,
                hide,
            };
        }

        function showToast(status = 'success', message) {
            console.log(message);
            iziToast[status]({
                title: status == 'success' ? 'Success' : 'Error',
                message: message,
                position: 'topRight'
            });
        }

        function getAmount(money) {
            let cleanString = money.replace(/([^0-9\.,])/gi, '');
            let onlyNumbersString = money.replace(/([^0-9])/gi, '');

            let separatorsCountToBeErased = cleanString.length - onlyNumbersString.length - 1;

            let stringWithCommaOrDot = cleanString.replace(/([,\.])/g, '', separatorsCountToBeErased);
            let removedThousandSeparator = stringWithCommaOrDot.replace(/(\.|,)(?=[0-9]{3,}$)/g, '');

            return parseFloat(removedThousandSeparator.replace(',', '.'));
        }

        function formatRupiah(angka, prefix) {
            var number_string = angka.replace(/[^,\d]/g, "").toString(),
                split = number_string.split(","),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? "." : "";
                rupiah += separator + ribuan.join(".");
            }

            rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;
            return prefix == undefined ? rupiah : rupiah ? "Rp. " + rupiah : "";
        }

        function showLoader(show = true) {
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
    </script>

    @stack('js')
</body>

</html>
