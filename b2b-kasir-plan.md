# Rencana Fitur Kasir B2B

- **Status:** Fase 1 (customer, kontrak, harga) dan Fase 2 (kasir, pesanan, invoice) sudah dibangun 14 Nov 2026. Pembayaran ditunda sampai metodenya diputuskan (kode unik vs VA BCA).
- **Tanggal rekap:** 14 November 2026
- **Presentasi:** `b2b-kasir-presentasi.html` (buka langsung di browser)

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
| Customer | Global, tidak terikat outlet. Data: nama, telepon, alamat, email. Kontak dan alamat bisa lebih dari satu |
| Barang | Produk yang sudah ada. Setiap pesanan wajib memilih outlet (produk terikat `outlet_id`) |
| Stok | Stok varian berkurang saat pesanan dikonfirmasi. **Boleh minus**, cukup peringatan. Dikembalikan kalau pesanan batal |
| Platform | Web backoffice yang dibuka dari tablet Android lewat browser. Tanpa petty cash |
| Kontrak | Maksimal 1 kontrak aktif per customer. Pembuatnya bisa langsung mengaktifkan, tanpa approval |
| Tanpa kontrak | Customer tetap boleh belanja dengan harga normal |
| Pajak | Tanpa PPN. NPWP opsional |
| Pembayaran | Jatuh tempo, belum ada cicilan. Struktur tabel sudah siap untuk cicilan |
| Rekening | **Ditunda.** Opsi: rekening statis BCA + kode unik, atau VA BCA langsung 1 nomor per customer (± Rp 2.271/transaksi). Menunggu jawaban BCA |
| Kode unik | **Ditunda.** Kolom sudah disiapkan (`unique_code`, `unique_code_amount`), sekarang selalu 0 |
| Cek pembayaran | Finance upload file mutasi BCA, sistem mencocokkan otomatis, sisanya dicek manual |
| Reminder | **Diatur per customer, tanpa pengaturan global.** Default mati; kalau tidak diatur, tidak ada yang dikirim. Channel + jadwal di customer, penerima = kontak yang dicentang "Terima reminder" |
| Limit kredit | Aturan bisa diatur dari UI, dengan pengecualian per customer |
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
Kasir B2B: pilih outlet → pilih customer → pilih produk → harga otomatis kena diskon kontrak
        ▼  konfirmasi (cek limit kredit → stok dikurangi, boleh minus)
Invoice + jadwal tagihan (jatuh tempo = tanggal invoice + termin; nominal + kode unik)
        ▼
Reminder otomatis (H-7, H-3, H-1, H-0, lalu setiap hari setelah jatuh tempo)
        ▼
Customer transfer ke rekening BCA perusahaan (dari bank mana saja)
        ▼
Finance upload file mutasi BCA → pencocokan otomatis → tagihan lunas
                                  └─ tidak cocok → dicek manual oleh finance
