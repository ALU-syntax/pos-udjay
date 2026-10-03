# Handoff Migrasi Database — Order Table untuk Backoffice Laravel

Dokumen ini adalah instruksi kerja untuk agent/developer yang mengerjakan **repository Laravel existing (backoffice POS)**. Semua perubahan skema database untuk fitur Order Table dibuat **di repository Laravel**, bukan di `apps/backend` Node.

Acuan domain tetap [`docs/system-design.md`](system-design.md). Jika ada perbedaan antara dokumen ini dan `system-design.md`, hentikan implementasi dan selaraskan dulu.

> **Status dokumen:** keputusan desain sudah final untuk versi ini. Item yang masih terbuka hanya petty cash bridge dan merchant QRIS (§9).

---

## 0. Aturan Wajib (baca dulu)

1. **Satu pemilik skema: Laravel.** Backend Node (`apps/backend`) tidak pernah membuat/mengubah tabel. Node hanya membaca katalog POS dan menulis hasil bridge.
2. **Prefix `ot_`** untuk semua tabel baru milik Order Table. Jangan mencampur dengan tabel POS.
3. **Jangan menyentuh `open_bills` dan `item_open_bills`.** Itu domain kasir (open bill karyawan). Order Table dilarang baca/tulis.
4. **Jangan mengubah kolom tabel POS** selain penambahan geolocation di `outlets` (§3.13) yang sudah disetujui.
5. Semua nominal uang = **BIGINT integer rupiah**. Tidak ada `DECIMAL`/`FLOAT` untuk uang.
6. Semua migration harus **reversible** (`down()` benar-benar mengembalikan kondisi awal).
7. Jangan menambah **foreign key constraint** ke tabel POS existing tanpa memastikan engine tabel mendukung (InnoDB) dan tidak memblokir. Bila ragu, cukup tambahkan `INDEX`, bukan FK.
8. `ot_outlet_settings`, `ot_payment_methods`, dan kolom geo di `outlets` **wajib ada** sebelum endpoint order bisa berjalan.

### 0.1 Keputusan desain final (ringkas)

| Topik | Keputusan |
|---|---|
| Model sesi | Sesi mengikuti **device** (1 device : 1 meja, tidak digabung) |
| Sesi baru | Dibuat setelah sesi sebelumnya `closed` atau hari berikutnya |
| `ot_session_devices` | **Tidak dipakai** (menunggu fitur Group Order) |
| Status setelah bayar | `placed` → `received` (bukan `confirmed`) |
| `completed` | **Dihapus** — `served` adalah status terminal sukses |
| `ready` | **Dihapus** dari `ot_orders` dan `ot_order_items` |
| Auto `received` → `preparing` | Dijalankan **Node worker**, delay default **30 detik**, configurable |
| QRIS expiry | Default **15 menit**, configurable via `ot_payment_methods` |
| Kasir payment due | Default **60 menit**, configurable via `ot_payment_methods` |
| Worker expiry | Interval scan **1 menit** |
| Penutupan sesi | EOD (default `23:59`, configurable) atau kasir menutup |
| Blokir order baru | Device-level selama ada order non-terminal |
| Pindah meja | Order lama belum lunas tetap memblokir order baru |
| `served` | Ditandai **manual** oleh dapur/kasir |
| Voucher | Guard berlapis; batas utama `quota_used < quota_total` |
| Config pembayaran | Tabel baru `ot_payment_methods`, terhubung ke `payments` + `category_payments` |

---

## 1. Konsep Arsitektur Tabel

### 1.1 Mengapa tabel baru dipisah dengan prefix `ot_`

- **Isolasi domain.** Order Table adalah aplikasi baru di atas database POS yang sudah production. Memisahkan nama tabel membuat perubahan Order Table tidak pernah menyentuh data kasir.
- **Auditability.** `SELECT ... WHERE table_name LIKE 'ot\_%'` langsung memisahkan seluruh jejak Order Table di laporan/backup.
- **Rollback aman.** Bila fitur dibatalkan, semua tabel `ot_*` bisa di-drop tanpa menyentuh domain POS.
- **Ownership jelas.** Laravel (backoffice) adalah pemilik migrasi; Node hanya konsumen.

### 1.2 Mengapa Order Table TIDAK memakai `open_bills`

| Fakta | Angka | Keputusan |
|---|---|---|
| Transaksi via `transactions` langsung | 547.220 (99,87%) | **Jalur yang kita ikuti** |
| Transaksi via `open_bills` | 731 (0,13%) | Domain kasir — **tidak disentuh** |

