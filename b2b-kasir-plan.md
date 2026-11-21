# Rencana Fitur Kasir B2B

- **Status:** Fase 1 (customer, kontrak, harga), Fase 2 (kasir, pesanan, invoice), bayar langsung + pelunasan tempo manual, dan Fase 4 (status overdue, reminder email + WhatsApp, menu Reminder) sudah dibangun (per 21 Nov 2026). Pelunasan tempo otomatis ditunda sampai metodenya diputuskan (kode unik vs VA BCA). Sedang dikerjakan: laporan B2B (fase 5), langkah 1 dari 8 selesai.
- **Tanggal rekap:** 18 November 2026
- **Presentasi:** `b2b-kasir-presentasi.html` (buka langsung di browser). Isinya masih versi 14 Nov (aturan diskon, banyak kontak/alamat).

Kasir B2B adalah fitur penjualan ke pelanggan bisnis (perusahaan) di web backoffice. Bedanya dengan kasir Android (POS):

- pembeli wajib dipilih dari data customer B2B,
- harga mengikuti kontrak per customer,
- pembayaran tidak langsung, tapi jatuh tempo dengan reminder otomatis,
- pembayaran dicek dari mutasi rekening BCA perusahaan.

---

## 1. Keputusan yang sudah dikunci

| Topik | Keputusan |
|---|---|
| Data | Tabel baru berawalan `b2b_`. Tidak digabung dengan `customers` atau `transactions` |
| Customer | Global, tidak terikat outlet. Satu customer punya satu kontak (PIC) dan satu alamat (untuk penagihan sekaligus pengiriman) |
| Barang | Produk yang sudah ada. Setiap pesanan wajib memilih outlet (produk terikat `outlet_id`) |
| Stok | Stok varian berkurang saat pesanan dikonfirmasi. **Boleh minus**, cukup peringatan. Dikembalikan kalau pesanan batal |
| Platform | Web backoffice yang dibuka dari tablet Android lewat browser. Tanpa petty cash |
| Kontrak | Harga tetap per varian, bertingkat menurut qty. Maksimal 1 kontrak berlaku per customer di satu tanggal. Langsung aktif saat dibuat (tanpa draft dan approval), tetap bisa diedit saat berjalan |
| Tanpa kontrak | Customer tetap boleh belanja dengan harga normal |
| Pajak | Tanpa PPN. NPWP opsional |
| Pembayaran | Jatuh tempo, belum ada cicilan. Struktur tabel sudah siap untuk cicilan |
| Rekening | **Ditunda.** Opsi: rekening statis BCA + kode unik, atau VA BCA langsung 1 nomor per customer (± Rp 2.271/transaksi). Menunggu jawaban BCA |
| Kode unik | **Ditunda.** Kolom sudah disiapkan (`unique_code`, `unique_code_amount`), sekarang selalu 0 |
| Cek pembayaran | Finance upload file mutasi BCA, sistem mencocokkan otomatis, sisanya dicek manual |
| Reminder | **Diatur per customer, tanpa pengaturan global.** Default mati; kalau tidak diatur, tidak ada yang dikirim. Channel + jadwal di customer, penerima = telepon/email customer |
| Limit kredit | **Dihapus (19 Nov 2026).** Tidak ada aturan limit kredit; pesanan tempo tidak dicek piutangnya |
| Laporan | Laporan B2B terpisah dari laporan dan dashboard POS |

### Kenapa tidak digabung dengan tabel `customers`

- Sync kasir Android (`app/Http/Controllers/Api/CustomerController.php:123`) menarik **semua** baris `customers`, termasuk yang terhapus. Customer B2B akan ikut muncul di Android.
- Cron email ulang tahun (`app/Console/Commands/SendBirthdayEmail.php:34`) membaca `customers`, jadi customer B2B bisa ikut dikirimi.
- Kolom `gender` wajib diisi, dan sisanya kolom B2C (level membership, poin/exp, komunitas, referral, tanggal lahir). Kolom-kolom itu tidak relevan untuk perusahaan.
- Kalau dibedakan dengan kolom `type`, semua query lama harus ditambah filter. Satu query yang lupa difilter membuat data bocor.

### Kenapa tidak digabung dengan tabel `transactions`

`transactions` terikat ke alur kas POS: `patty_cash_id` wajib diisi, ada `shift_session_id`, dan transaksi langsung lunas. Alur B2B adalah pesanan → invoice → piutang. Modul `piutangs` yang sudah ada belum selesai (`store()` masih kosong) dan konsepnya berbeda, jadi tidak dipakai.

### Temuan dari kode yang memengaruhi desain

- Stok hanya ada di level varian (`variant_products.stok`). POS mengurangi stok tanpa mengecek sisanya (`Api/TransactionController.php:513`), dan B2B mengikuti perilaku ini (boleh minus).
- POS menyimpan item transaksi satu baris per unit. B2B tidak meniru ini dan memakai kolom `qty`.
- Harga ada di `variant_products.harga`. Produk terikat `outlet_id`.
- Menu backoffice diambil dari database lewat helper `menus()`. Nama permission mengikuti pola `read|create|update|delete {url}` dan didaftarkan lewat seeder (contoh: `OrderTableMenuSeeder`).
- Tabel `configs` (key-value bertipe) sudah ada untuk pengaturan sederhana.
- Akses outlet user sudah ada di `users.outlet_id` (JSON).
- Kasir web yang lama (`resources/views/layouts/kasir/index.blade.php`) terikat petty cash. Kasir B2B dibuat sebagai halaman terpisah yang ramah layar sentuh.
- Produk belum punya kolom berat. Ini perlu kalau nanti memakai ekspedisi.

