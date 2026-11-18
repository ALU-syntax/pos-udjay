{{-- Date picker B2B (daterangepicker + tema .b2b-drp) yang dipakai bersama: periode kontrak, jatuh tempo kasir. --}}
{{-- Input: <div class="b2b-range"><i class="fas fa-calendar-alt"></i><input readonly class="form-control"><span class="b2b-range__days"></span></div> di dalam .b2b-form. --}}
{{-- Popup: tambahkan class b2b-drp ke picker.container. --}}
@once
    @push('css')
        <style>
            .b2b-range { position: relative; }
            .b2b-range > i { position: absolute; top: 50%; left: .9rem; transform: translateY(-50%); font-size: .85rem; color: var(--ob-primary); pointer-events: none; }
            /* Kaiadmin memberi input readonly latar abu-abu + opacity dengan !important, jadi harus dilawan dengan !important juga. */
            .b2b-form .b2b-range .form-control[readonly] {
                padding-left: 2.4rem; padding-right: 5.5rem; color: var(--ob-text); font-weight: 600; cursor: pointer;
                background: var(--ob-surface) !important; border-color: var(--ob-border) !important; opacity: 1 !important;
            }
            .b2b-form .b2b-range .form-control[readonly]:hover { border-color: var(--ob-border-strong) !important; }
            .b2b-form .b2b-range .form-control[readonly].is-open { border-color: var(--ob-primary) !important; box-shadow: 0 0 0 3px rgba(21, 114, 232, .15); }
            .b2b-range__days {
                position: absolute; top: 50%; right: .6rem; transform: translateY(-50%); padding: .15rem .55rem; border-radius: 999px;
                background: var(--ob-primary-soft); color: var(--ob-primary); font-size: .72rem; font-weight: 700; pointer-events: none;
            }
            .b2b-range__days:empty { display: none; }
            .b2b-field:has(input[type=hidden].is-invalid) .b2b-range .form-control[readonly] { border-color: var(--ob-danger) !important; }

            /* Popup kalender dipasang di <body>, di luar .ob-page, jadi token warnanya didefinisikan ulang di sini. */
            .daterangepicker.b2b-drp {
                --drp-primary: #1572e8; --drp-primary-soft: #e8f1fd; --drp-primary-strong: #0f5fc4;
                --drp-text: #0f172a; --drp-text-2: #475569; --drp-muted: #94a3b8; --drp-border: #e2e8f0; --drp-surface-2: #f8fafc;
                margin-top: 10px; padding: 0; border: 1px solid var(--drp-border); border-radius: 16px; overflow: visible;
                font-family: inherit; font-size: 13px; color: var(--drp-text);
                box-shadow: 0 2px 6px rgba(15, 23, 42, .06), 0 18px 48px rgba(15, 23, 42, .16);
            }
            .daterangepicker.b2b-drp.drop-up { margin-top: -10px; }
            .daterangepicker.b2b-drp:before { border-bottom-color: var(--drp-border); }
            .daterangepicker.b2b-drp.drop-up:before { border-top-color: var(--drp-border); }

            /* Pilihan cepat */
            .daterangepicker.b2b-drp .ranges { margin: 0; padding: 14px 12px; }
            .daterangepicker.b2b-drp .ranges ul { width: 132px; display: flex; flex-direction: column; gap: 4px; }
            .daterangepicker.b2b-drp .ranges ul::before {
                content: 'Pilihan cepat'; display: block; padding: 0 10px 6px; font-size: 11px; font-weight: 700;
                letter-spacing: .04em; text-transform: uppercase; color: var(--drp-muted);
            }
            .daterangepicker.b2b-drp .ranges li {
                padding: 9px 10px; border-radius: 10px; font-size: 13px; font-weight: 600; color: var(--drp-text-2);
                transition: background-color .15s, color .15s;
            }
            .daterangepicker.b2b-drp .ranges li:hover { background: var(--drp-surface-2); color: var(--drp-text); }
            .daterangepicker.b2b-drp .ranges li.active { background: var(--drp-primary-soft); color: var(--drp-primary); }
            .daterangepicker.b2b-drp .ranges li[data-range-key='Pilih sendiri'] { margin-top: 4px; border-top: 1px dashed var(--drp-border); border-radius: 0 0 10px 10px; }
            .daterangepicker.b2b-drp.show-ranges.ltr .drp-calendar.left { border-left: 1px solid var(--drp-border); }

            /* Kalender */
            .daterangepicker.b2b-drp .drp-calendar { max-width: none; }
            .daterangepicker.b2b-drp .drp-calendar.left { padding: 14px 10px 12px 16px; }
            .daterangepicker.b2b-drp .drp-calendar.right { padding: 14px 16px 12px 10px; }
            .daterangepicker.b2b-drp .drp-calendar.left .calendar-table { padding-right: 0; }
            .daterangepicker.b2b-drp .calendar-table, .daterangepicker.b2b-drp .calendar-table table { border: 0; border-collapse: separate; border-spacing: 0 3px; }
            .daterangepicker.b2b-drp .calendar-table th, .daterangepicker.b2b-drp .calendar-table td {
                min-width: 36px; width: 36px; height: 34px; line-height: 32px; font-size: 13px; border: 0; border-radius: 0;
            }
            .daterangepicker.b2b-drp .calendar-table thead tr:first-child th { height: 38px; padding-bottom: 6px; }
            .daterangepicker.b2b-drp .calendar-table thead tr:last-child th { height: 26px; line-height: 26px; font-size: 11px; font-weight: 700; color: var(--drp-muted); cursor: default; }
            .daterangepicker.b2b-drp th.month { font-weight: 700; color: var(--drp-text); }
            .daterangepicker.b2b-drp th.prev, .daterangepicker.b2b-drp th.next { min-width: 32px; width: 32px; }
            .daterangepicker.b2b-drp th.prev span, .daterangepicker.b2b-drp th.next span { border-color: var(--drp-text-2); padding: 3px; }
            .daterangepicker.b2b-drp th.prev.available, .daterangepicker.b2b-drp th.next.available { border-radius: 10px; }
            .daterangepicker.b2b-drp th.available:hover { background: var(--drp-surface-2); }
            .daterangepicker.b2b-drp select.monthselect, .daterangepicker.b2b-drp select.yearselect {
                height: 32px; padding: 0 22px 0 9px; border: 1px solid var(--drp-border); border-radius: 9px; cursor: pointer;
                font-size: 13px; font-weight: 600; color: var(--drp-text); background-color: #fff; appearance: none; -webkit-appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' fill='none' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
                background-repeat: no-repeat; background-position: right 8px center;
            }
            .daterangepicker.b2b-drp select.monthselect { width: 58%; margin-right: 3%; }
            .daterangepicker.b2b-drp select.yearselect { width: 39%; }
            .daterangepicker.b2b-drp select:focus { outline: 0; border-color: var(--drp-primary); box-shadow: 0 0 0 3px var(--drp-primary-soft); }

            /* Tanggal: rentang terpilih jadi satu pita, tanggal mulai & berakhir berupa kapsul biru */
            .daterangepicker.b2b-drp td { position: relative; color: var(--drp-text); font-weight: 500; }
            .daterangepicker.b2b-drp td.weekend { color: var(--drp-text-2); }
            .daterangepicker.b2b-drp td.available:hover { background: var(--drp-surface-2); border-radius: 10px; color: var(--drp-text); }
            .daterangepicker.b2b-drp td.off, .daterangepicker.b2b-drp td.off.in-range,
            .daterangepicker.b2b-drp td.off.start-date, .daterangepicker.b2b-drp td.off.end-date { background: transparent; color: #cbd5e1; border-radius: 0; }
            .daterangepicker.b2b-drp td.in-range { background: var(--drp-primary-soft); color: var(--drp-primary-strong); border-radius: 0; }
            .daterangepicker.b2b-drp td.in-range:hover { background: #d6e7fc; border-radius: 0; }
            .daterangepicker.b2b-drp td.active, .daterangepicker.b2b-drp td.active:hover {
                background: var(--drp-primary); color: #fff; font-weight: 700; box-shadow: 0 4px 10px rgba(21, 114, 232, .3);
            }
            .daterangepicker.b2b-drp td.start-date { border-radius: 10px 0 0 10px; }
            .daterangepicker.b2b-drp td.end-date { border-radius: 0 10px 10px 0; }
            .daterangepicker.b2b-drp td.start-date.end-date, .daterangepicker.b2b-drp td.active:not(.start-date):not(.end-date) { border-radius: 10px; }
            .daterangepicker.b2b-drp td.today::after {
                content: ''; position: absolute; left: 50%; bottom: 4px; width: 4px; height: 4px; margin-left: -2px; border-radius: 50%; background: var(--drp-primary);
            }
            .daterangepicker.b2b-drp td.today.active::after { background: #fff; }
            .daterangepicker.b2b-drp td.off.today::after { display: none; }

            /* Footer: ringkasan rentang + tombol */
            .daterangepicker.b2b-drp .drp-buttons {
                display: flex; align-items: center; justify-content: flex-end; gap: 8px; padding: 12px 16px;
                border-top: 1px solid var(--drp-border); background: var(--drp-surface-2); border-radius: 0 0 16px 16px; line-height: 1.3;
            }
            .daterangepicker.b2b-drp .drp-selected { display: flex; flex-direction: column; gap: 2px; margin-right: auto; padding: 0; font-size: 13px; text-align: left; }
            .daterangepicker.b2b-drp .drp-selected strong { font-weight: 700; color: var(--drp-text); }
            .daterangepicker.b2b-drp .drp-selected small { font-size: 12px; color: var(--drp-muted); }
            .daterangepicker.b2b-drp .drp-selected small.is-days { color: var(--drp-primary); font-weight: 600; }
            .daterangepicker.b2b-drp .drp-buttons .btn { margin: 0; padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; }
            .daterangepicker.b2b-drp .drp-buttons .btn-light { background: #fff; border: 1px solid var(--drp-border); color: var(--drp-text-2); }
            .daterangepicker.b2b-drp .drp-buttons .btn-primary { background: var(--drp-primary); border-color: var(--drp-primary); }
            .daterangepicker.b2b-drp .drp-buttons .btn-primary:disabled { opacity: .45; }
            /* Pilih tanggal tunggal (autoApply): tanpa footer, langsung tertutup setelah tanggal diklik. */
            .daterangepicker.b2b-drp.auto-apply .drp-buttons { display: none; }
            .daterangepicker.b2b-drp.single .drp-calendar.left { padding: 14px 16px 12px; }
            @media (max-width: 563px) {
                .daterangepicker.b2b-drp .ranges ul { width: 100%; flex-direction: row; flex-wrap: wrap; }
                .daterangepicker.b2b-drp .ranges ul::before { width: 100%; }
                .daterangepicker.b2b-drp .ranges li[data-range-key='Pilih sendiri'] { margin-top: 0; border-top: 0; border-radius: 10px; }
                .daterangepicker.b2b-drp.show-ranges.ltr .drp-calendar.left { border-left: 0; border-top: 1px solid var(--drp-border); }
                .daterangepicker.b2b-drp .drp-calendar.left, .daterangepicker.b2b-drp .drp-calendar.right { padding: 12px 14px; }
                .daterangepicker.b2b-drp .drp-buttons { flex-wrap: wrap; }
            }
        </style>
    @endpush
@endonce