`open_bills` adalah fitur kasir untuk membuka bill (mis. karyawan) sebelum dibayar. Order Table menyelesaikan pembayaran **sebelum** bridge, sehingga selalu menulis langsung ke `transactions` + `transaction_items` — persis seperti 99,87% transaksi normal POS.

### 1.3 Mengapa data di-`snapshot` (bukan hanya FK)

`ot_order_items` menyimpan `product_name`, `variant_name`, `unit_price`; `ot_order_item_modifiers` menyimpan `name` dan `harga`. Alasannya:

- Harga/nama produk di POS bisa berubah kapan saja. Order yang sudah dibuat harus tetap menampilkan nilai **saat transaksi terjadi**.
- Menjaga integritas histori & audit meski katalog POS berubah.
- Menghindari query join berat ke katalog saat menampilkan riwayat.

### 1.4 Mengapa `ot_orders` menyimpan `outlet_id` dan `table_id` yang denormalisasi

`ot_table_sessions` sudah punya `table_id` dan `outlet_id`. Namun `ot_orders` menyimpannya ulang agar:

- Query laporan per outlet tidak perlu join ke sesi.
- Tetap benar walau sesi sudah `closed` dan relasinya diarsipkan.
- Mempercepat filter `INDEX(outlet_id, status)`.
- Mendukung kasus pindah meja: sesi milik device, tetapi `ot_orders.table_id` mencatat meja **saat order dibuat**.

### 1.5 Model sesi: device-based (bukan meja)

Satu sesi merepresentasikan **satu kunjungan device** (bukan satu periode meja terisi):

- **1 device : 1 meja.** Dua HP yang scan meja yang sama mendapat sesi terpisah. Kita **tidak** menggabungkan device ke satu bill karena tidak bisa dipastikan mereka satu grup. Fitur Group Order akan dibahas terpisah di masa depan.
- **Pindah meja tetap terdeteksi.** Guard voucher dan guard blokir order memakai `device_id`, sehingga customer yang pindah meja tidak bisa mereset kuota/guard dengan scan ulang.
- **Multi-order dalam satu sesi.** Customer boleh memesan lagi di sesi yang sama selama **tidak ada order non-terminal** (lihat §5.4).
- **Privasi riwayat.** Riwayat di-scope ke `device_id` lintas sesi, bukan terikat sesi yang masih terbuka.
- **Sesi ditutup** saat EOD (default `23:59`) atau kasir menutup. Sesi **tidak** ditutup saat bill lunas, agar customer bisa memesan lagi.
- `qr_token` di `ot_dining_tables` bisa di-rotate/dinonaktifkan tanpa mengubah identitas meja.

### 1.6 Mengapa `served` adalah terminal (tanpa `completed` dan `ready`)

- Operasional sudah dibrief: begitu pesanan selesai dibuat, segera diantar ke customer. Tidak ada tahap perantara "Siap" yang perlu ditunggu.
- Timeline customer hanya 3 tahap: **Diterima → Disiapkan → Disajikan**.
- `served` menandakan pesanan tuntas. Tidak diperlukan status `completed` terpisah.
- Karena dapur tidak menyiapkan apa pun sebelum dibayar, `served` praktis selalu berarti **sudah dibayar**.

### 1.7 Peta relasi (ERD ringkas)

```
outlets (POS)
  ├── ot_dining_tables ──< ot_table_sessions (device_id) ──< ot_orders ──< ot_order_items ──< ot_order_item_modifiers
  │                                                                │
  │                                                                ├──< ot_order_payments
  │                                                                ├──< ot_order_status_logs
  │                                                                └──< ot_voucher_redemptions >── ot_vouchers
  ├── ot_outlet_settings (1:1)
  ├── ot_payment_methods (per outlet) >── payments / category_payments (POS)
  └── ot_banners

products / variant_products / modifiers / taxes (POS)  ← dibaca, tidak diubah
transactions / transaction_items (POS)                  ← ditulis saat bridge
```

---

## 2. Daftar Migration yang Harus Dibuat

Buat migration berikut **di repository Laravel**, urut sesuai dependensi. Nama file mengikuti timestamp Laravel.

| # | Nama migration | Tabel |
|---|---|---|
| 1 | `create_ot_dining_tables_table` | `ot_dining_tables` |
| 2 | `create_ot_table_sessions_table` | `ot_table_sessions` (device-based) |
| 3 | `create_ot_vouchers_table` | `ot_vouchers` |
| 4 | `create_ot_orders_table` | `ot_orders` |
| 5 | `create_ot_order_items_table` | `ot_order_items` |
| 6 | `create_ot_order_item_modifiers_table` | `ot_order_item_modifiers` |
| 7 | `create_ot_order_payments_table` | `ot_order_payments` |
| 8 | `create_ot_order_status_logs_table` | `ot_order_status_logs` |
| 9 | `create_ot_voucher_redemptions_table` | `ot_voucher_redemptions` |
| 10 | `create_ot_banners_table` | `ot_banners` |
| 11 | `create_ot_outlet_settings_table` | `ot_outlet_settings` |
| 12 | `create_ot_payment_methods_table` | `ot_payment_methods` |
| 13 | `add_geolocation_to_outlets_table` | **ALTER** tabel POS `outlets` |