---

## 2. Alur bisnis

```
Customer B2B + Kontrak (opsional)
        ▼
Kasir B2B: pilih outlet → pilih customer → pilih produk → harga otomatis mengikuti kontrak
        ▼  konfirmasi (stok dikurangi, boleh minus)
Invoice + jadwal tagihan (jatuh tempo dipilih kasir; kode unik menunggu metode pembayaran)
        ▼
Reminder otomatis (H-7, H-3, H-1, H-0, lalu setiap hari setelah jatuh tempo)
        ▼
Customer transfer ke rekening BCA perusahaan (dari bank mana saja)
        ▼
Finance upload file mutasi BCA → pencocokan otomatis → tagihan lunas
                                  └─ tidak cocok → dicek manual oleh finance
```

### Perhitungan harga kontrak

- Dihitung di backend (`PricingService`). Layar kasir hanya menampilkan preview, lalu server menghitung ulang saat pesanan disimpan.
- Kontrak berisi **harga tetap per varian**. Varian sudah menentukan outlet, jadi satu kontrak bisa berisi produk dari beberapa outlet.
- Satu varian boleh punya beberapa harga dengan `min_qty` berbeda (harga bertingkat). Dipakai `min_qty` terbesar yang masih ≤ qty. Kalau qty di bawah semua tingkat, dipakai harga normal.
- Harga kontrak boleh lebih tinggi dari harga normal.
- Produk yang tidak ada di kontrak memakai harga normal, begitu juga customer tanpa kontrak yang sedang berjalan.
- Harga kontrak tidak digabung dengan promo atau diskon POS.
- Harga **disalin** ke item pesanan (`final_price` dan `b2b_contract_item_id`), jadi perubahan kontrak tidak mengubah pesanan lama.

### Aturan kontrak

- Tanpa draft: kontrak langsung aktif saat dibuat. Kolom `status` hanya `active` atau `terminated`. Label yang tampil dihitung dari tanggal: **Terjadwal** (belum mulai), **Berjalan**, **Berakhir** (lewat `end_date`), dan **Dihentikan**. Jadi tidak perlu command harian.
- Periode kontrak yang belum dihentikan milik customer yang sama tidak boleh tumpang tindih. Ini dicek saat membuat dan mengedit, jadi pada satu tanggal paling banyak satu kontrak berlaku.
- Kontrak tetap bisa diedit (tanggal, catatan, harga, file) selama belum dihentikan, termasuk saat sedang berjalan. Customer tidak bisa diganti. Item yang dibuang di-soft-delete supaya pesanan lama tetap bisa merujuknya.
- Menghentikan kontrak wajib disertai alasan. Kontrak yang sudah berakhir tidak perlu dihentikan.
- Hapus = soft delete. Kontrak yang sedang berjalan harus dihentikan dulu.
- File kontrak (PDF, JPG, PNG, DOC, DOCX, maks 5 MB) bisa diganti atau dilepas saat edit.
- Perpanjangan dibuat sebagai kontrak baru. Ide yang belum dibangun: tombol untuk menyalin harga dari kontrak lama.

### Kode unik dan pencocokan pembayaran

- Kode unik dibuat **per jadwal tagihan**, sehingga nanti setiap cicilan otomatis punya kode sendiri.
- Kode dipilih acak dari 1–999 sehingga `amount_to_pay` tidak sama dengan tagihan lain yang belum lunas. Ini dijaga dengan lock di aplikasi. Opsional: generated column `open_amount_key` yang unik dan hanya terisi selama tagihan belum lunas.
- Kode ditampilkan jelas di invoice, PDF, dan reminder, misalnya "Transfer tepat sampai 3 digit terakhir: Rp 6.836.137".

Urutan pencocokan setelah file mutasi di-upload:

1. **Nominal persis** sama dengan `amount_to_pay` tagihan yang belum lunas, dan kandidatnya hanya satu → lunas otomatis.
2. **Nomor invoice** tertulis di berita transfer dan nominalnya cocok → lunas otomatis.
3. **Nama pengirim sudah dikenal** (pernah dicocokkan sebelumnya) → menjadi saran untuk finance.
4. **Selain itu** → masuk daftar cek manual. Finance memilih customer, lalu dana dibagi ke tagihan terlama atau tagihan yang dipilih.
5. Kelebihan bayar menjadi saldo customer.

Aturan tambahan:

- Hanya mutasi dana masuk (CR) yang diproses.
- Setiap baris diberi sidik unik (`fingerprint`), sehingga upload ulang file yang sama tidak mencatat dana dua kali.
- Transfer bank hanya diakui lewat mutasi. Input manual hanya untuk pembayaran tunai atau metode lain, supaya tidak tercatat ganda.
- Parser file mutasi dibuat per bank (`statement_parser`). Nanti mutasi otomatis (API BCA atau layanan pihak ketiga) cukup ditambahkan sebagai sumber data baru.

### Reminder (fase 4, disepakati 20 Nov 2026)

