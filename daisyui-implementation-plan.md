# Rencana Penerapan DaisyUI — POS UD DJAYA

> Status: **PERENCANAAN SAJA** — dokumen ini belum dieksekusi dan tidak mengubah UI yang berjalan.
> Tujuan: menjadi acuan teknis saat migrasi UI dari KAIAdmin (Bootstrap 5) ke Tailwind + DaisyUI.

---

## 1. Ringkasan

Dokumen ini merinci cara menerapkan **DaisyUI** sebagai lapisan komponen di atas Tailwind CSS,
sekaligus menerjemahkan **UD Djaya Brand Guidelines** (warna, tipografi, mood retro/fun) menjadi
konfigurasi tema yang konkret.

Pendekatan yang dipilih: **bertahap** (Fase A dulu, Fase B menyusul), dengan prinsip:

- **Fungsi tidak berubah.** Controller, route, model, DataTables, Select2, dan helper JS global tetap utuh.
- **Blade tetap dipakai.** Yang berubah hanya `class` di markup, bukan mesin templating.
- **jQuery tetap dipakai** untuk DataTables & Select2 selama Fase A.

---

## 2. Prasyarat & Kompatibilitas Versi

| Komponen | Versi saat ini | Catatan |
|---|---|---|
| Tailwind CSS | **3.4.14** | Terpasang di `package.json` |
| PostCSS | 8.4.31 | Sudah ada |
| Vite | 5.x | Sudah ada |
| Alpine.js | 3.4.2 | Sudah ada (dari Breeze) |
| **DaisyUI target** | **v4** (`daisyui@^4`) | **PENTING:** DaisyUI v5 hanya untuk Tailwind v4. Karena project masih Tailwind 3.4, gunakan DaisyUI v4. |

**Keputusan penting:** jangan upgrade Tailwind ke v4 dalam fase ini. Upgrade Tailwind v3→v4 adalah
perubahan besar tersendiri (breaking changes pada config & utility). Penerapan DaisyUI harus
memakai **daisyui@4** agar tetap kompatibel.

---

## 3. Rencana Instalasi (belum dijalankan)

Langkah saat eksekusi nanti:

1. Tambah dependency:
   ```bash
   npm install -D daisyui@^4
   ```
2. Daftarkan plugin di `tailwind.config.js`:
   ```js
   plugins: [forms, require('daisyui')],
   ```
3. Definisikan custom theme brand (bagian 4).
4. Definisikan font (bagian 5).
5. Jalankan `npm run dev` untuk memverifikasi build tanpa error.

> Catatan: `@tailwindcss/forms` saat ini terpasang. DaisyUI juga menata form, jadi perlu dicek
> potensi tumpang tindih style pada input/select/checkbox. Solusi: gunakan class DaisyUI
> (`input`, `select`, `checkbox`) sebagai penentu utama dan turunkan ketergantungan pada style forms.

---

## 4. Custom Theme — Token Warna Brand

Dari Brand Guidelines hal. 17–20, palet resmi dipetakan ke token semantik DaisyUI:

| Token DaisyUI | Hex | Nama Brand | Content (teks di atasnya) |
|---|---|---|---|
| `primary` | `#CA1618` | UD DJAYA RED | `#FFFFFF` |
| `secondary` | `#F7DE15` | Kuning | `#000000` |
| `accent` | `#E2A420` | Gold | `#000000` |
| `neutral` | `#000000` | Hitam | `#F2F0DA` |
| `base-100` | `#FFFFFF` | Putih | `#000000` |
| `base-200` | `#F2F0DA` | Cream | `#000000` |
| `base-300` | `#F2EAB2` | Cream muda | `#000000` |
| `base-content` | `#000000` | Teks utama | — |
| `info` | `#2E6DA5` | Biru | `#FFFFFF` |
| `success` | `#2A8832` | Hijau | `#FFFFFF` |
| `warning` | `#E2A420` | Gold | `#000000` |
| `error` | `#E9170B` | Merah terang | `#FFFFFF` |

Rancangan blok konfigurasi di `tailwind.config.js`:

