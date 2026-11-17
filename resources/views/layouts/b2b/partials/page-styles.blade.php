{{-- Gaya halaman B2B (kartu, daftar, detail, form) dipakai menu Customer & Kontrak. Include setelah layouts.b2b.partials.assets. --}}
@push('css')
    <style>
        /* ----- Kartu: ob-card tidak punya padding sendiri, jadi isinya dibagi kepala / badan / kaki ----- */
        .b2b-card__head {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem 1rem;
            padding: 1rem 1.25rem; border-bottom: 1px solid var(--ob-border);
        }
        .b2b-card__title { display: flex; align-items: center; gap: .55rem; margin: 0; font-size: .95rem; font-weight: 700; color: var(--ob-text); }
        .b2b-card__title > i { width: 1.1rem; text-align: center; font-size: .85rem; color: var(--ob-primary); }
        .b2b-card__desc { margin: .2rem 0 0; font-size: .8rem; line-height: 1.45; color: var(--ob-muted); }
        .b2b-card__body { padding: 1.25rem; }
        .b2b-card__body--flush { padding: 0; }
        .b2b-card__foot {
            padding: .75rem 1.25rem; border-top: 1px solid var(--ob-border); border-radius: 0 0 var(--ob-radius) var(--ob-radius);
            background: var(--ob-surface-2); font-size: .78rem; line-height: 1.6; color: var(--ob-muted);
        }
        .b2b-count {
            display: inline-grid; place-items: center; min-width: 1.35rem; height: 1.35rem; padding: 0 .4rem;
            border-radius: 999px; background: var(--ob-border); color: var(--ob-text-2); font-size: .7rem; font-weight: 700;
        }

        /* Kaiadmin memberi .badge margin-left:auto, yang mendorong badge ke kanan di dalam flex */
        .ob-page .badge { margin-left: 0; }

        /* ----- Avatar inisial; warna mengikuti jenis customer ----- */
        .b2b-avatar {
            display: inline-grid; place-items: center; flex-shrink: 0; width: 40px; height: 40px; border-radius: 12px;
            background: var(--ob-primary-soft); color: var(--ob-primary); font-size: .8rem; font-weight: 700; letter-spacing: .03em;
        }
        .b2b-avatar--cv { background: #ede9fe; color: #6d28d9; }
        .b2b-avatar--individual { background: #ccfbf1; color: #0f766e; }
        .b2b-avatar--lg { width: 56px; height: 56px; border-radius: 16px; font-size: 1.1rem; }
        .b2b-avatar.is-inactive { background: var(--ob-border); color: var(--ob-muted); }

        /* ----- Daftar customer ----- */
        .ob-cell-stack { display: flex; flex-direction: column; align-items: flex-start; gap: .2rem; min-width: 0; }
        .b2b-cust-cell { display: flex; align-items: center; gap: .75rem; min-width: 240px; }
        .b2b-cust-cell__name { font-weight: 600; color: var(--ob-text); text-decoration: none; }
        .b2b-cust-cell__name:hover { color: var(--ob-primary); }
        .b2b-cell-strong { font-weight: 600; color: var(--ob-text); }
        .ob-page .ob-list-filters .ob-field label { font-size: .72rem !important; color: var(--ob-muted) !important; }
        .ob-page .ob-list-filters .form-select { height: 42px; padding: 0 2.25rem 0 .85rem; }

        /* ----- Kepala halaman detail ----- */
        .b2b-profile { display: flex; align-items: center; gap: 1rem; min-width: 0; }
        .b2b-profile .ob-header__title { overflow-wrap: anywhere; }
        .b2b-profile__meta { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .6rem; margin-top: .5rem; font-size: .85rem; color: var(--ob-text-2); }
        .b2b-profile__meta a { font-weight: 600; }
        .b2b-crumb-sep { margin: 0 .3rem; color: var(--ob-border-strong); }

        /* Kartu statistik: buang margin bawah bawaan ob-card (jarak sudah dari grid); di HP jadi 2 kolom */
        .ob-page .ob-stats .ob-stat { margin-bottom: 0; }
        @media (max-width: 575.98px) {
            .ob-page .ob-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
            .ob-page .ob-stats .ob-stat { flex-direction: column; align-items: flex-start; gap: .6rem; padding: .9rem; }
            .ob-page .ob-stats .ob-stat__value { font-size: 1.1rem; }
        }

        /* ----- Daftar keterangan (label kiri, nilai kanan) ----- */
        .b2b-dl { margin: 0; }
        .b2b-dl__row { display: grid; grid-template-columns: 96px minmax(0, 1fr); gap: .75rem; padding: .75rem 0; border-top: 1px solid var(--ob-border); }
        .b2b-dl__row:first-child { border-top: 0; padding-top: 0; }
        .b2b-dl__row:last-child { padding-bottom: 0; }
        .b2b-dl dt { font-size: .8rem; font-weight: 500; color: var(--ob-muted); }
        .b2b-dl dd { margin: 0; font-size: .875rem; font-weight: 500; color: var(--ob-text); overflow-wrap: anywhere; }
        .b2b-dl dd:empty::before { content: "—"; font-weight: 400; color: var(--ob-muted); }
        .b2b-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
        .b2b-tag {
            display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .5rem; border: 1px solid var(--ob-border); border-radius: 6px;
            background: var(--ob-surface-2); color: var(--ob-text-2); font-size: .74rem; font-weight: 600; white-space: nowrap;
        }
        .b2b-tag--primary { border-color: transparent; background: var(--ob-primary-soft); color: var(--ob-primary); }

        /* ----- Tabel di dalam kartu ----- */
        .b2b-table--card th { padding: .7rem 1.25rem; background: var(--ob-surface-2); }
        .b2b-table--card td { padding: .85rem 1.25rem; border-bottom: 1px solid var(--ob-border); vertical-align: middle; }
        .b2b-table--card a { font-weight: 600; text-decoration: none; }

        /* ----- Keadaan kosong ----- */
        .b2b-empty { display: flex; flex-direction: column; align-items: center; gap: .15rem; padding: 2rem 1.25rem; text-align: center; }
        .b2b-empty__icon { display: inline-grid; place-items: center; width: 44px; height: 44px; margin-bottom: .5rem; border-radius: 12px; background: var(--ob-surface-2); border: 1px solid var(--ob-border); color: var(--ob-muted); }
        .b2b-empty__title { margin: 0; font-size: .9rem; font-weight: 600; color: var(--ob-text); }
        .b2b-empty__text { margin: 0; max-width: 34ch; font-size: .8rem; line-height: 1.5; color: var(--ob-muted); }

        /* ----- Zona hapus ----- */
        .b2b-danger-zone { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1rem; padding: 1rem 1.25rem; }
        .b2b-danger-zone__title { margin: 0; font-size: .875rem; font-weight: 600; color: var(--ob-text); }
        .b2b-danger-zone__text { margin: .15rem 0 0; font-size: .8rem; line-height: 1.5; color: var(--ob-muted); }
        .b2b-danger-zone > div { flex: 1 1 200px; }

        /* ----- Form ----- */
        .b2b-form-grid { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 1.25rem; align-items: start; }
        @media (max-width: 1199.98px) { .b2b-form-grid { grid-template-columns: minmax(0, 1fr); } }

        .b2b-fields { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 1rem; }
        .b2b-fields > * { grid-column: span 12; min-width: 0; }
        @media (min-width: 768px) {
            .b2b-fields > .b2b-col-3 { grid-column: span 3; }
            .b2b-fields > .b2b-col-4 { grid-column: span 4; }
            .b2b-fields > .b2b-col-5 { grid-column: span 5; }
            .b2b-fields > .b2b-col-6 { grid-column: span 6; }
        }

        .b2b-form .form-label { display: block; margin: 0 0 .35rem; font-size: .8rem !important; font-weight: 600; color: var(--ob-text-2) !important; }
        .b2b-required { color: var(--ob-danger); }
        .b2b-optional { font-weight: 400; color: var(--ob-muted); }
        .b2b-form .form-control {
            min-height: 42px; padding: .55rem .85rem; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm);
            background: var(--ob-surface); color: var(--ob-text); font-size: .875rem; transition: border-color .15s ease, box-shadow .15s ease;
        }
        .b2b-form textarea.form-control { line-height: 1.5; resize: vertical; }
        .b2b-form .form-control::placeholder { color: #94a3b8; }
        .b2b-form .form-control:focus { border-color: var(--ob-primary); box-shadow: 0 0 0 3px rgba(21, 114, 232, .15); outline: none; }
        .b2b-form .form-control.is-invalid { border-color: var(--ob-danger); background-image: none; }
        .b2b-form .invalid-feedback { margin-top: .35rem; font-size: .78rem; color: var(--ob-danger); }
        .b2b-help { display: block; margin-top: .4rem; font-size: .78rem; line-height: 1.5; color: var(--ob-muted); }
        .b2b-group-label { display: block; margin-bottom: .5rem; font-size: .8rem; font-weight: 600; color: var(--ob-text-2); }
        .b2b-divider { height: 1px; margin: 1.1rem 0; border: 0; background: var(--ob-border); opacity: 1; }

        /* Pilihan jenis customer */
        .b2b-types { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
        @media (max-width: 575.98px) { .b2b-types { grid-template-columns: minmax(0, 1fr); } }
        .b2b-type { position: relative; margin: 0; cursor: pointer; }
        .b2b-type input { position: absolute; opacity: 0; pointer-events: none; }
        .b2b-type__box {
            display: flex; align-items: center; gap: .75rem; height: 100%; padding: .8rem .9rem;
            border: 1px solid var(--ob-border-strong); border-radius: 12px; background: var(--ob-surface);
            transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
        }
        .b2b-type:hover .b2b-type__box { border-color: var(--ob-primary); }
        .b2b-type__icon { display: inline-grid; place-items: center; flex-shrink: 0; width: 38px; height: 38px; border-radius: 10px; background: var(--ob-surface-2); color: var(--ob-muted); transition: inherit; }
        .b2b-type__text { flex: 1; min-width: 0; }
        .b2b-type__text strong { display: block; font-size: .9rem; font-weight: 700; color: var(--ob-text); }
        .b2b-type__text small { display: block; font-size: .75rem; color: var(--ob-muted); }
        .b2b-type__check { color: var(--ob-primary); opacity: 0; transition: opacity .15s ease; }
        .b2b-type input:checked + .b2b-type__box { border-color: var(--ob-primary); background: var(--ob-primary-soft); box-shadow: 0 0 0 1px var(--ob-primary); }
        .b2b-type input:checked + .b2b-type__box .b2b-type__icon { background: var(--ob-surface); color: var(--ob-primary); }
        .b2b-type input:checked + .b2b-type__box .b2b-type__check { opacity: 1; }
        .b2b-type input:focus-visible + .b2b-type__box { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
        .b2b-type input.is-invalid + .b2b-type__box { border-color: var(--ob-danger); }

        /* Saklar */
        .b2b-switch { display: flex; align-items: flex-start; gap: .75rem; margin: 0; cursor: pointer; }
        .b2b-switch input { position: absolute; opacity: 0; pointer-events: none; }
        .b2b-switch__track { position: relative; flex-shrink: 0; width: 40px; height: 22px; margin-top: .1rem; border-radius: 999px; background: var(--ob-border-strong); transition: background-color .15s ease; }
        .b2b-switch__track::after { content: ""; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .25); transition: transform .15s ease; }
        .b2b-switch input:checked + .b2b-switch__track { background: var(--ob-primary); }
        .b2b-switch input:checked + .b2b-switch__track::after { transform: translateX(18px); }
        .b2b-switch input:focus-visible + .b2b-switch__track { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
        .b2b-switch__text strong { display: block; font-size: .875rem; font-weight: 600; color: var(--ob-text); }
        .b2b-switch__text small { display: block; margin-top: .1rem; font-size: .78rem; line-height: 1.45; color: var(--ob-muted); }

        /* Chip pilihan (checkbox berbentuk pil) */
        .b2b-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
        .b2b-chip { position: relative; margin: 0; cursor: pointer; }
        .b2b-chip input { position: absolute; opacity: 0; pointer-events: none; }
        .b2b-chip span {
            display: inline-flex; align-items: center; gap: .4rem; min-height: 34px; padding: .3rem .8rem;
            border: 1px solid var(--ob-border-strong); border-radius: 999px; background: var(--ob-surface);
            color: var(--ob-text-2); font-size: .8rem; font-weight: 600; white-space: nowrap; user-select: none;
            transition: border-color .15s ease, background-color .15s ease, color .15s ease;
        }
        .b2b-chip span::before {
            content: ""; width: 14px; height: 14px; flex-shrink: 0; border: 1.5px solid var(--ob-border-strong); border-radius: 4px; background: var(--ob-surface) center / 10px no-repeat;
            transition: inherit;
        }
        .b2b-chip:hover span { border-color: var(--ob-primary); }
        .b2b-chip input:checked + span { border-color: var(--ob-primary); background: var(--ob-primary-soft); color: var(--ob-primary); }
        .b2b-chip input:checked + span::before {
            border-color: var(--ob-primary); background-color: var(--ob-primary);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m2.5 6.2 2.3 2.3 4.7-5'/%3E%3C/svg%3E");
        }
        .b2b-chip input:focus-visible + span { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
        .b2b-field.is-invalid .b2b-chip span { border-color: var(--ob-danger); }

        /* Catatan tujuan reminder */
        .b2b-note { display: flex; gap: .6rem; padding: .75rem .85rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: var(--ob-surface-2); font-size: .8rem; line-height: 1.5; color: var(--ob-text-2); }
        .b2b-note > i { margin-top: .2rem; color: var(--ob-muted); }
        .b2b-note strong { display: block; color: var(--ob-text); }
        .b2b-note strong.is-empty { color: #b45309; }
        .b2b-field.is-invalid > .b2b-note { border-color: var(--ob-danger); }

        /* Bilah simpan */
        .b2b-form-footer { align-items: center; border-radius: 12px; }
        .b2b-form-footer__hint { margin-right: auto; font-size: .8rem; color: var(--ob-muted); }
        .b2b-form-footer .btn { min-height: 42px; }
        @media (max-width: 575.98px) { .b2b-form-footer__hint { display: none; } .b2b-form-footer .btn { flex: 1; justify-content: center; } }
    </style>
@endpush