```

### Perhitungan harga kontrak

- Dihitung di backend (`B2bPricingService`). Layar kasir hanya menampilkan preview, lalu server menghitung ulang saat pesanan disimpan.
- Aturan yang paling spesifik menang: **varian → produk → kategori → semua produk**.
- Di level yang sama, dipilih `min_qty` terbesar yang masih ≤ qty (harga bertingkat).
- Tipe diskon: persen (dibulatkan ke rupiah), potongan nominal per unit, atau harga khusus. Harga akhir tidak boleh di bawah 0.
- Customer tanpa kontrak aktif mendapat harga normal.
- Diskon kontrak tidak digabung dengan promo atau diskon POS.
- Harga dan diskon **disalin** ke item pesanan saat transaksi, jadi perubahan kontrak tidak mengubah pesanan lama.

### Aturan kontrak

- Status: `draft` → `active` → `expired` (otomatis tiap hari) atau `terminated` (manual).
- Periode kontrak milik customer yang sama tidak boleh tumpang tindih, jadi selalu maksimal 1 yang aktif.
- Perpanjangan dibuat sebagai kontrak baru, dengan tombol untuk menyalin aturan dari kontrak lama.
- Diskon kontrak yang sedang aktif tidak bisa diedit. Kalau diskon berubah, kontrak lama dihentikan lalu dibuat kontrak baru.

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

### Reminder

- Jadwal global ada di tabel `b2b_reminder_schedules`. Default: H-7, H-3, H-1, H-0, lalu Overdue mulai H+1 setiap hari sampai lunas.
- Per customer: `reminder_enabled` dan `reminder_channels` (kosong = ikut pengaturan global).
- Per kontak: `receive_email_reminder` dan `receive_wa_reminder`.
- Channel dibuat sebagai driver. Email langsung jalan. WhatsApp aktif setelah provider dipilih (template pesan WhatsApp resmi perlu disetujui Meta).
- Isi reminder: rincian tagihan, nominal + kode unik, rekening BCA tujuan, dan PDF invoice.

### Limit kredit

- Jenis aturan ditentukan di kode, nilai dan aksinya (`warn` / `block`) diatur dari UI.
- Dicek saat pesanan dikonfirmasi. Kalau aksinya `block`, user dengan permission `override b2b/credit-limit` bisa tetap meloloskan pesanan dengan alasan, dan itu tercatat di pesanan dan activity log.

| code | Arti |
|---|---|
| `max_outstanding_amount` | batas total piutang yang belum lunas (limit kredit) |
| `max_overdue_invoices` | batas jumlah tagihan yang lewat jatuh tempo |
| `max_overdue_days` | batas hari keterlambatan tagihan tertua |
| `max_open_invoices` | batas jumlah tagihan yang belum lunas |

---

## 3. Tahapan pengerjaan

| Fase | Isi | Tabel |
|---|---|---|
| **1** | Data customer (kontak, alamat), kontrak dan aturan diskon, perhitungan harga, menu dan permission | customers, customer_contacts, customer_addresses, contracts, contract_rules |
| **2** | Kasir B2B: pesanan → invoice + jadwal tagihan (dengan kode unik), PDF invoice, pengurangan stok | orders, order_items, invoices, invoice_schedules, sequences, activity_logs |
| **3** | Pembayaran: rekening, upload mutasi BCA, pencocokan otomatis, daftar cek manual, input tunai, saldo customer | bank_accounts, mutation_imports, incoming_funds, payments, payment_allocations, customer_sender_aliases |
| **4** | Reminder, status overdue, aturan limit kredit | reminder_schedules, reminder_logs, credit_rules, customer_credit_rules |
| **5** | Laporan B2B: penjualan per customer/outlet/produk, umur piutang, mutasi pembayaran, kontrak yang hampir berakhir | (membaca tabel di atas) |
| Nanti | Mutasi otomatis, WhatsApp, pengiriman dan ekspedisi, cicilan | — |

Fase 3 membutuhkan contoh file mutasi BCA untuk membuat parser.

---

## 4. Struktur tabel

Keterangan: `?` = boleh kosong, FK = relasi. Nominal rupiah disimpan sebagai `bigint`, sama seperti tabel yang sudah ada. Total: **21 tabel baru**.

### A. Customer (fase 1)

**`b2b_customers`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| code | varchar(30) unik | contoh: `CUS-B2B-0001` |
| type | enum: individual, pt, cv | Perorangan/PT/CV. Hanya klasifikasi, tidak ditempel ke nama |
| name | varchar | nama perusahaan/usaha |
| npwp | varchar(30)? | |
| phone, email | varchar? | kontak umum |
| reminder_enabled | bool, default false | tidak diatur = tidak dikirim |
| reminder_channels | json? | `["email"]`, `["whatsapp"]`, atau keduanya |
| reminder_days | json? | hari relatif ke jatuh tempo: -7, -3, -1, 0 |
| reminder_overdue_daily | bool, default false | kirim tiap hari setelah lewat jatuh tempo |
| notes | text? | |
| is_active | bool | |
| created_by, modified_by | bigint? | audit trail, tanpa foreign key/index |
| timestamps, deleted_at | | soft delete |

**`b2b_customer_contacts`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_customer_id | FK | |
| name | varchar | |
| phone, email, position | varchar? | |
| is_primary | bool | |
| receive_reminder | bool, default false | lewat channel yang dipilih di customer |
| timestamps, deleted_at | | |

**`b2b_customer_addresses`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_customer_id | FK | |
| label | varchar | Kantor, Gudang, … |
| recipient_name, phone | varchar | |
| address | text | |
| city, province, postal_code | varchar | |
| latitude, longitude | decimal? | |
| is_default_billing, is_default_shipping | bool | |
| timestamps, deleted_at | | |

### B. Kontrak (fase 1)

**`b2b_contracts`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_customer_id | FK | |
| contract_number | varchar unik | contoh: `KTR/B2B/2026/0001` |
| start_date, end_date | date | periode tidak boleh tumpang tindih |
| status | enum: draft, active, expired, terminated | |
| attachment_path | varchar? | file kontrak |
| notes | text? | |
| activated_by, activated_at | FK users?, datetime? | siapa yang mengaktifkan |
| terminated_by, terminated_at, termination_reason | ? | |
| created_by | FK users | |
| timestamps, deleted_at | | |

**`b2b_contract_rules`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_contract_id | FK | |
| scope | enum: all, category, product, variant | |
| outlet_id | FK? | kosong = semua outlet |
| category_id, product_id, variant_id | FK? | diisi sesuai `scope` (kolom FK terpisah, bukan polymorphic) |
| discount_type | enum: percent, nominal, fixed_price | |
| discount_value | decimal(15,2) | persen, rupiah per unit, atau harga khusus |
| min_qty | int, default 1 | harga bertingkat |
| timestamps | | |

Validasi di aplikasi: kombinasi `(contract, scope, outlet, target, min_qty)` tidak boleh dobel.

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
| status | enum: draft, confirmed, completed, cancelled | |
| subtotal, discount_total | bigint | |
| tax_total | bigint, default 0 | disiapkan |
| shipping_cost | bigint, default 0 | disiapkan untuk pengiriman |
| grand_total | bigint | |
| delivery_method | enum: pickup, internal | ekspedisi ditambahkan nanti |
| shipping_address_snapshot | json? | |
| credit_check_result | json? | hasil cek limit kredit |
| credit_override_by, credit_override_reason | ? | |
| confirmed_by/at, cancelled_by/at, cancel_reason | ? | |
| notes | text? | |
| timestamps, deleted_at | | |

**`b2b_order_items`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_order_id | FK | |
| product_id, variant_id | FK | |
| product_name, variant_name | varchar | salinan saat transaksi |
| qty | int | satu baris per item, bukan per unit |
| base_price | bigint | harga varian saat transaksi |
| b2b_contract_rule_id | FK? | aturan yang dipakai |
| discount_type, discount_value | ? | salinan aturan |
| discount_per_unit, final_price, line_total | bigint | |
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
| unique_code_amount | bigint | total kode unik |
| total_to_pay | bigint | `amount + unique_code_amount` |
| paid_amount | bigint, default 0 | |
| status | enum: unpaid, partial, paid, overdue, void | |
| paid_at | datetime? | |
| voided_by/at, void_reason | ? | |
| billing_address_snapshot | json? | |
| timestamps, deleted_at | | |

**`b2b_invoice_schedules`**: bagian yang membuat sistem siap cicilan. Sekarang selalu 1 baris per invoice. Reminder, status overdue, dan umur piutang membaca tabel ini.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_invoice_id | FK | |
| sequence | tinyint, default 1 | |
| due_date | date | |
| amount | bigint | pokok |
| unique_code | smallint | 1–999 |
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

**`b2b_payment_allocations`**: satu transfer bisa melunasi beberapa tagihan, dan nanti juga cicilan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_payment_id | FK | |
| b2b_invoice_schedule_id | FK | |
| amount | bigint | |
| allocated_by | FK users | |
| timestamps | | |

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

**`b2b_reminder_schedules`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar | contoh: "H-3" |
| offset_days | smallint | negatif = sebelum jatuh tempo, 0 = hari H, positif = sesudahnya |
| repeat_every_days | smallint? | 1 = setiap hari |
| max_repeat | smallint? | kosong = diulang sampai lunas |
| channels | json | `["email"]`, `["whatsapp"]`, atau keduanya |
| template_key | varchar | |
| is_active | bool | |
| sort_order | smallint | |
| timestamps | | |

**`b2b_reminder_logs`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| b2b_invoice_schedule_id | FK | |
| b2b_reminder_schedule_id | FK | |
| channel | enum: email, whatsapp | |
| recipient | varchar | |
| reminder_date | date | |
| status | enum: queued, sent, failed, skipped | |
| error | text? | |
| sent_at | datetime? | |
| timestamps | | unik `(schedule, channel, recipient, reminder_date)` supaya tidak terkirim dobel |

### G. Limit kredit (fase 4)

**`b2b_credit_rules`**: id, code (unik), value (bigint), action (`warn`/`block`), is_active, description, timestamps.

**`b2b_customer_credit_rules`**: id, b2b_customer_id (FK), b2b_credit_rule_id (FK), value?, action?, is_active, timestamps. Kombinasi customer dan rule unik.

### H. Pendukung (fase 2)

**`b2b_sequences`**: id, type (customer/contract/order/invoice/payment), period (`2026` atau `202611`), last_number, timestamps. Kombinasi type dan period unik, dan nomor diambil dengan lock supaya nomor dokumen tidak dobel.

**`b2b_activity_logs`**: id, user_id, subject_type, subject_id, action, changes (json), created_at. Dipakai untuk aktivasi/penghentian kontrak, void invoice, pencocokan manual, pembatalan pembayaran, dan limit kredit yang diloloskan.

### Tabel `configs` yang sudah ada

| Kunci | Nilai awal |
|---|---|
| `b2b.default_payment_term_days` | 30 |
| `b2b.unique_code.min` / `max` | 1 / 999 |
| `b2b.unique_code.mode` | `add` |
| `b2b.allow_installment` | false |
| `b2b.reminder.send_time` | `08:00` |
| `b2b.reminder.whatsapp_enabled` | false |

---

## 5. Relasi antartabel

```
b2b_customers ─┬─< b2b_customer_contacts
               ├─< b2b_customer_addresses
               ├─< b2b_customer_sender_aliases
               ├─< b2b_customer_credit_rules >── b2b_credit_rules
               ├─< b2b_contracts ─< b2b_contract_rules ──> outlets / categories / products / variant_products
               └─< b2b_orders ─< b2b_order_items ──> products / variant_products
                       │
                       └── b2b_invoices ─< b2b_invoice_schedules ─< b2b_reminder_logs >── b2b_reminder_schedules
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
| `b2b:sync-contract-status` | 00:01 | kontrak yang lewat `end_date` → `expired` |
| `b2b:mark-overdue` | 00:05 | jadwal tagihan yang lewat jatuh tempo → `overdue` |
| `b2b:send-due-reminders` | sesuai config (default 08:00) | kirim reminder sesuai jadwal lewat queue |

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
| Pembayaran (Upload Mutasi, Rekonsiliasi) | `b2b/payments` |
| Laporan | `b2b/reports` |
| Pengaturan (Rekening, Reminder, Limit Kredit) | `b2b/settings` |