> **Catatan urutan:** `ot_vouchers` (3) dibuat sebelum `ot_orders` (4) agar FK `ot_orders.voucher_id` valid. `ot_table_sessions` (2) sebelum `ot_orders` (4). `ot_orders` sebelum item/payment/log/redemption. `ot_session_devices` **tidak dibuat**.

---

## 3. Spesifikasi Kolom Tiap Tabel

Semua tabel memakai `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`, `timestamps()` sesuai kebutuhan, dan charset mengikuti database existing.

### 3.1 `ot_dining_tables`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| outlet_id | BIGINT UNSIGNED | index, ref `outlets.id` |
| code | VARCHAR(50) | kode meja, mis. `M-14` |
| name | VARCHAR(100) | nama tampilan |
| qr_token | VARCHAR(64) | **UNIQUE**, opaque, rotatable |
| qr_active | TINYINT(1) | default 1 |
| capacity | INT NULL | opsional |
| timestamps | | |
| deleted_at | TIMESTAMP NULL | soft delete |

Index: `UNIQUE(qr_token)`, `INDEX(outlet_id)`.

### 3.2 `ot_table_sessions` (device-based)

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| device_id | CHAR(36) | UUID dari PWA |
| table_id | BIGINT UNSIGNED | meja saat sesi dibuka |
| outlet_id | BIGINT UNSIGNED | denormalisasi |
| status | ENUM('open','closed') | default `open` |
| opened_at | TIMESTAMP | |
| closed_at | TIMESTAMP NULL | |
| close_reason | ENUM('eod','kasir','manual') NULL | |
| closed_by | ENUM('system','kasir') NULL | |
| timestamps | | |

Index: `INDEX(device_id, status)`, `INDEX(table_id, status)`, `INDEX(outlet_id, status)`.

> **Aturan aplikasi (bukan constraint):** satu device boleh punya **satu sesi `open` per kombinasi (device_id, table_id)**. Sesi baru dibuat hanya setelah sesi sebelumnya `closed` atau pada hari berikutnya. Karena kasus pindah meja diperbolehkan, **jangan** memasang unique constraint pada `(device_id, status)`.

### 3.3 `ot_vouchers`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| outlet_id | BIGINT UNSIGNED NULL | NULL = global |
| code | VARCHAR(50) | **UNIQUE** |
| type | ENUM('percent','fixed') | |
| value | BIGINT | persen atau rupiah |
| min_spend | BIGINT | default 0 |
| max_discount | BIGINT NULL | cap untuk percent |
| quota_total | INT NULL | NULL = tak terbatas |
| quota_per_device | INT NULL | |
| quota_per_session | INT NULL | |
| quota_used | INT | default 0 |
| valid_from | TIMESTAMP NULL | |
| valid_to | TIMESTAMP NULL | |
| scope | ENUM('all','category','product') | default `all` |
| scope_ids | JSON NULL | |
| status | TINYINT(1) | default 1 |
| timestamps | | |

### 3.4 `ot_orders`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| order_no | VARCHAR(30) | **UNIQUE**, nomor publik |
| outlet_id | BIGINT UNSIGNED | |
| session_id | BIGINT UNSIGNED | ref `ot_table_sessions.id` |
| device_id | CHAR(36) | pembuat order |
| table_id | BIGINT UNSIGNED | meja **saat order dibuat** |
| status | ENUM('draft','placed','received','preparing','served','cancelled','expired') | |
| payment_mode | ENUM('qris','pay_at_cashier') | |
| payment_status | ENUM('unpaid','pending','paid','failed','expired','refunded') | |
| subtotal | BIGINT | |
| discount_item_total | BIGINT | default 0 |
| voucher_discount | BIGINT | default 0 |
| modifier_total | BIGINT | default 0 |
| tax_total | BIGINT | |
| tax_breakdown | JSON NULL | detail per pajak |
| rounding | BIGINT | default 0 |
| grand_total | BIGINT | |
| voucher_id | BIGINT UNSIGNED NULL | ref `ot_vouchers.id` |
| notes | TEXT NULL | |
| lat | DECIMAL(10,7) NULL | audit geofence |
| lng | DECIMAL(10,7) NULL | audit geofence |
| accuracy | VARCHAR(20) NULL | audit geofence |
| geofence_flag | ENUM('inside','outside','unknown') | |
| pos_bridge_status | ENUM('pending','linked','failed') | default `pending` |
| pos_transaction_id | BIGINT UNSIGNED NULL | id di `transactions` |
| placed_at | TIMESTAMP NULL | |
| payment_due_at | TIMESTAMP NULL | `placed_at` + `payment_due_minutes` (kasir) |
| paid_at | TIMESTAMP NULL | |
| timestamps | | |