- Jadwal diatur **per customer**: `reminder_enabled`, `reminder_channels`, `reminder_days` (H-7, H-3, H-1, Hari H), `reminder_overdue_daily`. Tidak ada pengaturan jadwal global. Default mati.
- Setelah jatuh tempo (kalau `reminder_overdue_daily` aktif): harian sampai H+7, lalu seminggu sekali (H+14, H+21, …) sampai lunas.
- Kiriman otomatis mulai **09:00 WIB**; percobaan ulang kiriman gagal paling lambat **17:00 WIB**. Kirim manual dari detail invoice bebas jam.
- **Satu email gabungan per customer per hari** berisi semua invoice yang kena jadwal hari itu, lampiran PDF per invoice (dompdf). From sama dengan email ulang tahun, reply-to ke `b2b.invoice.email`.
- Isi: nomor invoice, tanggal pesanan, jatuh tempo, total, sudah dibayar, sisa tagihan, rekening tujuan (config invoice), kontak penagihan. Kode unik belum disertakan.
- Sebelum dikirim, sisa tagihan dicek ulang; yang sudah lunas ditandai `skipped`.
- WhatsApp lewat **Baileys** (Node.js) di VPS terpisah dengan nomor khusus penagihan. Kode layanan di folder baru satu tingkat di atas repo ini. Laravel ↔ layanan lewat HTTPS + API key, firewall hanya menerima IP server Laravel, file session hanya di VPS. Channel dibuat sebagai driver.
- **Saklar darurat**: tombol on/off untuk menghentikan sementara semua kiriman Email atau WhatsApp.
- Ditunda: status baca WhatsApp (webhook), notifikasi email ke admin saat nomor WhatsApp terputus.

### Laporan B2B (fase 5, disepakati 21 Nov 2026)

- **Satu halaman** `b2b/reports`. Detail lewat tiga tingkat: (1) ringkasan di halaman, (2) **drilldown** di chart (klik batang/irisan → rincian berikutnya, breadcrumb + tombol kembali), (3) **modal** daftar transaksi (link ke halaman pesanan/invoice yang ada) atau **halaman baru** di dalam menu laporan untuk kartu piutang customer (`b2b/reports/customers/{id}`).
- Filter bersama untuk semua bagian: periode (preset: hari ini, 7 hari, bulan ini, bulan lalu, 3 bulan, tahun ini, pilih sendiri), outlet (sesuai akses user), customer. Disimpan di URL. Halaman = kerangka; setiap bagian mengambil data sendiri dari `b2b/reports/data/{bagian}`.
- Susunan: KPI → A. Tren penjualan → B. Penjualan per Customer/Outlet/Produk/Kategori (tab) → C. Umur piutang + D. Pembayaran & kas → E. Kontrak hampir berakhir (≤ 30 hari).
- Definisi angka:
  - **Penjualan**: `grand_total` pesanan Dikonfirmasi + Selesai menurut `order_date`. Batal dan draft tidak dihitung.
  - **Kas masuk bersih**: uang yang benar-benar diterima menurut `paid_at` (pembayaran `posted`, atau `reversed` karena pesanan batal dan punya refund) dikurangi refund menurut `refunded_at`. Pelunasan yang dibatalkan karena salah catat tidak dihitung. (Usulan awal "posted − refund" mengurangi refund dua kali, karena batal + refund juga membuat pembayaran `reversed`.)
  - **Piutang dan umur piutang**: sisa invoice belum lunas (`total_to_pay − paid_amount`) **per hari ini**, tidak ikut periode, tetap ikut outlet/customer. Kelompok umur dari `due_date`: belum jatuh tempo, 1–30, 31–60, 61–90, > 90 hari.
  - **Outlet** = outlet pesanan. Rekap kas per outlet penerima (`b2b_payments.outlet_id`, kosong = rekening pusat) ada di bagian D.
- Tahapan: 1 kerangka + KPI ✅ → 2 tren + pola drilldown/modal → 3 penjualan per dimensi → 4 umur piutang → 5 pembayaran & kas → 6 kontrak hampir berakhir → 7 kartu piutang customer → 8 export Excel (opsional).

---

## 3. Tahapan pengerjaan

| Fase | Isi | Tabel |
|---|---|---|
| **1** ✅ | Data customer (satu kontak dan alamat), kontrak dengan harga per varian, perhitungan harga, menu dan permission | customers, contracts, contract_items |
| **2** ✅ | Kasir B2B: pesanan → invoice + jadwal tagihan (kode unik belum aktif), cetak invoice, pengurangan stok | orders, order_items, invoices, invoice_schedules, sequences, activity_logs |
| **3** | Pembayaran: rekening, upload mutasi BCA, pencocokan otomatis, daftar cek manual, input tunai, saldo customer | bank_accounts, mutation_imports, incoming_funds, payments, payment_allocations, customer_sender_aliases |
| **4** | Status overdue (`mark-overdue`), reminder email + WhatsApp (Baileys), menu Reminder | reminder_logs, reminder_log_invoices |
| **5** 🚧 | Laporan B2B satu halaman: penjualan per customer/outlet/produk, umur piutang, mutasi pembayaran, kontrak yang hampir berakhir (lihat "Laporan B2B" di atas) | (membaca tabel di atas) |
| Nanti | Mutasi otomatis, WhatsApp, pengiriman dan ekspedisi, cicilan | — |

Fase 3 membutuhkan contoh file mutasi BCA untuk membuat parser.

---

## 4. Struktur tabel

Keterangan: `?` = boleh kosong, FK = relasi. Nominal rupiah disimpan sebagai `bigint`, sama seperti tabel yang sudah ada. Total: **18 tabel baru**, 9 di antaranya sudah dibangun (fase 1–2).

### A. Customer (fase 1)