```js
daisyui: {
  themes: [
    {
      uddjaya: {
        "primary": "#CA1618",
        "primary-content": "#FFFFFF",
        "secondary": "#F7DE15",
        "secondary-content": "#000000",
        "accent": "#E2A420",
        "accent-content": "#000000",
        "neutral": "#000000",
        "neutral-content": "#F2F0DA",
        "base-100": "#FFFFFF",
        "base-200": "#F2F0DA",
        "base-300": "#F2EAB2",
        "base-content": "#000000",
        "info": "#2E6DA5",
        "info-content": "#FFFFFF",
        "success": "#2A8832",
        "success-content": "#FFFFFF",
        "warning": "#E2A420",
        "warning-content": "#000000",
        "error": "#E9170B",
        "error-content": "#FFFFFF",
        // Variabel bentuk (mood retro/fun) — lihat bagian 6
        "--rounded-box": "1rem",
        "--rounded-btn": "0.75rem",
        "--rounded-badge": "1.9rem",
        "--animation-btn": "0.25s",
        "--border-btn": "1px",
      },
    },
  ],
  logs: false,
}
```

**Catatan kontras:** Guidelines menekankan "kontras kuat antar warna". Semua kombinasi
`*-content` di atas sudah dipilih agar memenuhi kontras teks yang layak (putih di atas merah/hitam,
hitam di atas kuning/gold/cream).

---

## 5. Tipografi

Guidelines hal. 23–28 menetapkan tiga font:

| Peran | Font | Status | Tindakan |
|---|---|---|---|
| Headline/Display | **BN Boops** | Belum dimiliki | Cari file + lisensi web, self-host |
| Body/UI | **Jakarta Sans** | Belum dimiliki | Tersedia gratis sebagai **"Plus Jakarta Sans"** di Google Fonts |
| Aksen | **Lastik** | Belum dimiliki | Cari file + lisensi, pakai selektif |

Rancangan konfigurasi font di `tailwind.config.js`:

```js
fontFamily: {
  sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
  display: ['"BN Boops"', '"Plus Jakarta Sans"', 'sans-serif'],
  accent: ['"Lastik"', '"Plus Jakarta Sans"', 'sans-serif'],
},
```

Aturan pemakaian:

- **Default UI (body, tabel, form)** → `font-sans` (Plus Jakarta Sans).
- **Judul halaman / card header / angka besar** → `font-display` (BN Boops).
- **Label promo / aksen kecil** → `font-accent` (Lastik), dipakai hemat.

**Blocker:** BN Boops & Lastik belum tersedia. Sampai file didapat, `font-display` dan `font-accent`
akan fallback ke Plus Jakarta Sans. Jadi UI tetap bisa dibangun lebih dulu.

---

## 6. Nuansa Retro / Fun

Mood brand: **Hangat, Retro, Fun** (Guidelines hal. 30–35, 43). DaisyUI default terasa flat/modern,
jadi perlu sentuhan tambahan:

| Aspek | Arahan | Implementasi |
|---|---|---|
| Sudut (radius) | Lebih membulat, tidak kaku | Naikkan `--rounded-box`/`--rounded-btn` (lihat bagian 4) |
| Border | Garis tegas bergaya retro | `border-2 border-base-content` pada card/button tertentu |
| Shadow | Bayangan solid (offset), bukan blur | `shadow-[4px_4px_0_0_#000]` (hard shadow) |
| Warna | Dominan cream + aksen merah/kuning | Pakai `base-200`/`base-300` sebagai latar, `primary` sebagai aksen |
| Pattern | Ornamen bintang/garis gelombang (selektif) | SVG/CSS background, hanya di header/login — jangan di tabel |
| Ikon | Bulat, ramah | Konsisten dengan Font Awesome yang sudah ada |

**Penting:** mood retro/fun diterapkan **terukur**. Untuk area data (tabel, form, laporan),
prioritaskan keterbacaan; nuansa retro cukup lewat warna, radius, dan aksen — bukan pattern ramai.

---

## 7. Peta Konversi Class: Bootstrap/KAIAdmin → DaisyUI