Index: `INDEX(device_id, status)`, `INDEX(session_id)`, `INDEX(outlet_id, status)`, `INDEX(payment_status)`, `INDEX(pos_bridge_status)`.

> **Catatan `payment_due_at`:** diisi saat order `placed` dengan mode `pay_at_cashier` (default +60 menit). Untuk QRIS, deadline ada di `ot_order_payments.expires_at`.

### 3.5 `ot_order_items`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| order_id | BIGINT UNSIGNED | ref `ot_orders.id` |
| product_id | BIGINT UNSIGNED | ref `products.id` (POS) |
| variant_id | BIGINT UNSIGNED NULL | ref `variant_products.id` (POS) |
| product_name | VARCHAR(255) | snapshot |
| variant_name | VARCHAR(255) NULL | snapshot |
| unit_price | BIGINT | harga saat order |
| qty | INT | |
| modifier_total | BIGINT | total modifier per unit |
| line_total | BIGINT | |
| exclude_tax | TINYINT(1) | snapshot dari product |
| notes | TEXT NULL | |
| status | ENUM('pending','preparing','served','cancelled') | |

### 3.6 `ot_order_item_modifiers`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| order_item_id | BIGINT UNSIGNED | ref `ot_order_items.id` |
| modifier_id | BIGINT UNSIGNED | ref `modifiers.id` (POS) |
| name | VARCHAR(255) | snapshot |
| harga | BIGINT | snapshot |
| qty | INT | default 1 |

### 3.7 `ot_order_payments`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| order_id | BIGINT UNSIGNED | ref `ot_orders.id` |
| method | ENUM('qris','cashier','gateway') | |
| gateway_ref | VARCHAR(100) NULL | id dari gateway |
| amount | BIGINT | |
| status | ENUM('pending','paid','failed','expired') | |
| qr_string | TEXT NULL | payload QR |
| expires_at | TIMESTAMP NULL | QRIS: `created_at` + `qris_expiry_minutes` |
| paid_at | TIMESTAMP NULL | |
| raw_callback | JSON NULL | audit webhook |

Index: `INDEX(order_id)`, `UNIQUE(gateway_ref)` (bila `gateway_ref` tidak null) untuk idempotency webhook.

### 3.8 `ot_order_status_logs`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| order_id | BIGINT UNSIGNED | |
| from_status | VARCHAR(30) NULL | |
| to_status | VARCHAR(30) | |
| actor | ENUM('system','kasir','customer') | |
| note | VARCHAR(255) NULL | |
| created_at | TIMESTAMP | |

### 3.9 `ot_voucher_redemptions`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| voucher_id | BIGINT UNSIGNED | |
| order_id | BIGINT UNSIGNED | |
| session_id | BIGINT UNSIGNED | guard kuota per sesi |
| device_id | CHAR(36) | guard kuota per device |
| discount_amount | BIGINT | |
| ip_address | VARCHAR(45) NULL | audit |
| user_agent | VARCHAR(255) NULL | audit |
| created_at | TIMESTAMP | |

Index: `UNIQUE(voucher_id, order_id)`, `UNIQUE(voucher_id, device_id)`, `UNIQUE(voucher_id, session_id)`, `INDEX(device_id)`.

### 3.10 `ot_banners`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| outlet_id | BIGINT UNSIGNED NULL | NULL = global |
| title | VARCHAR(255) | |
| image_url | VARCHAR(500) | |
| action_type | ENUM('url','internal','product','category','voucher','promo') | |
| action_value | VARCHAR(500) NULL | |
| position | VARCHAR(50) | mis. `home_top` |
| sort_order | INT | default 0 |
| start_at | TIMESTAMP NULL | |
| end_at | TIMESTAMP NULL | |
| status | TINYINT(1) | default 1 |
| timestamps | | |