**`b2b_customers`**: satu kontak (PIC) dan satu alamat per customer, langsung di tabel ini.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| code | varchar(30) unik | contoh: `CUS-B2B-0001` |
| type | enum: individual, pt, cv | Perorangan/PT/CV. Hanya klasifikasi, tidak ditempel ke nama |
| name | varchar | nama perusahaan/usaha |
| npwp | varchar(30)? | |
| phone, email | varchar? | juga tujuan reminder |
| contact_name | varchar? | nama PIC |
| address | text? | alamat penagihan sekaligus pengiriman |
| city, province | varchar(100)? | |
| postal_code | varchar(10)? | |
| reminder_enabled | bool, default false | tidak diatur = tidak dikirim |
| reminder_channels | json? | `["email"]`, `["whatsapp"]`, atau keduanya |
| reminder_days | json? | hari relatif ke jatuh tempo: -7, -3, -1, 0 |
| reminder_overdue_daily | bool, default false | kirim tiap hari setelah lewat jatuh tempo |
| notes | text? | |
| is_active | bool | |
| created_by, modified_by | bigint? | audit trail, tanpa foreign key/index |
| timestamps, deleted_at | | soft delete |

### B. Kontrak (fase 1)

**`b2b_contracts`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_customer_id | FK | tidak bisa diganti setelah dibuat |
| contract_number | varchar(40) unik | contoh: `KTR/B2B/2026/0001` |
| start_date, end_date | date | periode tidak boleh tumpang tindih |
| status | enum: active, terminated | label Terjadwal/Berjalan/Berakhir dihitung dari tanggal |
| attachment_path, attachment_name | varchar? | file kontrak dan nama aslinya |
| notes | text? | |
| terminated_by, terminated_at, termination_reason | ? | |
| created_by, modified_by | bigint? | audit trail, tanpa foreign key/index |
| timestamps, deleted_at | | soft delete |

Index `(b2b_customer_id, status, start_date, end_date)` untuk cek overlap dan mencari kontrak yang berlaku.

**`b2b_contract_items`**: harga kontrak per varian. Beberapa baris untuk varian yang sama = harga bertingkat.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_contract_id | FK | |
| variant_id | FK variant_products | varian sudah menentukan outlet |
| min_qty | int unsigned, default 1 | harga berlaku mulai qty ini |
| price | bigint | harga per unit, boleh di atas harga normal |
| timestamps, deleted_at | | soft delete supaya pesanan lama tetap bisa merujuk |

Kombinasi `(kontrak, varian, min_qty)` divalidasi unik di aplikasi, bukan unique index, karena akan bentrok dengan baris yang sudah di-soft-delete.

### C. Pesanan (fase 2)

**`b2b_orders`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| order_number | varchar unik | |
| b2b_customer_id | FK | |
| b2b_contract_id | FK? | kosong = harga normal |
| outlet_id | FK | |
| user_id | FK users | kasir |
| order_date | datetime | |
| due_date | date | jatuh tempo pilihan kasir, disalin ke invoice |
| status | enum: draft, confirmed, completed, cancelled | `draft` disiapkan, belum dipakai |
| subtotal | bigint | jumlah `line_total` item |
| tax_total | bigint, default 0 | disiapkan |
| shipping_cost | bigint, default 0 | disiapkan untuk pengiriman |
| grand_total | bigint | |
| delivery_method | enum: pickup, internal | ekspedisi ditambahkan nanti |
| shipping_address_snapshot | json? | |
| confirmed_by/at, completed_by/at, cancelled_by/at, cancel_reason | ? | |
| notes | text? | |
| timestamps, deleted_at | | |

**`b2b_order_items`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_order_id | FK | |
| product_id, variant_id | FK? | jadi kosong kalau produk dihapus permanen |
| product_name, variant_name | varchar | salinan saat transaksi |
| qty | int | satu baris per item, bukan per unit |
| base_price | bigint | harga normal varian saat transaksi |
| b2b_contract_item_id | FK? | harga kontrak yang dipakai; kosong = harga normal |
| final_price | bigint | harga yang dipakai: harga kontrak atau harga normal |
| line_total | bigint | |
| stock_insufficient | bool | penanda stok kurang saat transaksi |
| notes | text? | |
| timestamps | | |

### D. Invoice dan jadwal tagihan (fase 2)

**`b2b_invoices`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| invoice_number | varchar unik | contoh: `INV/B2B/2026/11/0001` |
| b2b_order_id | FK unik | sekarang 1 pesanan = 1 invoice |
| b2b_customer_id | FK | |
| issue_date, due_date | date | `due_date` = jatuh tempo jadwal terakhir |
| amount | bigint | pokok |
| unique_code_amount | bigint, default 0 | total kode unik; sekarang selalu 0 |
| total_to_pay | bigint | `amount + unique_code_amount` |
| paid_amount | bigint, default 0 | |
| status | enum: unpaid, partial, paid, overdue, void | |
| paid_at | datetime? | |
| voided_by/at, void_reason | ? | |
| billing_address_snapshot | json? | |
| timestamps, deleted_at | | |

**`b2b_invoice_schedules`**: jadwal tagihan, bagian yang membuat sistem siap cicilan dan kode unik per cicilan. Sekarang selalu 1 baris per invoice (`sequence = 1`, `unique_code = 0`, `amount_to_pay` = total invoice), dibuat bersama invoice oleh `OrderService`.

- **Sudah membaca/menulis tabel ini:** pembayaran (dibagi ke jadwal yang belum lunas, urut `sequence`, dan dikembalikan saat pembayaran dibatalkan), `b2b:mark-overdue` (jadwal lewat jatuh tempo → `overdue`), pembatalan pesanan (jadwal → `void`), dan log reminder (`b2b_reminder_log_invoices.b2b_invoice_schedule_id`, hanya sebagai catatan).
- **Masih membaca `b2b_invoices`:** pemilihan tagihan untuk reminder (`ReminderService::openCreditInvoices`) memakai jatuh tempo dan sisa tagihan invoice. Hasilnya sama selama 1 invoice = 1 jadwal.
- **Wajib saat cicilan diaktifkan:** reminder diubah agar memilih per jadwal (jatuh tempo dan sisa per cicilan); kalau tidak, cicilan yang telat tidak ditagih selama jatuh tempo akhir invoice belum lewat. Laporan umur piutang (fase 5) sebaiknya langsung dihitung dari jadwal.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_invoice_id | FK | |
| sequence | tinyint, default 1 | |
| due_date | date | |
| amount | bigint | pokok |
| unique_code | smallint, default 0 | 1–999 setelah kode unik aktif; sekarang 0 |
| amount_to_pay | bigint | `amount + unique_code` |
| paid_amount | bigint, default 0 | |
| status | enum: unpaid, partial, paid, overdue, void | |
| paid_at | datetime? | |
| timestamps | | index `(status, due_date)` dan `(amount_to_pay, status)` |