Referensi saat mengubah markup per halaman. Banyak nama class sebenarnya sama.

| Bootstrap / KAIAdmin | DaisyUI | Catatan |
|---|---|---|
| `btn btn-primary` | `btn btn-primary` | Nama sama, style beda |
| `btn-outline-primary` | `btn btn-outline btn-primary` | Perlu `btn-outline` |
| `btn-sm` / `btn-lg` | `btn-sm` / `btn-lg` | Sama |
| `card` | `card` | Sama |
| `card-body` | `card-body` | Sama |
| `card-header` | `card-title` + wrapper | Sesuaikan struktur |
| `form-control` (input) | `input input-bordered` | Perlu dua class |
| `form-select` | `select select-bordered` | — |
| `form-label` | `label` + `label-text` | Struktur wrapper beda |
| `form-group` | `form-control` (wrapper DaisyUI) | Hati-hati: nama bentrok dengan input Bootstrap |
| `form-check-input` | `checkbox` / `radio` / `toggle` | — |
| `alert alert-success` | `alert alert-success` | Sama |
| `badge bg-primary` | `badge badge-primary` | — |
| `table` | `table` | Sama |
| `modal fade` + `data-bs-*` | `modal` (berbasis `<dialog>`) | Butuh strategi interaksi (bagian 8) |
| `dropdown-menu` | `dropdown-content` + `menu` | — |
| `nav nav-pills` | `tabs tabs-boxed` | — |
| `breadcrumb` | `breadcrumbs` | — |
| `pagination` | `join` + `btn` | — |
| `progress` | `progress` | Sama |
| `tooltip` | `tooltip` | — |
| `input-group` | `join` | — |
| `row` / `col-md-6` | `grid grid-cols-*` / `flex` | **Bukan DaisyUI** — murni Tailwind, remap manual |
| `d-flex`, `justify-content-*` | `flex`, `justify-*` | Murni Tailwind |
| `text-muted` | `text-base-content/60` | — |
| `fw-bold` | `font-bold` | — |

**Kesimpulan tabel ini:** ~60% komponen punya nama yang sama atau mirip; yang paling butuh
perhatian adalah **grid (`row`/`col-*`)** dan **modal**, karena keduanya bukan sekadar ganti nama.

---

## 8. Interaksi dengan Plugin Lama (Strategi Fase A)

Plugin yang berjalan sekarang dan **tetap dipakai** di Fase A:

| Plugin | Peran | Strategi |
|---|---|---|
| **jQuery** | AJAX, event, helper global | **Tetap.** Tidak berubah. |
| **Yajra DataTables** | 44 controller, 60 file | **Tetap.** Perlu CSS override agar warna mengikuti brand. |
| **Select2** | 44 file | **Tetap.** Perlu CSS override (border, radius, warna fokus). |
| **SweetAlert2** | 39 file | **Tetap.** Sesuaikan `confirmButtonColor` ke `#CA1618`. |
| **iziToast** | Notifikasi | **Tetap.** Sesuaikan warna tema ke palet brand. |
| **Bootstrap JS** | `.modal()`, `.collapse()`, `data-bs-*` | **Sementara tetap dimuat** agar 54 pemanggilan modal tidak pecah. Diganti bertahap di Fase B. |
| **Fancybox / ApexCharts / Chart.js** | Media & grafik | Tetap. Warna chart diset manual ke palet brand. |

**Prinsip:** Fase A mengganti **CSS/look**, bukan **JS behavior**. Bootstrap JS masih dimuat
selama transisi supaya fungsi lama tidak rusak.

---

## 9. Titik Kritis yang Harus Dipertahankan

Agar 67 halaman tidak pecah, elemen berikut **wajib dipertahankan** di layout baru:

- `@yield('content')`, `@stack('css')`, `@stack('js')` — kontrak layout.
- `#modal_action` — target inject HTML modal via AJAX.
- `#preloader` — indikator loading.
- Seluruh helper JS global di `layouts/app.blade.php`:
  `handleAjax`, `handleAction`, `handleDelete`, `handleFormSubmit`, `submitLoader`,
  `showToast`, `getAmount`, `formatRupiah`, `showLoader`, `generateRandomID`.