### 3.11 `ot_outlet_settings`

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| outlet_id | BIGINT UNSIGNED | **UNIQUE** |
| order_enabled | TINYINT(1) | master switch |
| stock_mode | ENUM('off','status_only','strict') | default `status_only` |
| open_time | TIME NULL | |
| close_time | TIME NULL | |
| forced_close | TINYINT(1) | pause darurat |
| service_fee_pct | DECIMAL(5,2) NULL | opsional |
| auto_preparing_delay_seconds | INT | default **30** (auto `received` → `preparing`) |
| session_close_time | TIME | default **23:59** (EOD auto-close sesi) |
| timestamps | | |

### 3.12 `ot_payment_methods` (baru)

Konfigurasi metode pembayaran per outlet. Menjembatani Order Table dengan master pembayaran POS.

| Kolom | Tipe | Aturan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| outlet_id | BIGINT UNSIGNED NULL | NULL = global default semua outlet |
| payment_id | BIGINT UNSIGNED NULL | ref `payments.id` (POS) — **verifikasi nama tabel/kolom** |
| category_payment_id | BIGINT UNSIGNED NULL | ref `category_payments.id` (POS) — dipakai bridge |
| code | VARCHAR(30) | `qris` / `pay_at_cashier` |
| label | VARCHAR(100) | nama tampilan, mis. "Bayar Sekarang (QRIS)" |
| nama_tipe_pembayaran | VARCHAR(50) NULL | nilai untuk kolom bridge di `transactions` |
| qris_expiry_minutes | INT NULL | default **15** (khusus QRIS) |
| payment_due_minutes | INT NULL | default **60** (khusus kasir) |
| enabled | TINYINT(1) | default 1 |
| sort_order | INT | default 0 |
| timestamps | | |

Index: `INDEX(outlet_id)`, `INDEX(payment_id)`, `UNIQUE(outlet_id, code)`.

> **Verifikasi ke skema existing:** nama tabel `payments` dan `category_payments`, serta kolom `nama_tipe_pembayaran` pada `transactions`, harus dikonfirmasi langsung ke database POS sebelum implementasi.

### 3.13 Perubahan tabel POS `outlets`

```sql
ALTER TABLE outlets
  ADD COLUMN latitude  DECIMAL(10,7) NULL,
  ADD COLUMN longitude DECIMAL(10,7) NULL,
  ADD COLUMN geofence_radius_m INT DEFAULT 150;
```

> **Kritis:** perubahan ini menyentuh tabel POS dan dapat memengaruhi aplikasi POS/Android. Wajib direview pemilik POS sebelum dijalankan di production. Pastikan migration `down()` menghapus ketiga kolom.

---

## 4. Matriks Akses Tabel (read-only vs write)

| Tabel | Order Table (Node) | Keterangan |
|---|---|---|
| `outlets` | **READ** | identitas + geo (kolom baru) |
| `categories` | **READ** | kategori menu |
| `products` | **READ** | nama, deskripsi, foto, `exclude_tax`, status |
| `variant_products` | **READ** | harga jual & stok (produk tak punya harga) |
| `modifier_groups` | **READ** | grup modifier (`product_id` JSON) |
| `modifiers` | **READ** | item modifier + harga |
| `taxes` | **READ** | pajak per outlet (PB1 10%) |
| `category_payments` | **READ** | mapping metode bayar untuk bridge |
| `payments` | **READ** | master metode pembayaran |
| `petty_cashes` / `shift_sessions` | **READ** | penentuan petty cash bridge (open item) |
| `users` | **READ** | validasi bridge user sistem |
| `transactions` | **WRITE (bridge saja)** | satu INSERT per order lunas |
| `transaction_items` | **WRITE (bridge saja)** | INSERT per order item |
| `open_bills` | **DILARANG** | domain kasir |
| `item_open_bills` | **DILARANG** | domain kasir |
| `ot_*` | **READ/WRITE** | milik Order Table |

Rekomendasi akun DB backend (least privilege), lihat §7.

---

## 5. Flow Query

### 5.1 Resolve QR → buka/join sesi device

```sql
-- 1. Resolve meja dari token
SELECT dt.id, dt.outlet_id, dt.code, dt.qr_active, o.name AS outlet_name,
       os.order_enabled, os.open_time, os.close_time, os.forced_close,
       o.latitude, o.longitude, o.geofence_radius_m
FROM ot_dining_tables dt
JOIN outlets o                   ON o.id = dt.outlet_id
LEFT JOIN ot_outlet_settings os  ON os.outlet_id = dt.outlet_id
WHERE dt.qr_token = :qrToken AND dt.deleted_at IS NULL;

-- 2. Cari sesi OPEN milik device ini pada meja ini
SELECT id FROM ot_table_sessions
WHERE device_id = :deviceId AND table_id = :tableId AND status = 'open'
ORDER BY id DESC LIMIT 1;

-- 3. Jika tidak ada, buat sesi baru (device-based)
INSERT INTO ot_table_sessions (device_id, table_id, outlet_id, status, opened_at)
VALUES (:deviceId, :tableId, :outletId, 'open', NOW());

-- 4. Update last activity sesi (opsional, untuk audit)
```