### E. Pembayaran (fase 3)

**`b2b_bank_accounts`**: sekarang hanya 1 baris (BCA).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| bank_code | varchar | `BCA` |
| account_number, account_name | varchar | ditampilkan di invoice |
| statement_parser | varchar | `bca` |
| is_active | bool | |
| timestamps | | |

**`b2b_mutation_imports`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_bank_account_id | FK | |
| file_path, original_filename | varchar | |
| period_start, period_end | date | |
| total_rows, credit_rows, new_rows, duplicate_rows, matched_rows | int | |
| uploaded_by | FK users | |
| timestamps | | |

**`b2b_incoming_funds`**: satu baris per dana masuk.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_bank_account_id | FK | |
| b2b_mutation_import_id | FK? | |
| source | enum: statement_upload, api | `api` untuk nanti |
| transaction_date | date | |
| amount | bigint | |
| description | text | berita transfer |
| sender_name | varchar? | |
| balance | bigint? | saldo setelah transaksi, kalau ada |
| fingerprint | char(64) unik | anti dobel |
| match_status | enum: unmatched, suggested, matched, ignored | |
| match_method | enum: exact_amount, invoice_number, manual? | |
| suggested_customer_id | FK? | |
| b2b_payment_id | FK? | |
| ignored_reason | varchar? | |
| raw | json | baris asli dari file |
| timestamps | | |

**`b2b_payments`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| payment_number | varchar unik | |
| b2b_customer_id | FK | |
| b2b_incoming_fund_id | FK? unik | kosong kalau tunai |
| method | enum: bank_transfer, cash, other | |
| amount, allocated_amount, unallocated_amount | bigint | `unallocated_amount` = saldo lebih bayar |
| paid_at | datetime | |
| proof_path | varchar? | |
| status | enum: confirmed, reversed | |
| reversed_by/at, reverse_reason | ? | |
| recorded_by | FK users | |
| notes | text? | |
| timestamps | | |

**`b2b_payment_allocations`**: penghubung pembayaran ↔ tagihan, mencatat berapa rupiah dari satu pembayaran dipakai untuk invoice/jadwal mana. Satu pembayaran bisa dibagi ke beberapa jadwal (cicilan) dan, nanti, beberapa invoice (satu transfer untuk banyak tagihan). Sekarang pembayaran selalu untuk satu invoice dan nominalnya dibatasi sisa tagihan, jadi biasanya 1 baris per pembayaran.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_payment_id | FK | |
| b2b_invoice_id | FK, index | invoice yang dibayar |
| b2b_invoice_schedule_id | FK? | jadwal yang dibayar; kosong kalau sisa uang tidak masuk jadwal mana pun |
| amount | bigint | bagian pembayaran untuk baris ini |
| timestamps | | |

Pembayaran yang dibatalkan tidak menghapus baris alokasi: status `b2b_payments` menjadi `reversed` dan `paid_amount` invoice/jadwal dikurangi sebesar alokasinya. Petugas dicatat di `b2b_payments.received_by`, bukan di alokasi.

**`b2b_customer_sender_aliases`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_customer_id | FK | |
| sender_name | varchar | dinormalisasi; unik bersama customer |
| times_matched | int | |
| last_matched_at | datetime | |
| timestamps | | |

### F. Reminder (fase 4)

Jadwal reminder disimpan di customer (lihat `b2b_customers`), jadi tabel `b2b_reminder_schedules` dibatalkan.

**Kolom tambahan:** `b2b_invoices.overdue_at` dan `b2b_invoice_schedules.overdue_at` (timestamp?, kapan pertama kali ditandai lewat jatuh tempo; tidak dikosongkan lagi saat lunas supaya riwayat telat tetap terlihat).

**`b2b_reminder_logs`** (satu baris per kiriman, karena email digabung per customer)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_customer_id | FK | |
| channel | enum: email, whatsapp | |
| recipient | varchar | email / nomor tujuan |
| reminder_date | date | |
| trigger | enum: auto, manual | |
| status | enum: queued, sent, failed, skipped | |
| subject, body | varchar?, longtext? | isi email/pesan untuk log email |
| attachments | json? | nama file PDF yang dilampirkan |
| provider_message_id | varchar? | id pesan WhatsApp |
| error | text? | |
| sent_by | FK users? | diisi untuk kiriman manual |
| sent_at | datetime? | |
| timestamps | | kiriman otomatis unik per customer + channel + tanggal; manual boleh berulang |

**`b2b_reminder_log_invoices`**: b2b_reminder_log_id, b2b_invoice_id, b2b_invoice_schedule_id?, offset_days (hari relatif ke jatuh tempo; positif = lewat).

### G. Pendukung (fase 2)

**`b2b_sequences`**: id, type (customer/contract/order/invoice/payment), period (`2026` atau `202611`), last_number, timestamps. Kombinasi type dan period unik, dan nomor diambil dengan lock supaya nomor dokumen tidak dobel.

