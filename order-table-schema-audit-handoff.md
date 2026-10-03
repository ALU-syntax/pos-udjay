# Handoff Audit Skema POS untuk Agent Order Table

Dokumen ini meneruskan hasil audit read-only terhadap migration Laravel existing dan skema MySQL aktual. Gunakan bersama:

1. `laravel-migration-handoff.md`
2. `system-design.md`, khususnya bagian 2, 4, 5, 6, 7, dan 8
3. `README.md`

Jika dokumen ini berbeda dengan contoh query pada dokumen desain, fakta skema aktual dalam dokumen ini menjadi acuan implementasi sampai dokumen desain diperbarui.

## 1. Status dan Batas Pekerjaan

- Audit dilakukan pada 14 tabel POS: `outlets`, `categories`, `products`, `variant_products`, `modifier_groups`, `modifiers`, `taxes`, `payments`, `category_payments`, `transactions`, `transaction_items`, `petty_cashes`, `shift_sessions`, dan `users`.
- Seluruh 14 tabel memakai InnoDB dan collation `utf8mb4_unicode_ci`.
- Seluruh primary key `id` pada tabel tersebut adalah `BIGINT UNSIGNED`.
- Belum ada tabel `ot_*` pada database yang diaudit.
- `outlets.latitude`, `outlets.longitude`, dan `outlets.geofence_radius_m` belum ada.
- Audit tidak membaca data dari dan tidak menulis ke `open_bills` atau `item_open_bills`.
- Agent Order Table dilarang membaca atau menulis `open_bills` dan `item_open_bills`.
- Agent Order Table tidak boleh membuat atau mengubah skema database. Semua migration dimiliki repository Laravel ini.
- Jangan membuat `ot_session_devices`.
- Jangan menjalankan migration ke production.

Persetujuan hasil audit mencakup koreksi kontrak teknis pada dokumen ini. Persetujuan tersebut tidak menyelesaikan keputusan bisnis yang masih terbuka pada bagian 8.

## 2. Ownership dan Guardrail

- Laravel adalah satu-satunya pemilik skema dan migration.
- Aplikasi Order Table hanya membaca katalog POS, menulis hasil bridge ke `transactions` dan `transaction_items`, serta membaca/menulis tabel `ot_*` setelah migration Laravel tersedia.
- Jangan mengubah tabel POS selain penambahan tiga kolom geolocation pada `outlets` yang sudah direncanakan.
- Jangan menambah foreign key dari tabel `ot_*` ke tabel POS tanpa persetujuan lanjutan, walaupun engine dan tipe ID saat ini kompatibel. Gunakan index untuk kolom referensi POS.
- Semua nominal uang milik domain `ot_*` harus menggunakan `BIGINT` integer rupiah, bukan `DECIMAL` atau `FLOAT`.
- Semua migration baru wajib mempunyai `down()` yang benar.
- Bridge ke POS harus atomic dan idempotent.

## 3. Kontrak Katalog Aktual

Contoh query katalog pada dokumen desain tidak dapat digunakan mentah-mentah. Gunakan nama dan tipe aktual berikut.

### 3.1 Categories

- Primary key: `categories.id BIGINT UNSIGNED`.
- Nama: `categories.name VARCHAR(255) NOT NULL` dan unique.
- Status: `categories.status TINYINT(1) NOT NULL DEFAULT 1`.
- Tabel memakai soft delete melalui `deleted_at`.
- Kolom `sort_order` tidak ada.

Query katalog wajib memfilter:

```sql
c.status = 1 AND c.deleted_at IS NULL
```

Jangan melakukan `ORDER BY categories.sort_order`.

### 3.2 Products

- Primary key: `products.id BIGINT UNSIGNED`.
- Kategori: `category_id BIGINT UNSIGNED NULL`, FK ke `categories.id`.
- Outlet: `outlet_id BIGINT UNSIGNED NOT NULL`, tanpa FK dan tanpa index khusus.
- Nama gambar: `photo`, bukan `image`.
- Deskripsi: `description TEXT NULL`.
- Pajak: `exclude_tax TINYINT(1) NULL`; perlakukan `NULL` sebagai false kecuali kebijakan bisnis menetapkan lain.
- Status: `status TINYINT(1) NOT NULL DEFAULT 1`.
- Tabel memakai soft delete melalui `deleted_at`.
- Kolom `sort_order` tidak ada.
- `harga_modal DECIMAL(15,2)` adalah harga modal dan tidak boleh digunakan sebagai harga jual Order Table.

Query katalog wajib memfilter `products.outlet_id`, `status = 1`, dan `deleted_at IS NULL`.

### 3.3 Variant Products