> Pindah meja = device yang sama membuka sesi baru pada `table_id` berbeda. Guard blokir & voucher tetap mengikuti `device_id`.

### 5.2 Ambil katalog menu (read-only, lintas tabel POS)

```sql
SELECT c.id AS category_id, c.name AS category_name,
       p.id AS product_id, p.name AS product_name, p.description, p.image,
       p.exclude_tax,
       vp.id AS variant_id, vp.name AS variant_name, vp.price, vp.stock
FROM products p
JOIN categories c        ON c.id = p.category_id
JOIN variant_products vp ON vp.product_id = p.id
WHERE p.status = 1 AND c.status = 1
ORDER BY c.sort_order, p.sort_order;

-- Modifier per produk
SELECT mg.id AS group_id, mg.name AS group_name, mg.type, mg.required,
       m.id AS modifier_id, m.name, m.price
FROM modifier_groups mg
JOIN modifiers m ON m.group_id = mg.id
WHERE mg.product_id = :productId;
```

> Harga otoritatif diambil dari `variant_products.price`, bukan dari request frontend.

### 5.3 Metode pembayaran & konfigurasi deadline

```sql
SELECT code, label, category_payment_id, nama_tipe_pembayaran,
       qris_expiry_minutes, payment_due_minutes
FROM ot_payment_methods
WHERE enabled = 1
  AND (outlet_id IS NULL OR outlet_id = :outletId)
ORDER BY sort_order;
```

> `qris_expiry_minutes` (default 15) dipakai saat membuat `ot_order_payments.expires_at`. `payment_due_minutes` (default 60) dipakai saat mengisi `ot_orders.payment_due_at`.

### 5.4 Guard blokir order baru (device-level)

```sql
-- Order dianggap non-terminal bila status bukan served/cancelled/expired
SELECT COUNT(*) AS blocking_orders
FROM ot_orders
WHERE device_id = :deviceId
  AND status NOT IN ('served','cancelled','expired');
-- bila > 0 → TOLAK pembuatan order baru, arahkan customer menyelesaikan/membayar order sebelumnya
```

> `draft` **tidak** memblokir. Guard memakai `device_id`, sehingga tetap berlaku walau customer pindah meja.

### 5.5 Validasi voucher (authoritative di backend)

```sql
SELECT * FROM ot_vouchers
WHERE code = :code AND status = 1
  AND (outlet_id IS NULL OR outlet_id = :outletId)
  AND (valid_from IS NULL OR valid_from <= NOW())
  AND (valid_to   IS NULL OR valid_to   >= NOW());
```

Guard berlapis saat redeem (dalam satu transaksi DB):

1. `SELECT ... FOR UPDATE` baris voucher; pastikan `quota_used < quota_total` (atau `quota_total IS NULL`).
2. Cek `UNIQUE(voucher_id, device_id)` dan `UNIQUE(voucher_id, session_id)` belum ada.
3. Cek kuota per device/per sesi bila dikonfigurasi.
4. `INSERT ot_voucher_redemptions` + `UPDATE ot_vouchers SET quota_used = quota_used + 1`.

> Batas kerugian utama tetap `quota_total`. Guard device/sesi bersifat best-effort (bisa dilewati dengan clear storage/incognito) dan tidak boleh menjadi satu-satunya proteksi.

### 5.6 Pricing (dihitung di backend, bukan frontend)

```
Langkah 1  subtotal       = Σ ((unit_price + modifier_total) × qty)
Langkah 2  discount_item  = total diskon per item (jika ada)
Langkah 3  voucher        = percent difus cap max_discount, atau fixed;
                            cek min_spend & scope
Langkah 4  tax_base       = subtotal − discount_item − voucher
                            (hanya baris non-exclude_tax)
Langkah 5  tax_total      = Σ (tax_base × taxes.amount / 100) per outlet
Langkah 6  rounding       = 0 (default OFF)
Langkah 7  grand_total    = subtotal − discount_item − voucher + tax_total ± rounding
```

### 5.7 Siklus status order

```
draft ──► placed ──(bayar)──► received ──(worker +30s)──► preparing ──► served
             │                                                        (terminal sukses)
             ├──(QRIS 15 mnt)────► expired
             ├──(kasir 60 mnt)───► expired
             └──(manual)─────────► cancelled
```

Setiap transisi menulis `ot_order_status_logs` (`from_status`, `to_status`, `actor`).