**`b2b_activity_logs`**: id, user_id, subject_type, subject_id, action, changes (json), created_at. Sekarang mencatat customer (dibuat, diubah, diaktifkan/dinonaktifkan, dihapus), kontrak (dibuat, diubah beserta jumlah item dan perubahan file, dihentikan, dihapus), dan pesanan (dibuat, selesai, dibatalkan). Nanti juga pencocokan manual dan pembatalan pembayaran.

### Tabel `configs` yang sudah ada

| Kunci | Nilai awal | Status |
|---|---|---|
| `b2b.default_payment_term_days` | 7 (cadangan di kode juga 7, maks 180) | sudah dipakai: saran jatuh tempo di kasir |
| `b2b.unique_code.min` / `max` | 1 / 999 | rencana fase 3 |
| `b2b.unique_code.mode` | `add` | rencana fase 3 |
| `b2b.allow_installment` | false | rencana |
| `b2b.reminder.send_time` | `08:00` | rencana fase 4 (jam kirim saja, jadwal tetap per customer) |
| `b2b.reminder.whatsapp_enabled` | false | rencana fase 4 |

---

## 5. Relasi antartabel

```
b2b_customers ─┬─< b2b_customer_sender_aliases
               ├─< b2b_contracts ─< b2b_contract_items ──> variant_products (→ outlet)
               └─< b2b_orders ─< b2b_order_items ──> products / variant_products / b2b_contract_items
                       │
                       └── b2b_invoices ─< b2b_invoice_schedules ─< b2b_reminder_logs
                                                    ▲
                                    b2b_payment_allocations
                                                    │
b2b_bank_accounts ─< b2b_mutation_imports ─< b2b_incoming_funds ──> b2b_payments ─┘
```

---

## 6. Proses otomatis harian

Polanya sama dengan `email:birthday` di `app/Console/Kernel.php` (zona waktu Asia/Jakarta, pengiriman lewat queue job).

| Command | Jadwal (WIB) | Fungsi |
|---|---|---|
| `b2b:mark-overdue` | 00:05 | invoice dan jadwal tagihan tempo yang lewat jatuh tempo dan belum lunas → `overdue` + `overdue_at`, dicatat `invoice.overdue` di log aktivitas |
| `b2b:send-due-reminders` | 09:00 | kirim reminder sesuai jadwal customer lewat queue (retry maks 17:00) |

Status invoice dihitung ulang setiap ada pembayaran atau pembatalan pembayaran (`PaymentService`): sisa 0 → `paid`; masih sisa dan sudah lewat jatuh tempo → `overdue`; selain itu `partial`/`unpaid`. `Invoice::displayStatus()` tetap menghitung dari tanggal sebagai cadangan sebelum `mark-overdue` jalan.

---|---|---|
| `b2b:mark-overdue` | 00:05 | jadwal tagihan yang lewat jatuh tempo → `overdue` |
| `b2b:send-due-reminders` | sesuai config (default 08:00) | kirim reminder sesuai jadwal lewat queue |

Belum ada yang dibangun. Status kontrak tidak butuh command harian karena dihitung dari tanggal, jadi `b2b:sync-contract-status` dihapus 18 Nov 2026. Status overdue invoice juga sudah dihitung dari tanggal saat ditampilkan (`Invoice::displayStatus()`), jadi `b2b:mark-overdue` hanya perlu untuk laporan dan reminder.

---

## 7. Menu dan permission

Menu **B2B** didaftarkan lewat `B2bMenuSeeder`:

| Menu | URL / permission |
|---|---|
| Customer | `b2b/customers` |
| Kontrak | `b2b/contracts` |
| Kasir B2B | `b2b/cashier` |
| Pesanan | `b2b/orders` |
| Invoice | `b2b/invoices` |
| Reminder (Aktivitas + log email, WhatsApp, Customer) | `b2b/reminders` |
| Pembayaran (Upload Mutasi, Rekonsiliasi) | `b2b/payments` |
| Laporan | `b2b/reports` |
| Pengaturan (Rekening) | `b2b/settings` |

Setiap URL mendapat permission `read|create|update|delete <url>`, ditambah `pay b2b/invoices` untuk mencatat pelunasan, `cancel b2b/orders` (batal pesanan dikonfirmasi), dan `cancel-completed b2b/orders` (batal pesanan selesai). Menu Reminder: `read b2b/reminders`, `send b2b/reminders` (kirim ulang/manual), `manage-whatsapp b2b/reminders` (hubungkan/putuskan nomor).

---

## 8. Ditunda (keep in mind)

- Format file mutasi BCA dan contoh filenya (dibutuhkan sebelum fase 3)
- Mutasi otomatis lewat API BCA atau layanan pihak ketiga
- Status baca WhatsApp dan notifikasi admin saat nomor WhatsApp terputus
- Pengiriman dan ekspedisi, termasuk kolom berat produk untuk cek ongkir
- Cicilan (struktur tabel sudah siap)

## 9. Perlu dikonfirmasi