Setiap URL mendapat permission `read|create|update|delete <url>`. Ada satu permission khusus: `override b2b/credit-limit`.

---

## 8. Ditunda (keep in mind)

- Format file mutasi BCA dan contoh filenya (dibutuhkan sebelum fase 3)
- Mutasi otomatis lewat API BCA atau layanan pihak ketiga
- Provider WhatsApp untuk reminder
- Pengiriman dan ekspedisi, termasuk kolom berat produk untuk cek ongkir
- Cicilan (struktur tabel sudah siap)

## 9. Perlu dikonfirmasi

- Apakah NPWP wajib diisi?
- **Usulan:** tagihan dianggap lunas kalau dana masuk sudah ≥ pokok. Kode unik hanya penanda, jadi customer yang lupa menambahkan kode unik tetap dianggap lunas.

---

## 10. Yang sudah dibangun (14 Nov 2026)

- Migration 11 tabel: `b2b_sequences`, `b2b_activity_logs`, `b2b_customers`, `b2b_customer_contacts`, `b2b_customer_addresses`, `b2b_contracts`, `b2b_contract_rules`, `b2b_orders`, `b2b_order_items`, `b2b_invoices`, `b2b_invoice_schedules`.
- Service: `PricingService`, `ContractService`, `CustomerService`, `OrderService`, `SequenceService`, `ActivityLogger` (`app/Services/B2b`).
- Menu `B2bMenuSeeder`: Kasir B2B, Pesanan, Invoice, Customer, Kontrak.
- Command `b2b:sync-contract-status` dijadwalkan 00:01 WIB.
- Invoice dicetak dari browser (Cetak / Simpan PDF). Library PDF ditambahkan saat reminder email butuh lampiran.
- Belum ada: draft pesanan, cek limit kredit, pembayaran, reminder, laporan.
- Test: `tests/Feature/B2b` (44 test).