### 5.8 Node worker: expiry & auto-preparing (interval 1 menit)

```
Setiap 1 menit:
  1. QRIS expired
     UPDATE ot_order_payments SET status='expired'
       WHERE status='pending' AND expires_at <= NOW();
     → order terkait: status='expired', payment_status='expired'

  2. Kasir payment due lewat
     UPDATE ot_orders SET status='expired', payment_status='expired'
       WHERE payment_mode='pay_at_cashier' AND status='placed'
         AND payment_due_at <= NOW();

  3. Auto received → preparing
     UPDATE ot_orders SET status='preparing'
       WHERE status='received'
         AND paid_at <= NOW() - INTERVAL auto_preparing_delay_seconds SECOND;

  4. EOD close sesi
     UPDATE ot_table_sessions
       SET status='closed', closed_at=NOW(), close_reason='eod', closed_by='system'
       WHERE status='open' AND TIME(NOW()) >= session_close_time;
```

> `auto_preparing_delay_seconds` dan `session_close_time` dibaca dari `ot_outlet_settings` per outlet.

### 5.9 Bridge ke POS (satu transaksi DB)

Dijalankan saat order lunas (`payment_status = paid`). Wajib **idempotent** lewat `reference_id = order_no`.

```sql
BEGIN;

INSERT INTO transactions (
  outlet_id, user_id, patty_cash_id, shift_session_id,
  category_payment_id, nama_tipe_pembayaran,
  reference_id,                 -- = ot_orders.order_no (kunci idempotency)
  total, total_pajak, total_diskon, total_modifier, created_at
) VALUES (
  :outletId, :bridgeUserId, :pettyCashId, NULL,
  :categoryPaymentId, :paymentLabel,
  :orderNo,
  :grandTotal, :taxTotal, :discountTotal, :modifierTotal, NOW()
);

SET @posTxnId = LAST_INSERT_ID();

-- per ot_order_items
INSERT INTO transaction_items (
  transaction_id, product_id, variant_id, harga,
  modifier_id, catatan, item_open_bill_id
) VALUES (
  @posTxnId, :productId, :variantId, :unitPrice,
  :modifierId, :notes, NULL          -- WAJIB NULL
);

UPDATE ot_orders
SET pos_bridge_status = 'linked', pos_transaction_id = @posTxnId
WHERE id = :orderId;

COMMIT;
```

**Rollback:** bila salah satu INSERT gagal, `ROLLBACK` penuh; set `pos_bridge_status='failed'` untuk memicu re-bridge. Jangan pernah set `linked` sebelum COMMIT berhasil.

> Kolom `transactions` di atas adalah daftar minimal berdasarkan `docs/system-design.md` §5.3. **Nama kolom final harus diverifikasi langsung ke skema `transactions` existing** sebelum implementasi.

---

## 6. Idempotency & Konsistensi

| Titik | Kunci idempotency | Perilaku |
|---|---|---|
| Bridge POS | `transactions.reference_id = order_no` | Jika sudah ada, jangan insert ulang |
| Webhook payment | `ot_order_payments.gateway_ref` | Duplikat diabaikan |
| Voucher redemption | `UNIQUE(voucher_id, order_id)` | Cegah double redeem |
| Voucher (device) | `UNIQUE(voucher_id, device_id)` | Cegah redeem ulang oleh device sama |
| Voucher (sesi) | `UNIQUE(voucher_id, session_id)` | Cegah redeem ulang dalam sesi |
| Checkout | header `Idempotency-Key` (di Node) | Cegah double order |

Aturan transaksi:
- Voucher redemption + update order + bridge harus dalam **satu transaksi DB** yang saling terkait.
- Jangan menandai `pos_bridge_status='linked'` sebelum COMMIT sukses.
- Cek kuota voucher memakai `SELECT ... FOR UPDATE` agar tidak race condition.

---

## 7. Akun Database Backend (Least Privilege)

Sediakan akun MySQL khusus untuk `apps/backend` (contoh; sesuaikan host/db):

```sql
CREATE USER 'order_table_app'@'%' IDENTIFIED BY '<secret>';

-- Read-only untuk katalog POS
GRANT SELECT ON pos_db.outlets            TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.categories         TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.products           TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.variant_products   TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.modifier_groups    TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.modifiers          TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.taxes              TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.category_payments  TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.payments           TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.petty_cashes       TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.shift_sessions     TO 'order_table_app'@'%';
GRANT SELECT ON pos_db.users              TO 'order_table_app'@'%';

-- Write HANYA untuk bridge
GRANT INSERT ON pos_db.transactions        TO 'order_table_app'@'%';
GRANT INSERT ON pos_db.transaction_items   TO 'order_table_app'@'%';

-- Full access hanya ke tabel milik sendiri
GRANT SELECT, INSERT, UPDATE, DELETE ON pos_db.ot_% TO 'order_table_app'@'%';

-- TIDAK ada grant ke open_bills / item_open_bills
FLUSH PRIVILEGES;
```