- **Invoice selalu satu per pesanan (diputuskan 19 Nov 2026):** belum ada penagihan gabungan per periode, jadi `b2b_invoices.b2b_order_id` tetap unik. Menu Pesanan untuk operasional barang, menu Invoice dijadikan ruang kerja penagihan (pelunasan tempo, filter belum lunas/lewat jatuh tempo).
- Apakah NPWP wajib diisi?
- **Usulan:** tagihan dianggap lunas kalau dana masuk sudah ≥ pokok. Kode unik hanya penanda, jadi customer yang lupa menambahkan kode unik tetap dianggap lunas.
- **Bayar langsung (diputuskan 18 Nov 2026):** tidak semua pesanan tempo; ada customer yang langsung membayar saat pesan.
  - Metode: Tunai, Transfer BCA, EDC/kartu, QRIS. Memakai master pembayaran POS yang sudah ada (`category_payments` → `payments`, seperti `transactions.category_payment_id` + `tipe_pembayaran` + nama snapshot).
  - Transfer di depan kasir langsung dianggap Lunas tanpa verifikasi mutasi (aturan "hanya lewat mutasi" berlaku untuk pelunasan tempo).
  - Tanpa DP: bayar penuh sekarang atau tempo penuh. Skema alokasi tetap memungkinkan DP ditambah nanti.
  - Kasir B2B tidak bergantung pada shift POS: tunai B2B tidak ikut setoran shift, direkap di laporan B2B per outlet/kasir (`b2b_payments.outlet_id`, `received_by`; refund lewat `b2b_refunds.outlet_id`).
  - Batal pesanan yang sudah dibayar: pembayaran di-reverse dengan alasan, refund dicatat (metode, nominal, petugas, waktu).
  - Skema (disetujui 19 Nov 2026; `payment_type` di migration order, tabel pembayaran di migration invoice `2026_11_14_100005`): `b2b_orders.payment_type` (`credit`/`immediate`), `b2b_payments` (+ `outlet_id`, `category_payment_id`, `payment_id`, nama metode snapshot, referensi), `b2b_payment_allocations`, `b2b_refunds`.

---

## 10. Yang sudah dibangun (per 21 Nov 2026)

- Commit: `2906e8a` (fase 1–2), `470fe1b` (satu kontak dan satu alamat), `865727f` (kontrak harga per varian).
- Migration 12 tabel: `b2b_sequences`, `b2b_activity_logs`, `b2b_customers`, `b2b_contracts`, `b2b_contract_items`, `b2b_orders`, `b2b_order_items`, `b2b_invoices`, `b2b_invoice_schedules`, `b2b_payments`, `b2b_payment_allocations`, `b2b_refunds` (3 terakhir sejak 19 Nov, belum di-commit).
- Service: `PricingService`, `ContractService`, `CustomerService`, `OrderService`, `PaymentService`, `SequenceService`, `ActivityLogger`, `B2bSettings` (`app/Services/B2b`).
- Menu `B2bMenuSeeder`: Kasir B2B, Pesanan, Invoice, Customer, Kontrak.
- Tidak ada command terjadwal B2B.
- Invoice dicetak dari browser (Cetak / Simpan PDF). Library PDF ditambahkan saat reminder email butuh lampiran.
- Menu Invoice sekarang hanya daftar dan cetak, karena pencatatan pembayaran (fase 3) belum ada.
- Bayar langsung (19 Nov): `OrderService::place()` menerima `payment_type` + `payment`, `cancel()` menerima data refund. Dialog kasir punya pilihan Tempo / Bayar langsung (metode dari master POS, sub metode, no. referensi, uang diterima + kembalian untuk tunai). Detail pesanan menampilkan jenis pembayaran, riwayat pembayaran dan pengembalian dana; pesanan lunas dibatalkan lewat dialog refund (metode default = metode bayar semula). Cetak invoice menampilkan LUNAS + nomor bukti bayar.
- Pelunasan tempo manual (19 Nov, B5): menu Invoice dan detail pesanan punya dialog "Catat Pelunasan" (nominal boleh sebagian, tanggal bayar mundur s.d. tanggal invoice, metode dari master POS, outlet penerima atau rekening pusat, no. referensi, bukti opsional `b2b_payments.attachment_path`, catatan). Semua metode termasuk transfer boleh dicatat manual sampai fitur mutasi ada. Pelunasan salah catat bisa dibatalkan tanpa refund (`PaymentService::reverse`); pembayaran pesanan bayar langsung tetap lewat batal pesanan + refund. Permission `pay b2b/invoices`.
- 20 Nov: pesanan "Diambil sendiri" langsung Selesai; batal pesanan Selesai butuh permission `cancel-completed b2b/orders`.
- Fase 4 (20–21 Nov):
  - `b2b:mark-overdue` (00:05 WIB) menulis status `overdue` + `overdue_at`; status dihitung ulang tiap bayar/batal bayar (`OverdueService`, `PaymentService`).
  - Reminder otomatis `b2b:send-due-reminders` (09:00 WIB, retry s.d. 17:00): jadwal per customer, setelah jatuh tempo harian s.d. H+7 lalu mingguan, satu kiriman gabungan per customer per channel per hari (`ReminderService`, `SendReminderJob`).
  - Email `InvoiceReminderMail` + PDF invoice (dompdf, `InvoicePdfService`, template `invoices/pdf.blade.php`); tombol Unduh PDF di cetak invoice.
  - WhatsApp lewat gateway Baileys terpisah (repo `udjaya-wa-gateway`, config `services.b2b_wa_gateway` / `B2B_WA_GATEWAY_*`): teks ringkasan + PDF per invoice; nomor tidak terdaftar dan kiriman sebagian tidak di-retry (hindari pesan dobel).
  - Menu Reminder (`b2b/reminders`): tab Aktivitas (log + detail isi email/WhatsApp, kirim ulang), tab Customer (channel, jadwal, data kontak kurang), tab WhatsApp (status, QR / kode pairing, putuskan, pesan uji), saklar darurat per channel. Permission `read`, `send`, `toggle`, `manage-whatsapp b2b/reminders`.
  - Kirim reminder manual per invoice (detail pesanan + daftar invoice) dengan pilihan Email / WhatsApp, riwayat reminder di kartu Invoice.
  - Tabel `b2b_reminder_logs`, `b2b_reminder_log_invoices`; kolom `overdue_at`.