- Pola controller `create()`/`edit()` yang mengembalikan **fragment modal HTML** — tidak diubah.

---

## 10. Strategi Bertahap

### Fase A — Tailwind + DaisyUI, jQuery dipertahankan
1. Instalasi & konfigurasi DaisyUI + tema brand + font.
2. Ganti cangkang layout: `app`, `sidebar`, `navigation`, `footer`.
3. Restyle komponen inti: card, button, form, alert, badge, table.
4. Override CSS DataTables & Select2 agar ikut warna brand.
5. Migrasi halaman per modul, dimulai dari yang paling sederhana (master data).

### Fase B — Pelepasan ketergantungan Bootstrap (menyusul)
1. Bootstrap JS → DaisyUI modal (`<dialog>`) / Alpine.
2. DataTables → Tabel alternatif non-jQuery (mis. Tabulator) — *opsional*.
3. Select2 → TomSelect/Choices — *opsional*.
4. `$.ajax` → axios — *opsional*.

> Halaman `layouts/kasir/*` **dikecualikan** karena sudah digantikan kasir Android native.

---

## 11. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Salah versi DaisyUI (v5 di Tailwind 3) | Build gagal | Pakai `daisyui@^4` |
| Tumpang tindih `@tailwindcss/forms` vs DaisyUI | Form tampil aneh | Standarkan pada class DaisyUI; uji form per tipe |
| DataTables/Select2 tidak mengikuti tema | Tampilan tidak konsisten | Override CSS khusus kedua plugin |
| Bootstrap JS & DaisyUI modal bentrok | Modal tidak jalan | Fase A: pertahankan Bootstrap JS; jangan campur modal DaisyUI |
| Font brand belum tersedia | Headline tidak sesuai guidelines | Fallback ke Plus Jakarta Sans sementara |
| Grid `row`/`col-*` bukan DaisyUI | Layout berantakan | Remap manual ke Tailwind grid/flex per halaman |
| 67 halaman luas | Migrasi lama | Bertahap per modul, uji tiap modul |

---

## 12. Kriteria Uji (Checklist)

Per modul yang dimigrasi, verifikasi:

- [ ] Build `npm run dev`/`npm run build` tanpa error.
- [ ] Layout (sidebar, navbar, footer) tampil benar di desktop & mobile.
- [ ] Warna mengikuti token brand (merah `#CA1618` sebagai primary).
- [ ] Font body = Plus Jakarta Sans; headline = BN Boops (bila sudah tersedia).
- [ ] Tabel DataTables: search, sort, pagination, tombol aksi berfungsi.
- [ ] Select2: muncul & bisa memilih.
- [ ] Modal (`#modal_action`) terbuka & submit berhasil.
- [ ] SweetAlert2 & iziToast tampil dengan warna brand.
- [ ] Form validation error tampil pada field yang tepat.
- [ ] Tidak ada regresi fungsi (CRUD berjalan normal).

---

## 13. Referensi

- Brand Guidelines: `UD-Djaya-Brand-Guidelines.pdf`
  (Warna hal. 17–20; Tipografi hal. 23–28; Mood hal. 30–35, 43)
- DaisyUI v4: https://v4.daisyui.com/
- Tailwind CSS 3.4: https://v3.tailwindcss.com/
- File layout terkait: `resources/views/layouts/app.blade.php`,
  `resources/views/layouts/sidebar.blade.php`,
  `resources/views/layouts/navigation.blade.php`,
  `resources/views/layouts/footer.blade.php`
- Konfigurasi: `tailwind.config.js`, `resources/css/app.css`, `vite.config.js`

---

## 14. Yang Belum Diputuskan

1. **File font BN Boops & Lastik** — belum dimiliki; perlu dicari + cek lisensi web.
2. **Cakupan retro/fun** — seberapa jauh pattern/ilustrasi dipakai di panel admin.
3. **Jadwal eksekusi** — kapan Fase A mulai (dokumen ini belum dieksekusi).
4. **Fase B** — apakah DataTables/Select2 akan benar-benar diganti.