> Laravel migration runner memakai akun terpisah (akun deploy/migrator), bukan akun aplikasi di atas.

---

## 8. Bridge User Sistem

Buat **satu** user sistem di `users` untuk semua transaksi Order Table (dibuat sekali oleh backoffice):

- Email/identifier: `order-table@system`
- Dipakai sebagai `transactions.user_id` pada semua baris bridge.
- Tujuan: memudahkan filter laporan "transaksi Order Table" dan membedakan dari transaksi kasir.

---

## 9. Keputusan Terbuka (jangan diasumsikan)

Item berikut **belum final** dan tidak boleh dijadikan default production tanpa keputusan tertulis:

| Keputusan | Pemilik | Dampak pada skema |
|---|---|---|
| Petty cash bridge (§5.2: opsi A/B/C) | Keuangan + backoffice | Nilai `transactions.patty_cash_id` |
| Merchant QRIS terpisah/sama | Keuangan | Konfigurasi pembayaran (di luar skema) |
| Kontrak auth aplikasi kasir | Mobile/POS + backend | Endpoint `settle` |

> `stock_mode`, jam operasional, QRIS expiry, payment due, auto-preparing delay, dan session close time **sudah final** (default di §3) dan tetap dapat diubah via `ot_outlet_settings` / `ot_payment_methods`.

---

## 10. Checklist Verifikasi Setelah Migration

- [ ] Semua 12 tabel `ot_*` dibuat dan `php artisan migrate:rollback` mengembalikan kondisi awal.
- [ ] Kolom `outlets.latitude/longitude/geofence_radius_m` ditambahkan dan bisa di-rollback.
- [ ] `UNIQUE(qr_token)`, `UNIQUE(order_no)`, `UNIQUE(outlet_id, code)` pada `ot_payment_methods` terpasang.
- [ ] `UNIQUE(voucher_id, order_id)`, `UNIQUE(voucher_id, device_id)`, `UNIQUE(voucher_id, session_id)` terpasang.
- [ ] Enum `ot_orders.status` = `draft, placed, received, preparing, served, cancelled, expired` (tanpa `completed`/`ready`).
- [ ] Enum `ot_order_items.status` = `pending, preparing, served, cancelled` (tanpa `ready`).
- [ ] Enum `ot_order_payments.status` mencakup `expired`.
- [ ] `ot_outlet_settings` punya `auto_preparing_delay_seconds` (default 30) dan `session_close_time` (default 23:59).
- [ ] `ot_payment_methods` terisi minimal `qris` dan `pay_at_cashier` untuk 7 outlet.
- [ ] Semua kolom uang bertipe BIGINT (bukan decimal/float).
- [ ] Tidak ada FK/constraint baru yang memblokir tabel POS.
- [ ] Akun DB aplikasi tidak punya akses ke `open_bills` dan `item_open_bills`.
- [ ] Bridge user `order-table@system` sudah dibuat.
- [ ] Koordinat + radius geofence 7 outlet terisi.

---

## 11. Prompt Siap-Tempel untuk Agent Laravel

> Kerjakan di repository Laravel existing (backoffice POS). Buat 13 migration sesuai daftar di `docs/laravel-migration-handoff.md` (12 tabel `ot_*` + ALTER `outlets`), **tanpa** `ot_session_devices`. Ikuti spesifikasi kolom, index, dan enum pada dokumen tersebut. Enum status final: `ot_orders.status` = `draft, placed, received, preparing, served, cancelled, expired`; `ot_order_items.status` = `pending, preparing, served, cancelled`; `ot_order_payments.status` mencakup `expired`. Semua kolom uang gunakan BIGINT integer rupiah. Jangan menambah foreign key constraint ke tabel POS existing; cukup index. Jangan menyentuh `open_bills` dan `item_open_bills`. Pastikan setiap migration punya `down()` yang benar. Jangan isi data production untuk petty cash, merchant QRIS, dan auth kasir sebelum ada keputusan tertulis. Setelah selesai, jalankan `php artisan migrate` di database development dan laporkan hasilnya.

---

*Dokumen ini melengkapi `docs/system-design.md` (§4 Skema Data, §5 Bridge). Bila skema POS existing berbeda dari asumsi di sini, verifikasi langsung ke database dan perbarui dokumen ini.*