### Perubahan 14 Nov 2026 (bedah kolom)

- `payment_term_days` dihapus dari customer dan kontrak. Jatuh tempo dipilih manual di kasir (`b2b_orders.due_date`, disalin ke invoice). Saran dari config `b2b.default_payment_term_days` (seeder: 7), cadangan di kode 7 hari. Maksimal 180 hari.
- Kontrak wajib minimal satu aturan diskon.
- Kolom `type` customer: enum Perorangan, PT, CV (wajib).
- Customer: `created_by` + `modified_by` sebagai audit trail tanpa foreign key/index.
- Reminder per customer (lihat tabel customer). Rencana `b2b_reminder_schedules` dan config reminder global dibatalkan.

## 11. Riwayat keputusan

| Tanggal | Keputusan |
|---|---|
| 14 Nov 2026 | Tabel `b2b_` terpisah. Produk yang sudah ada, per outlet. Stok berkurang. Hanya web backoffice. Belum ada cicilan tapi siap. Tanpa PPN. Reminder dan limit kredit dinamis. 1 kontrak aktif per customer |
| 14 Nov 2026 | Customer global. Stok boleh minus. Tanpa kontrak = harga normal. Kontrak tanpa approval. Laporan B2B terpisah |
| 14 Nov 2026 | Tidak pakai VA/payment gateway. Pakai rekening statis + kode unik + cek mutasi |
| 14 Nov 2026 | 1 rekening BCA. Kode unik ditambahkan. Format mutasi dibahas nanti |
| 14 Nov 2026 | Metode pembayaran dibuka lagi (VA BCA langsung jadi kandidat). Pembayaran ditunda; bangun customer + kasir dulu |