- Primary key: `variant_products.id BIGINT UNSIGNED`.
- Relasi: `product_id BIGINT UNSIGNED NOT NULL`, FK ke `products.id`.
- Harga jual: `harga BIGINT SIGNED NOT NULL`, bukan `price`.
- Stok: `stok BIGINT SIGNED NOT NULL`, bukan `stock`.
- Tabel memakai soft delete melalui `deleted_at`.

Harga otoritatif checkout berasal dari `variant_products.harga`.

### 3.4 Modifier Groups

- Primary key: `modifier_groups.id BIGINT UNSIGNED`.
- Produk: `product_id` adalah JSON nullable yang disimpan sebagai `LONGTEXT`, bukan scalar FK.
- Outlet: `outlet_id VARCHAR(255) NOT NULL`, bukan `BIGINT`.
- Required flag: `is_required TINYINT(1) NOT NULL DEFAULT 0`, bukan `required`.
- Kolom `type` tidak ada.
- Tabel memakai soft delete melalui `deleted_at`.

Jangan memakai kondisi berikut:

```sql
WHERE modifier_groups.product_id = :productId
```

Gunakan operasi JSON yang sesuai dengan bentuk data aktual. Pastikan juga filter outlet dan `deleted_at IS NULL` diterapkan.

### 3.5 Modifiers

- Primary key: `modifiers.id BIGINT UNSIGNED`.
- Relasi grup: `modifiers_group_id BIGINT UNSIGNED NOT NULL`, bukan `group_id`.
- Harga: `harga BIGINT SIGNED NOT NULL`, bukan `price`.
- Stok: `stok BIGINT SIGNED NULL`.
- Tabel memakai soft delete melalui `deleted_at`.

Relasi aktual:

```sql
modifiers.modifiers_group_id = modifier_groups.id
```

### 3.6 Taxes

- Primary key: `taxes.id BIGINT UNSIGNED`.
- Outlet: `outlet_id BIGINT UNSIGNED NOT NULL`, tanpa FK dan tanpa index khusus.
- Persentase: `amount VARCHAR(255) NOT NULL`, bukan tipe numerik.
- Satuan: `satuan VARCHAR(255) NOT NULL`.
- Tabel memakai soft delete melalui `deleted_at`.

Data aktif yang diaudit memakai nilai seperti `amount = '10'` dan `satuan = '%'`. Pricing engine harus memvalidasi dan mengonversi `amount` secara eksplisit sebelum perhitungan. Jangan mengandalkan implicit MySQL cast.

## 4. Kontrak Pembayaran Aktual

### 4.1 Category Payments

Master yang ditemukan:

| ID | Nama |
|---:|---|
| 1 | Cash |
| 2 | EDC |
| 3 | Transfer |
| 4 | Grab - Food |
| 5 | Go - Food |
| 6 | Pesanan Online |

`category_payments.id` adalah `BIGINT UNSIGNED`. Nama tidak mempunyai unique constraint.

### 4.2 Payments

Master yang ditemukan:

| ID | Nama | `category_payment_id` |
|---:|---|---:|
| 1 | DEBIT | 2 |
| 2 | QRIS | 2 |
| 3 | BCA | 3 |
| 4 | Go-Food | 6 |
| 5 | Grab-Food | 6 |
| 6 | BSI | 3 |
| 7 | BNI | 3 |

Fakta penting:

- `payments.id = 2` adalah QRIS.
- QRIS saat ini dipetakan ke `category_payments.id = 2`, yaitu kategori EDC.
- Tidak ada record `payments` bernama Cash.
- `payments.category_payment_id` adalah `BIGINT UNSIGNED NOT NULL`, tetapi tidak mempunyai FK atau index.

Mapping awal yang disetujui secara teknis untuk implementasi:

- QRIS: `payment_id = 2`, `category_payment_id = 2`, `nama_tipe_pembayaran = 'QRIS'`, dan bridge mengisi `transactions.tipe_pembayaran = 2`.
- Bayar di kasir: kandidat konfigurasi adalah `payment_id = NULL`, `category_payment_id = 1`, dan `nama_tipe_pembayaran = 'Cash'`.

Mapping bayar di kasir tetap harus dikonfirmasi sebagai konfigurasi operasional sebelum production.

## 5. Kontrak Bridge Transactions

Bridge hanya boleh berjalan setelah order lunas dan harus dibungkus satu transaksi database.

Kolom aktual yang relevan:

| Kolom | Tipe aktual | Nullable | Catatan |
|---|---|---:|---|
| `outlet_id` | `BIGINT UNSIGNED` | Tidak | FK ke `outlets.id` |
| `user_id` | `BIGINT UNSIGNED` | Tidak | FK ke `users.id` |
| `patty_cash_id` | `BIGINT UNSIGNED` | Tidak | Nama aktual memakai `patty`, wajib diisi |
| `shift_session_id` | `BIGINT UNSIGNED` | Ya | Tanpa FK |
| `category_payment_id` | `BIGINT UNSIGNED` | Ya | FK ke `category_payments.id` |
| `tipe_pembayaran` | `BIGINT UNSIGNED` | Ya | FK ke `payments.id` |
| `nama_tipe_pembayaran` | `VARCHAR(255)` | Ya | Label pembayaran |
| `reference_id` | `VARCHAR(100)` | Ya | Index biasa, bukan unique |
| `total` | `BIGINT SIGNED` | Ya | Total rupiah |
| `total_pajak` | JSON/`LONGTEXT` | Ya | Bukan scalar nominal |
| `total_modifier` | `BIGINT SIGNED` | Ya | Total rupiah |
| `total_diskon` | `BIGINT SIGNED` | Ya | Total rupiah |
| `rounding_amount` | `BIGINT SIGNED` | Ya | Gunakan bila diperlukan |
| `created_at` | `TIMESTAMP` | Ya | Timestamp transaksi |

### 5.1 Format Total Pajak

`transactions.total_pajak` harus menerima JSON breakdown yang kompatibel dengan POS, bukan angka `tax_total` langsung. Contoh format aktual:

```json
[
  {
    "id": 8,
    "name": "Pajak Restoran",
    "amount": 10,
    "satuan": "%",
    "total": 2700
  }
]
```

Sumber data dapat berasal dari snapshot `ot_orders.tax_breakdown`. `ot_orders.tax_total` tetap disimpan sebagai `BIGINT` integer rupiah.

### 5.2 Idempotency Bridge

`transactions.reference_id` mempunyai index non-unique. Database tidak menjamin uniqueness.

Bridge wajib:

1. Menggunakan `ot_orders.order_no` sebagai `reference_id`.
2. Mengecek `pos_bridge_status` dan mencari `transactions.reference_id` sebelum insert.
3. Melakukan check, insert transaction, insert item, dan update `ot_orders` dalam satu transaksi database.
4. Mengunci baris order saat bridge agar dua worker tidak memproses order yang sama.
5. Tidak menandai `pos_bridge_status = 'linked'` sebelum semua operasi sukses.

Jangan menambah unique constraint ke `transactions.reference_id` dalam pekerjaan Order Table karena perubahan tabel POS yang disetujui hanya geolocation `outlets`.

### 5.3 Petty Cash dan Shift

- Nama kolom transaksi adalah `patty_cash_id`, bukan `petty_cash_id`.
- Kolom tersebut `NOT NULL`; audit data tidak menemukan transaksi dengan nilai null.
- `transactions.shift_session_id` nullable dan tidak mempunyai FK.
- `petty_cashes.outlet_id` bertipe `VARCHAR(255)`, bukan `BIGINT`.
- `petty_cashes.amount_awal` dan `amount_akhir` bertipe string.
- `shift_sessions.petty_cash_id` memakai ejaan `petty`, berbeda dengan `transactions.patty_cash_id`.

Jangan mengaktifkan bridge sebelum strategi petty cash pada bagian 8 diputuskan.

## 6. Kontrak Bridge Transaction Items

Kolom aktual yang relevan:

| Kolom | Tipe aktual | Nullable | Catatan |
|---|---|---:|---|
| `transaction_id` | `BIGINT UNSIGNED` | Tidak | FK ke `transactions.id` |
| `product_id` | `BIGINT UNSIGNED` | Ya | FK ke `products.id` |
| `variant_id` | `BIGINT UNSIGNED` | Ya | FK ke `variant_products.id` |
| `harga` | `BIGINT UNSIGNED` | Ya | Harga rupiah |
| `modifier_id` | JSON/`LONGTEXT` | Ya | Array snapshot, bukan scalar ID |
| `catatan` | `VARCHAR(255)` | Ya | Batasi panjang sebelum insert |
| `item_open_bill_id` | `BIGINT UNSIGNED` | Ya | Jangan dibaca atau diisi oleh Order Table |

### 6.1 Format Modifier

`transaction_items.modifier_id` harus menerima JSON array yang kompatibel dengan POS. Contoh format aktual:

```json
[
  {
    "id": "164",
    "nama": "Cup Ice",
    "harga": 0
  },
  {
    "id": "237",
    "nama": "Ice",
    "harga": 0
  }
]
```

Bangun array tersebut dari snapshot `ot_order_item_modifiers`. Jangan menulis satu scalar `modifier_id`.

### 6.2 Kuantitas Item

`transaction_items` tidak mempunyai kolom `qty` atau `quantity`. Keputusan teknis yang disetujui adalah mengekspansi satu `ot_order_items` menjadi satu baris `transaction_items` per unit.