- Laporan B2B langkah 1 (21 Nov): menu `b2b/reports` + permission `read b2b/reports`, `ReportController` (halaman + endpoint `data/{bagian}`), `ReportService::summary()`, `App\Support\B2b\ReportFilter`, halaman dengan filter periode/outlet/customer dan 6 kartu KPI (penjualan, jumlah pesanan, kas masuk bersih, piutang, lewat jatuh tempo, batal). Helper JS `B2b.rangePicker` dan `B2b.ajaxSelect` dipisah dari `list-filters` supaya dipakai bersama.
- Belum ada: draft pesanan, pelunasan tempo otomatis (kode unik/VA), laporan B2B langkah 2–8, status baca WhatsApp + notifikasi nomor terputus.
- Test: `tests/Feature/B2b` (126 test, 21 Nov 2026).

### Perubahan 14 Nov 2026 (bedah kolom)

- `payment_term_days` dihapus dari customer dan kontrak. Jatuh tempo dipilih manual di kasir (`b2b_orders.due_date`, disalin ke invoice). Saran dari config `b2b.default_payment_term_days` (seeder: 7), cadangan di kode 7 hari. Maksimal 180 hari.
- Kontrak wajib minimal satu aturan diskon (sejak 18 Nov: minimal satu harga produk).
- Kolom `type` customer: enum Perorangan, PT, CV (wajib).
- Customer: `created_by` + `modified_by` sebagai audit trail tanpa foreign key/index.
- Reminder per customer (lihat tabel customer). Rencana `b2b_reminder_schedules` dan config reminder global dibatalkan.

### Perubahan 17 Nov 2026 (satu kontak dan satu alamat)

- Tabel `b2b_customer_contacts` dan `b2b_customer_addresses` dihapus.
- Kolom `contact_name`, `address`, `city`, `province`, `postal_code` pindah ke `b2b_customers`. `phone` dan `email` customer jadi tujuan reminder.
- Alamat customer disalin ke pesanan (`shipping_address_snapshot`) dan invoice (`billing_address_snapshot`).

### Perubahan 18 Nov 2026 (kontrak harga per varian)

- `b2b_contract_rules` (diskon persen/nominal/harga khusus per scope) diganti `b2b_contract_items` (harga tetap per varian + `min_qty`).
- Item pesanan menyimpan `b2b_contract_item_id` dan `final_price`. Kolom diskon (`discount_total`, `discount_type`, `discount_value`, `discount_per_unit`) dihapus.
- Status `draft` dan `expired` dihapus dari kontrak, begitu juga `activated_by/at` dan command `b2b:sync-contract-status`.
- Kontrak bisa diedit saat berjalan, overlap dicek saat buat dan edit, soft delete (kontrak berjalan tidak bisa dihapus).
- File kontrak menyimpan nama asli (`attachment_name`) dan bisa dilepas saat edit.
- Tampilan baru daftar, detail, dan form kontrak: tabel pilih produk per outlet, date range picker, format ribuan harga.

## 11. Riwayat keputusan

| Tanggal | Keputusan |
|---|---|
| 14 Nov 2026 | Tabel `b2b_` terpisah. Produk yang sudah ada, per outlet. Stok berkurang. Hanya web backoffice. Belum ada cicilan tapi siap. Tanpa PPN. Reminder dan limit kredit dinamis (limit kredit dihapus 19 Nov). 1 kontrak aktif per customer |
| 14 Nov 2026 | Customer global. Stok boleh minus. Tanpa kontrak = harga normal. Kontrak tanpa approval. Laporan B2B terpisah |
| 14 Nov 2026 | Tidak pakai VA/payment gateway. Pakai rekening statis + kode unik + cek mutasi |
| 14 Nov 2026 | 1 rekening BCA. Kode unik ditambahkan. Format mutasi dibahas nanti |
| 14 Nov 2026 | Metode pembayaran dibuka lagi (VA BCA langsung jadi kandidat). Pembayaran ditunda; bangun customer + kasir dulu |
| 17 Nov 2026 | Satu kontak dan satu alamat per customer |
| 18 Nov 2026 | Kontrak = harga tetap per varian dengan tingkat qty, bisa lintas outlet. Produk di luar kontrak harga normal. Pesanan lama tetap memakai harga snapshot. Tanpa draft. Bisa diedit saat berjalan. Overlap dicek saat buat dan edit. Harga boleh di atas harga normal. Soft delete, kontrak berjalan tidak bisa dihapus |
| 19 Nov 2026 | Limit kredit dihapus dari rencana: tidak ada aturan limit kredit maupun override. Kolom `credit_check_result`, `credit_override_by`, `credit_override_reason` dibuang dari `b2b_orders` |
| 20 Nov 2026 | Pesanan "Diambil sendiri" langsung berstatus Selesai saat dibuat (`completed_by/at` = kasir). Pesanan kurir internal tetap Dikonfirmasi sampai ditandai selesai. Pesanan Selesai tetap bisa dibatalkan (stok kembali, invoice void, refund bila sudah dibayar) untuk koreksi salah input, dengan permission terpisah `cancel-completed b2b/orders` |
| 20 Nov 2026 | Fase 4: `mark-overdue` menulis status + `overdue_at`, status dihitung ulang tiap bayar/batal bayar. Reminder harian s.d. H+7 lalu mingguan, mulai 09:00 (retry s.d. 17:00), email gabungan per customer + PDF. WhatsApp via Baileys di VPS terpisah. Menu Reminder + saklar darurat |