Contoh: bila `qty = 3`, bridge menulis tiga baris POS dengan `product_id`, `variant_id`, `harga`, modifier JSON, dan catatan yang sama.

### 6.3 Larangan Domain Kasir

- Jangan membaca atau menulis tabel `open_bills` dan `item_open_bills`.
- Jangan memakai kolom yang menghubungkan bridge ke domain tersebut.
- Order Table selalu menggunakan jalur transaksi langsung melalui `transactions` dan `transaction_items`.

## 7. User Sistem Bridge

User `order-table@system` belum ditemukan pada `users.email` maupun `users.username`.

Tabel `users` mensyaratkan lebih dari email:

- `name NOT NULL`
- `username NOT NULL`
- `status INT NOT NULL`
- `role INT NOT NULL`
- `password NOT NULL`
- `outlet_id` berupa JSON/`LONGTEXT NOT NULL`

Tidak ada unique index pada `email` atau `username`. Pembuatan user sistem harus dilakukan oleh backoffice setelah nilai role, status, password, username, dan cakupan outlet disetujui. Jangan membuat user ini otomatis dari aplikasi Order Table.

## 8. Keputusan yang Masih Terbuka

Hal berikut belum boleh diasumsikan untuk production:

1. Strategi `transactions.patty_cash_id`:
   - petty cash virtual Order Table per outlet;
   - petty cash aktif milik kasir;
   - atau mekanisme lain dari tim keuangan.
2. Merchant/rekening QRIS dinamis sama atau terpisah dari QRIS EDC.
3. Mapping final bayar di kasir, termasuk nilai `payment_id`, `category_payment_id`, dan `nama_tipe_pembayaran`.
4. Nilai lengkap user sistem `order-table@system`.
5. Kontrak autentikasi endpoint settle dari aplikasi kasir.

Kode boleh menyiapkan adapter atau konfigurasi untuk keputusan tersebut, tetapi tidak boleh mengaktifkan default production tanpa keputusan tertulis.

## 9. Ekspektasi untuk Migration Laravel

Setelah review ini disetujui untuk tahap migration, repository Laravel akan membuat tepat 13 migration:

1. `create_ot_dining_tables_table`
2. `create_ot_table_sessions_table`
3. `create_ot_vouchers_table`
4. `create_ot_orders_table`
5. `create_ot_order_items_table`
6. `create_ot_order_item_modifiers_table`
7. `create_ot_order_payments_table`
8. `create_ot_order_status_logs_table`
9. `create_ot_voucher_redemptions_table`
10. `create_ot_banners_table`
11. `create_ot_outlet_settings_table`
12. `create_ot_payment_methods_table`
13. `add_geolocation_to_outlets_table`

Ketentuan:

- Tidak membuat `ot_session_devices`.
- Tidak mengubah tabel POS selain tiga kolom geo pada `outlets`.
- Tidak membuat FK dari tabel `ot_*` ke tabel POS pada tahap awal; gunakan index.
- FK internal antar-tabel `ot_*` boleh dibuat sesuai urutan dependensi dan kebijakan migration Laravel.
- Nominal uang tabel `ot_*` menggunakan `BIGINT`.
- Seluruh `down()` harus mengembalikan kondisi sebelum migration.
- Migration hanya boleh diuji pada database development setelah persetujuan eksplisit.

## 10. Checklist Agent Order Table

- [ ] Gunakan `products.photo`, bukan `products.image`.
- [ ] Gunakan `variant_products.harga` dan `variant_products.stok`.
- [ ] Gunakan `modifiers.modifiers_group_id` dan `modifiers.harga`.
- [ ] Gunakan `modifier_groups.is_required`; jangan mengakses kolom `type` atau `required` yang tidak ada.
- [ ] Query `modifier_groups.product_id` sebagai JSON.
- [ ] Jangan memakai `categories.sort_order` atau `products.sort_order`.
- [ ] Filter `status` dan `deleted_at` pada katalog yang mendukung soft delete.
- [ ] Parse `taxes.amount` dari string dengan validasi eksplisit.
- [ ] Tulis `transactions.total_pajak` sebagai JSON breakdown.
- [ ] Tulis `transaction_items.modifier_id` sebagai JSON array snapshot.
- [ ] Ekspansi kuantitas menjadi satu baris `transaction_items` per unit.
- [ ] Batasi `transaction_items.catatan` maksimal 255 karakter.
- [ ] Gunakan nama aktual `transactions.patty_cash_id`.
- [ ] Jangan mengandalkan unique constraint pada `transactions.reference_id`.
- [ ] Jangan membaca atau menulis `open_bills` dan `item_open_bills`.
- [ ] Jangan membuat migration dari repository Order Table.
- [ ] Jangan mengaktifkan bridge sebelum petty cash dan user sistem diputuskan.
