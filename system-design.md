# System Design — PWA Order di Meja (UD.Djaya)

**Versi:** 1.1
**Status:** Final — siap diserahkan ke tim
**Terakhir diperbarui:** 2025

**Riwayat revisi**
- **v1.1** — (a) Rollout menjadi **7 outlet** (volume tertinggi); (b) Bridge **menghindari `open_bills`** (milik kasir untuk open bill karyawan); (c) Tambah kebijakan **mode stok**, **jam operasional menu**, dan **strategi merchant QRIS**.
- **v1.0** — Dokumen awal.

---

## 1. Ringkasan Eksekutif

Aplikasi ini adalah **PWA (Progressive Web App)** yang memungkinkan customer coffee shop memesan langsung dari meja tanpa antri ke kasir. Customer memindai QR code di meja, menelusuri menu, menambahkan modifier/add-ons, memasukkan voucher, lalu membayar melalui QRIS **atau** memilih bayar di kasir.

Sistem dibangun **terpisah** dari backoffice POS (Laravel) yang sudah berjalan, namun **berbagi database MySQL yang sama**. Data order disimpan pada tabel ber-prefix `ot_`, sedangkan katalog (produk, varian, modifier, pajak) dibaca langsung dari tabel POS. Saat transaksi selesai, order publik di-bridge ke tabel transaksi POS agar laporan tetap menyatu.

**Cakupan rilis awal: 7 outlet volume tertinggi.**

| Outlet ID | Nama | Transaksi 30 hari |
|---|---|---|
| 11 | UD.Djaya Taman Kencana | 5.363 |
| 2 | UD.Djaya Malabar | 4.078 |
| 6 | UD.Djaya Jakal | 3.802 |
| 1 | UD.Djaya Puter | 3.031 |
| 5 | UD.Djaya RSCM | 2.072 |
| 7 | UD.Djaya Slamet Riyadi | 2.012 |
| 10 | Budiman Roastery | 1.909 |

Outlet lain diaktifkan kemudian melalui konfigurasi (lihat `ot_outlet_settings.order_enabled`).

### Prinsip Utama
1. **Satu database, domain terpisah** — tabel order ber-prefix `ot_`, tabel POS tetap utuh.
2. **Backoffice Laravel adalah pemilik skema** — semua migrasi (termasuk tabel `ot_`) dikelola Laravel.
3. **BFF Node.js adalah otak order publik** — pricing, sesi, voucher, payment, bridge.
4. **POS tetap sumber kebenaran katalog & omzet**.
5. **Realtime andal** — webhook (payment), SSE (PWA), FCM (kasir Android).
6. **Tidak menyentuh domain kasir** — `open_bills`/`item_open_bills` adalah milik kasir (open bill karyawan) dan tidak digunakan order-table.

---

## 2. Keputusan Arsitektur (ADR Ringkas)

| # | Topik | Keputusan | Alasan |
|---|-------|-----------|--------|
| 1 | Frontend | PWA (Vue 3) | Cross-platform, installable, tanpa app store |
| 2 | Backend order | **BFF Node.js (Express)** terpisah | Pemisahan domain order vs backoffice |
| 3 | Backoffice | Laravel (existing) | Sudah berjalan, pemilik skema & laporan |
| 4 | Database | **MySQL yang sama dengan POS** | Satu sumber data, laporan menyatu |
| 5 | Penamaan tabel order | Prefix **`ot_`** | Jelas terpisah di DB bersama |
| 6 | Kepemilikan migrasi | **Laravel** | Satu sumber migrasi, hindari konflik |
| 7 | Akses katalog | Node baca **langsung** tabel POS | Real-time, tanpa tabel cache |
| 8 | Bridge ke POS | Tulis ke **`transactions` + `transaction_items`** saja (tanpa `open_bills`) | `open_bills` milik kasir; atomic dalam satu transaksi DB |
| 9 | Realtime | Webhook + SSE + FCM | Andal saat app kasir tertutup |
| 10 | Identitas meja | Sesi **device-based** (1 device : 1 meja) + `device_id` | Pindah meja terdeteksi; privasi history lintas sesi |
| 11 | Cart | Hybrid local-first + server draft | Tahan refresh & lintas device |
| 12 | Geofence | Hybrid (blokir jika yakin jauh) | Anti-abuse tanpa memblokir pelanggan sah |
| 13 | Pajak | Dihitung dari subtotal **setelah voucher** | Sesuai kebijakan bisnis |
| 14 | Rounding | Ikut POS, **default OFF** + switch | Fleksibilitas |
| 15 | Rollout | **7 outlet volume tertinggi** (sisanya menyusul via konfigurasi) | Risiko lebih terkelola |
| 16 | Payment | QRIS + bayar di kasir (gateway TBD) | Pakai adapter pattern |
| 17 | Membership | Skip (hanya `customer_id` nullable) | Belum dipakai |
| 18 | Stok | Mode `status_only` (badge Tersedia/Habis) + toggle per outlet | **Final** — data stok POS tidak akurat |
| 19 | Jam operasional | Di-setup sendiri per outlet, bukan ikut shift | **Final** — shift = siklus kas |
| 20 | Merchant QRIS | Terpisah dari EDC; disatukan hanya di pencatatan | **Terbuka** — menunggu keputusan tim keuangan |
| 21 | Lifecycle order | `received` → auto `preparing` (worker +30s) → `served` (terminal) | Operasional antar langsung setelah selesai |
| 22 | Expiry | QRIS 15 mnt, kasir 60 mnt, sesi EOD 23:59 (semua configurable) | Batas waktu via Node worker |

> **Catatan:** baris 20 masih **terbuka** dan dibahas pada sesi presentasi internal (lihat §14). Baris 18, 19, 21, 22 sudah **final**.

---

## 3. Arsitektur Komponen

```
┌──────────────────┐      REST / SSE       ┌─────────────────────────────┐
│  PWA Vue 3       │◄─────────────────────►│                             │
│  (customer)      │                       │     BFF — ORDER SERVICE     │
└──────────────────┘                       │        Node.js (Express)    │
                                           │  ┌───────────────────────┐  │
┌──────────────────┐      REST              │  │  Tenant Resolver      │  │
│  Android Kasir   │◄─────────────────────►│  │  (outlet dari QR)     │  │
│  (existing app)  │                       │  ├───────────────────────┤  │
└────────┬─────────┘                       │  │  Cart / Order Engine  │  │
         ▲                                 │  ├───────────────────────┤  │
         │ FCM Push                        │  │  Pricing Engine       │  │
         │                                 │  │  (tax/voucher)        │  │
         │                                 │  ├───────────────────────┤  │
         │                                 │  │  Payment Adapter      │  │
         │                                 │  ├───────────────────────┤  │
         │                                 │  │  SSE Hub              │  │
         │                                 │  └───────────┬───────────┘  │
         │                                 └──────────────┼──────────────┘
         │                                                │
         │  ┌─────────────────────────────────────────────▼──────────────┐
         │  │                    MySQL (shared with POS Laravel)         │
         │  │  ┌───────────────────────┐   ┌──────────────────────────┐  │
         │  │  │  Tabel order (ot_*)   │   │  Tabel POS (existing)    │  │
         │  │  │  ot_orders, ...       │   │  products, variants,     │  │
         │  │  │  ot_vouchers, ...     │   │  modifiers, taxes,       │  │
         │  │  │  engine: MySQL        │   │  transactions            │  │
         │  │  │                       │   │  (open_bills: DILARANG)  │  │
         │  │  └───────────────────────┘   └──────────────────────────┘  │
         │  └────────────────────────────────────────────────────────────┘
         │                                   ▲
         │                                   │ tulis transaksi (bridge)
         │  ┌────────────────────┐           │
         └──│  Payment Gateway   │──webhook──┘
            │  (QRIS)            │
            └────────────────────┘
```

### Tanggung Jawab per Komponen

| Komponen | Tanggung Jawab | Tidak Bertanggung Jawab |
|---|---|---|
| **PWA Vue 3** | UI menu/cart/checkout, scan QR, tampil status | Hitung harga final, validasi stok |
| **BFF Node** | Sesi, pricing, voucher, payment, bridge, SSE | Simpan katalog, laporan keuangan |
| **Backoffice Laravel** | Skema, katalog, laporan, modul penerimaan order | Menghitung pricing publik |
| **Android Kasir** | Terima order (FCM), settle bayar-di-kasir, tampil list | Membuat order publik |
| **Payment Gateway** | Proses QRIS, kirim webhook | — |
| **MySQL** | Sumber data tunggal | — |

---

## 4. Skema Data

### 4.1 Tabel Order Baru (prefix `ot_`)

#### `ot_dining_tables`
Menyimpan meja & token QR per outlet.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| outlet_id | BIGINT | FK ke `outlets.id` |
| code | VARCHAR(50) | Kode meja, mis. "M-14" |
| name | VARCHAR(100) | Nama tampilan meja |
| qr_token | VARCHAR(64) UNIQUE | Token opaque, **bisa di-rotate** |
| qr_active | TINYINT(1) | Default 1 |
| capacity | INT | Opsional |
| created_at, updated_at, deleted_at | TIMESTAMP | Soft delete |

Index: `UNIQUE(qr_token)`, `INDEX(outlet_id)`.

#### `ot_table_sessions`
Satu sesi = satu kunjungan **device**. Model sesi **device-based**: 1 device : 1 meja, tidak digabung antar-device (fitur Group Order ditunda).

| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| device_id | CHAR(36) | UUID dari PWA |
| table_id | BIGINT | FK `ot_dining_tables.id` (meja saat sesi dibuka) |
| outlet_id | BIGINT | Denormalisasi untuk query |
| status | ENUM('open','closed') | Default `open` |
| opened_at | TIMESTAMP | |
| closed_at | TIMESTAMP NULL | |
| close_reason | ENUM('eod','kasir','manual') NULL | |
| closed_by | ENUM('system','kasir') NULL | |
| created_at, updated_at | TIMESTAMP | |

Index: `INDEX(device_id, status)`, `INDEX(table_id, status)`, `INDEX(outlet_id, status)`.

> **Aturan aplikasi:** satu device boleh punya satu sesi `open` per `(device_id, table_id)`; sesi baru dibuat setelah sesi sebelumnya `closed` atau pada hari berikutnya. Karena pindah meja diperbolehkan, **tidak** ada unique constraint pada `(device_id, status)`. Sesi ditutup saat EOD (default `23:59`, configurable) atau kasir menutup — **bukan** saat bill lunas.

> **Catatan:** tabel `ot_session_devices` **tidak dipakai** pada model ini. Bila fitur Group Order (beberapa device berbagi satu bill) diaktifkan di masa depan, tabel tersebut dapat ditambahkan kembali.

#### `ot_orders`
Entitas utama order publik.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| order_no | VARCHAR(30) UNIQUE | Nomor order publik |
| outlet_id | BIGINT | |
| session_id | BIGINT | FK `ot_table_sessions.id` |
| device_id | CHAR(36) | Device pembuat |
| table_id | BIGINT | Meja saat order dibuat |
| status | ENUM('draft','placed','received','preparing','served','cancelled','expired') | |
| payment_mode | ENUM('qris','pay_at_cashier') | |
| payment_status | ENUM('unpaid','pending','paid','failed','expired','refunded') | |
| subtotal | BIGINT | |
| discount_item_total | BIGINT | Default 0 |
| voucher_discount | BIGINT | Default 0 |
| modifier_total | BIGINT | Default 0 |
| tax_total | BIGINT | |
| tax_breakdown | JSON NULL | Detail per pajak |
| rounding | BIGINT | Default 0 |
| grand_total | BIGINT | |
| voucher_id | BIGINT NULL | FK `ot_vouchers.id` |
| notes | TEXT NULL | |
| lat, lng, accuracy | DECIMAL/VARCHAR NULL | Audit geofence |
| geofence_flag | ENUM('inside','outside','unknown') | Hasil geofence |
| pos_bridge_status | ENUM('pending','linked','failed') | Default `pending` |
| pos_transaction_id | BIGINT NULL | |
| placed_at | TIMESTAMP NULL | |
| payment_due_at | TIMESTAMP NULL | `placed_at` + `payment_due_minutes` (kasir) |
| paid_at | TIMESTAMP NULL | |
| created_at, updated_at | TIMESTAMP | |

Index: `INDEX(device_id, status)`, `INDEX(session_id)`, `INDEX(outlet_id, status)`, `INDEX(payment_status)`, `INDEX(pos_bridge_status)`.

> **Catatan status:** `served` adalah status **terminal sukses** (tanpa `completed`). `ready` dihapus karena operasional mengantar pesanan langsung setelah selesai dibuat. Timeline customer = 3 tahap: `received` → `preparing` → `served`.

#### `ot_order_items`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| order_id | BIGINT | FK `ot_orders.id` |
| product_id | BIGINT | Ref `products.id` |
| variant_id | BIGINT NULL | Ref `variant_products.id` |
| product_name | VARCHAR(255) | Snapshot |
| variant_name | VARCHAR(255) NULL | Snapshot |
| unit_price | BIGINT | Harga variant saat order |
| qty | INT | |
| modifier_total | BIGINT | Total modifier per unit |
| line_total | BIGINT | |
| exclude_tax | TINYINT(1) | Snapshot dari product |
| notes | TEXT NULL | |
| status | ENUM('pending','preparing','served','cancelled') | |

#### `ot_order_item_modifiers`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| order_item_id | BIGINT | FK `ot_order_items.id` |
| modifier_id | BIGINT | Ref `modifiers.id` |
| name | VARCHAR(255) | Snapshot |
| harga | BIGINT | Snapshot |
| qty | INT | Default 1 |

#### `ot_order_payments`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| order_id | BIGINT | FK `ot_orders.id` |
| method | ENUM('qris','cashier','gateway') | |
| gateway_ref | VARCHAR(100) NULL | ID dari gateway |
| amount | BIGINT | |
| status | ENUM('pending','paid','failed','expired') | |
| qr_string | TEXT NULL | Payload QR |
| expires_at | TIMESTAMP NULL | QRIS: `created_at` + `qris_expiry_minutes` |
| paid_at | TIMESTAMP NULL | |
| raw_callback | JSON NULL | Audit webhook |

#### `ot_order_status_logs`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| order_id | BIGINT | |
| from_status | VARCHAR(30) NULL | |
| to_status | VARCHAR(30) | |
| actor | ENUM('system','kasir','customer') | |
| note | VARCHAR(255) NULL | |
| created_at | TIMESTAMP | |

#### `ot_vouchers`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| outlet_id | BIGINT NULL | NULL = global semua outlet |
| code | VARCHAR(50) UNIQUE | Kode voucher |
| type | ENUM('percent','fixed') | |
| value | BIGINT | Persen atau rupiah |
| min_spend | BIGINT | Default 0 |
| max_discount | BIGINT NULL | Cap untuk percent |
| quota_total | INT NULL | NULL = tak terbatas |
| quota_per_device | INT NULL | |
| quota_per_session | INT NULL | |
| quota_used | INT | Default 0 |
| valid_from | TIMESTAMP NULL | |
| valid_to | TIMESTAMP NULL | |
| scope | ENUM('all','category','product') | Default `all` |
| scope_ids | JSON NULL | |
| status | TINYINT(1) | Default 1 |

#### `ot_voucher_redemptions`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| voucher_id | BIGINT | |
| order_id | BIGINT | |
| session_id | BIGINT | Guard kuota per sesi |
| device_id | CHAR(36) | Guard kuota per device |
| discount_amount | BIGINT | |
| ip_address | VARCHAR(45) NULL | Audit |
| user_agent | VARCHAR(255) NULL | Audit |
| created_at | TIMESTAMP | |

Index: `UNIQUE(voucher_id, order_id)`, `UNIQUE(voucher_id, device_id)`, `UNIQUE(voucher_id, session_id)`, `INDEX(device_id)`.

#### `ot_banners`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| outlet_id | BIGINT NULL | NULL = global |
| title | VARCHAR(255) | |
| image_url | VARCHAR(500) | |
| action_type | ENUM('url','internal','product','category','voucher','promo') | |
| action_value | VARCHAR(500) NULL | |
| position | VARCHAR(50) | Mis. "home_top" |
| sort_order | INT | Default 0 |
| start_at, end_at | TIMESTAMP NULL | |
| status | TINYINT(1) | Default 1 |

### 4.2 Tabel POS yang Digunakan (read/link only)

| Tabel POS | Dipakai untuk |
|---|---|
| `outlets` | Identitas outlet + geo (kolom baru) |
| `categories` | Kategori menu |
| `products` | Nama, deskripsi, foto, exclude_tax, status |
| `variant_products` | **Harga jual & stok** (produk tak punya harga) |
| `modifier_groups` | Grup modifier per produk (JSON `product_id`) |
| `modifiers` | Item modifier + harga |
| `taxes` | Pajak per outlet (10% PB1) |
| `transactions` / `transaction_items` | Bridge transaksi final |
| `category_payments` / `payments` | Mapping metode bayar |
| `petty_cashes` / `shift_sessions` | Penentuan petty cash bridge (lihat §5.2 — open item) |
| `users` | Bridge user sistem |

> ⚠️ **Tabel yang TIDAK BOLEH disentuh order-table:**
> `open_bills` dan `item_open_bills` adalah **domain kasir POS** yang sudah dipakai untuk **open bill khusus karyawan**. Order-table dilarang membaca/menulis ke tabel ini agar tidak mengganggu operasional kasir.

### 4.4 Tabel Konfigurasi Order (baru)

#### `ot_outlet_settings`
Menyimpan konfigurasi order per outlet (jam operasional, mode stok, dsb).

| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| outlet_id | BIGINT UNIQUE | |
| order_enabled | TINYINT(1) | Master switch outlet |
| stock_mode | ENUM('off','status_only','strict') | Default `status_only` |
| open_time | TIME NULL | Jam buka order menu |
| close_time | TIME NULL | Jam tutup order menu |
| forced_close | TINYINT(1) | Pause darurat oleh staff |
| service_fee_pct | DECIMAL(5,2) NULL | Opsional |
| auto_preparing_delay_seconds | INT | Default **30** — auto `received` → `preparing` |
| session_close_time | TIME | Default **23:59** — EOD auto-close sesi |
| created_at, updated_at | TIMESTAMP | |

Index: `UNIQUE(outlet_id)`.

#### `ot_payment_methods` (baru)
Konfigurasi metode pembayaran per outlet, menjembatani Order Table dengan master pembayaran POS.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | BIGINT PK | |
| outlet_id | BIGINT NULL | NULL = global default semua outlet |
| payment_id | BIGINT NULL | Ref `payments.id` (POS) |
| category_payment_id | BIGINT NULL | Ref `category_payments.id` (POS) — dipakai bridge |
| code | VARCHAR(30) | `qris` / `pay_at_cashier` |
| label | VARCHAR(100) | Nama tampilan |
| nama_tipe_pembayaran | VARCHAR(50) NULL | Nilai untuk kolom bridge di `transactions` |
| qris_expiry_minutes | INT NULL | Default **15** (khusus QRIS) |
| payment_due_minutes | INT NULL | Default **60** (khusus kasir) |
| enabled | TINYINT(1) | Default 1 |
| sort_order | INT | Default 0 |
| created_at, updated_at | TIMESTAMP | |

Index: `INDEX(outlet_id)`, `INDEX(payment_id)`, `UNIQUE(outlet_id, code)`.

> **Verifikasi ke skema existing:** nama tabel `payments`/`category_payments` dan kolom `nama_tipe_pembayaran` pada `transactions` harus dikonfirmasi langsung ke database POS.

### 4.5 Kolom Baru di Tabel POS

```sql
ALTER TABLE outlets
  ADD COLUMN latitude  DECIMAL(10,7) NULL,
  ADD COLUMN longitude DECIMAL(10,7) NULL,
  ADD COLUMN geofence_radius_m INT DEFAULT 150;
```

> **Catatan kritis:** mengubah tabel POS harus dikomunikasikan dengan tim backoffice karena dapat memengaruhi aplikasi POS/Android.

---

## 5. Model Integritas & Bridge ke POS

### 5.1 Prinsip: Pisah dari Domain Kasir
Order-table **hanya menulis ke `transactions` + `transaction_items`** — jalur transaksi langsung yang dipakai 99,87% transaksi POS. Order-table **tidak menyentuh `open_bills`/`item_open_bills`** (domain kasir untuk open bill karyawan).

| Fakta DB | Angka | Implikasi |
|---|---|---|
| Transaksi via `transactions` langsung | 547.220 (99,87%) | Jalur normal, aman diikuti |
| Transaksi via `open_bills` | 731 (0,13%) | Fitur khusus kasir — tidak dipakai |
| `transactions.patty_cash_id` NULL | **0** | Wajib diisi → butuh keputusan (lihat 5.2) |
| `transactions.shift_session_id` NULL | 547.940 (99,99%) | Kolom baru, praktis belum dipakai → aman dikosongkan |

### 5.2 Bridge User & Petty Cash

**Bridge user:** satu akun sistem global `order-table@system` (dibuat sekali oleh backoffice), dipakai untuk semua transaksi order-table agar mudah difilter di laporan.

**Petty cash — ⚠️ OPEN ITEM (menunggu keputusan tim keuangan):**
`transactions.patty_cash_id` bersifat NOT NULL. Karena petty cash adalah **laci kas fisik** yang dibuka/ditutup kasir, memasukkan uang QRIS order-table ke petty cash kasir berpotensi **mengganggu rekonsiliasi kas**. Tiga opsi yang dibahas:

| Opsi | Deskripsi | Dampak |
|---|---|---|
| **A. Petty cash "Order Online" per outlet** | Buat 1 petty cash virtual khusus order-table (non-cash) per outlet | Laci kasir tidak terganggu; perlu record sistem per outlet |
| **B. Pakai petty cash aktif outlet** | Bridge mencari petty cash `close IS NULL` untuk outlet | Sederhana; uang QRIS tercampur ke laci kas → rekonsiliasi terganggu |
| **C. Pendekatan lain dari tim keuangan** | Mis. kolom khusus / petty cash non-cash tersendiri | Menunggu masukan |

> Keputusan ini dibahas di sesi presentasi internal (§14, Pertanyaan Diskusi #3).

### 5.3 Alur Bridge (satu transaksi DB)
Saat order lunas:
```
BEGIN TRANSACTION
  1. INSERT transactions
       - outlet_id, user_id       = bridge user sistem
       - patty_cash_id            = <hasil keputusan §5.2>
       - shift_session_id         = NULL
       - category_payment_id      = 2 (QRIS)  / sesuai metode
       - nama_tipe_pembayaran     = 'QRIS'
       - reference_id             = order_no  (telusur + idempotency)
       - total, total_pajak, total_diskon, total_modifier
  2. INSERT transaction_items (per ot_order_item)
       - product_id, variant_id, harga, modifier_id, catatan
       - item_open_bill_id        = NULL
  3. UPDATE ot_orders SET pos_bridge_status='linked', pos_transaction_id=?
COMMIT
```
- **Atomic** — jika salah satu gagal, rollback penuh.
- **Idempotency** — cek `pos_bridge_status` sebelum bridge; `reference_id` memakai `order_no`.
- **Tanpa `open_bills`** — sama sekali tidak menulis ke tabel domain kasir.
- **Deleted order** — pembatalan order yang sudah di-bridge harus memicu void/refund di POS (perlu kesepakatan tim backoffice).

---

## 6. Alur Bisnis

### 6.1 Scan QR → Mulai Order
```
Customer scan QR  →  GET /o/{outlet_slug}/t/{qr_token}
   → BFF resolve ot_dining_tables via qr_token
   → validasi qr_active
   → PWA kirim device_id (UUID lokal) + geolokasi (opsional)
   → BFF jalankan geofence check (lihat 6.2)
   → BFF cari/ciptakan ot_table_sessions(device_id, table_id, status=open)
   → PWA load menu dari tabel POS (via BFF)
```

### 6.2 Geofence Hybrid
```
radius = outlets.geofence_radius_m (default 150m)
jarak  = Haversine(lat,lng user, lat,lng outlet)

if lokasi tidak tersedia / izin ditolak:
    geofence_flag = 'unknown'  → warning + tandai order
elif jarak <= radius:
    geofence_flag = 'inside'   → lolos
elif jarak <= 2 * radius:
    geofence_flag = 'outside'  → warning + lanjut + tandai order
else:
    geofence_flag = 'outside'  → BLOKIR order (yakin jauh)
```
Seluruh request menyimpan `lat`, `lng`, `accuracy`, `geofence_flag` untuk audit.

### 6.3 Cart (Hybrid)
- **Local-first:** cart disimpan di Pinia + persist IndexedDB → tahan refresh/offline.
- **Server draft:** disinkronkan ke `ot_orders(status='draft')` secara background → tahan clear storage & lintas device.
- **Otoritatif:** saat checkout, server **revalidasi** harga, stok, modifier, dan voucher. Harga lokal diabaikan bila berbeda.

### 6.3b Kebijakan Stok (final)
Data stok POS tidak akurat (31% angka raksasa), sehingga kebijakan bertingkat via `ot_outlet_settings.stock_mode`:

| Mode | Perilaku | Kapan dipakai |
|---|---|---|
| `off` | Stok diabaikan sepenuhnya | Bila tim belum siap opname |
| `status_only` | Tampilkan **badge Tersedia/Habis** (tanpa angka); blokir bila stok ≤ 0; tombol manual "Habis" | **Default** |
| `strict` | Tampilkan angka & blokir ketat; butuh opname harian | Bila operasional siap |

**Default: `status_only`.** Mode `strict` menuntut setup/opname setiap hari; dapat diaktifkan per outlet via `ot_outlet_settings`.

### 6.3c Jam Operasional Menu (final)
Akses menu diatur oleh `ot_outlet_settings`, **bukan** oleh shift kasir:

```
order_enabled = 1
AND (open_time <= now <= close_time)   // bila diisi
AND forced_close = 0                    // pause darurat oleh staff
```
Tiga lapis kontrol: (1) jam terjadwal, (2) pause darurat, (3) status per kategori/produk POS. Jam buka/tutup configurable per outlet.

### 6.4 Pricing Engine
```
Langkah 1  subtotal        = Σ ((unit_price + modifier_total) × qty)
Langkah 2  discount_item   = total diskon per item (jika ada)
Langkah 3  voucher         = diskon voucher (percent difus cap max_discount;
                             fixed; cek min_spend & scope)
Langkah 4  tax_base        = subtotal − discount_item − voucher
                             (hanya baris non-exclude_tax)
Langkah 5  tax_total       = Σ (tax_base × tax.amount/100)  per outlet
Langkah 6  rounding        = 0 (default OFF, ikut config checkouts bila ON)
Langkah 7  grand_total     = subtotal − discount_item − voucher
                             + tax_total ± rounding
```
> **Keputusan final:** voucher memotong subtotal, dan pajak dihitung dari subtotal **setelah** voucher. `modifier_total` masuk ke basis pajak (kecuali produk `exclude_tax`).

### 6.5 Checkout — Metode A: Bayar QRIS
```
PWA pilih "Bayar Sekarang" (QRIS)
 → POST /orders/{id}/checkout {payment_mode: 'qris'}
 → BFF hitung final, buat ot_orders(status=placed, payment_status=pending)
 → BFF baca ot_payment_methods.qris_expiry_minutes (default 15 menit)
 → BFF minta QR dinamis ke gateway
 → INSERT ot_order_payments(pending, qr_string, expires_at = now + qris_expiry_minutes)
 → BFF kirim FCM ke kasir outlet ("order baru, menunggu bayar")
 → PWA tampilkan QR + buka SSE
 → Customer bayar → Gateway kirim WEBHOOK
 → BFF verifikasi signature + idempotency
 → UPDATE ot_order_payments(paid), ot_orders(payment_status=paid, status=received, paid_at=now)
 → BRIDGE ke tabel POS (transaksi DB)
 → SSE push status "received" ke PWA + FCM status ke kasir
 → (worker) +auto_preparing_delay_seconds → status=preparing
```

### 6.6 Checkout — Metode B: Bayar di Kasir
```
PWA pilih "Bayar di Kasir"
 → POST /orders/{id}/checkout {payment_mode: 'pay_at_cashier'}
 → BFF hitung final, buat ot_orders(status=placed, payment_status=unpaid)
 → BFF baca ot_payment_methods.payment_due_minutes (default 60 menit)
 → ot_orders.payment_due_at = now + payment_due_minutes
 → BFF kirim FCM ke Android kasir (outlet terkait)
 → Kasir buka pesanan di app → settle pembayaran
 → POST /orders/{id}/settle (dari app kasir)
 → BFF update payment_status=paid, status=received, paid_at=now
 → BRIDGE ke tabel POS
 → SSE push status ke PWA
 → (worker) +auto_preparing_delay_seconds → status=preparing
```

> Bila `payment_due_at` terlewat, worker mengubah order menjadi `expired`. Dapur **tidak** menyiapkan pesanan sebelum pembayaran lunas.

### 6.6b Strategi Merchant QRIS **[USULAN — §14 #3]**
| Aspek | Rekomendasi |
|---|---|
| Settlement/merchant | **Terpisah** dari QRIS statis EDC |
| Pencatatan di POS | Disatukan: `nama_tipe_pembayaran='QRIS'`, `category_payment_id=2` |
| Alasan | EDC = QRIS statis (nominal diinput kasir, tanpa webhook); order-table = QRIS **dinamis** (nominal terikat order, ber-webhook). Menggabung merchant mengacaukan rekonsiliasi otomatis |

Keputusan penempatan dana QRIS order-table (merchant sama / terpisah) **menunggu tim keuangan**.

### 6.7 Realtime & Notifikasi

| Kebutuhan | Mekanisme | Alasan |
|---|---|---|
| Order baru → kasir (app **tertutup**) | **FCM Push** | WebSocket mati saat app tertutup |
| Order baru → kasir (app terbuka) | FCM + refresh list | Bisa tambah WebSocket opsional |
| Status order → PWA | **SSE** | Satu arah, ringan, auto-reconnect |
| Status pembayaran | **Webhook gateway** | Source of truth, bukan socket |
| Fallback | Polling ringan | Saat SSE/FCM gagal |

**SSE Hub:** endpoint `GET /orders/{id}/stream` (auth via order token). Broadcast internal pakai Redis pub/sub agar bisa horizontal scaling.

**FCM:** registrasi topic per outlet (`outlet-{id}`) atau token per device kasir; payload `data` + `notification`.

**Timeline customer:** 3 tahap — `received` (Diterima) → `preparing` (Disiapkan) → `served` (Disajikan).

### 6.8 Riwayat Transaksi
- `GET /orders?device_id={uuid}` → hanya order milik device tersebut, **lintas sesi**.
- `session_id` hanya filter opsional, bukan syarat akses. Customer yang pindah meja atau memesan lagi setelah order sebelumnya lunas tetap melihat riwayatnya.
- Privasi antar customer tetap terjaga karena `device_id` berbeda; saat sesi ditutup, device lain tidak bisa melihat order tersebut.
- Riwayat lengkap untuk bisnis tetap tersedia di laporan POS.

---

## 7. Kontrak API (Ringkas)

Base: `/api/v1` — auth customer via `X-Device-Id` + `X-Session-Token` (JWT ringan).

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/tables/{qr_token}` | Resolve meja & outlet dari QR |
| POST | `/tables/{qr_token}/session` | Mulai/join sesi device |
| GET | `/outlets/{id}/menu` | Katalog: kategori → produk → varian → modifier |
| GET | `/outlets/{id}/banners` | Banner aktif |
| POST | `/cart/draft` | Simpan/update draft cart (server) |
| GET | `/cart/draft` | Ambil draft cart |
| POST | `/vouchers/validate` | Validasi kode voucher |
| POST | `/orders` | Buat order dari cart |
| POST | `/orders/{id}/checkout` | Checkout (qris / pay_at_cashier) |
| GET | `/orders/{id}` | Detail order |
| GET | `/orders` | Riwayat order (by device, lintas sesi) |
| GET | `/orders/{id}/stream` | **SSE** status order |
| POST | `/orders/{id}/settle` | (App kasir) settle bayar-di-kasir |
| POST | `/payments/webhook` | **Webhook gateway** (public, signature) |
| GET | `/health` | Health check |

### Contoh Request Checkout
```json
POST /api/v1/orders/1234/checkout
{
  "payment_mode": "qris",
  "voucher_code": "KOLUDJAYA15",
  "table_session_id": 99,
  "device_id": "b3f1e2...-uuid"
}
```

### Contoh Response Checkout
```json
{
  "order_no": "OT-20250101-0042",
  "status": "placed",
  "payment_status": "pending",
  "subtotal": 100000,
  "discount_item_total": 0,
  "voucher_discount": 15000,
  "tax_total": 8500,
  "rounding": 0,
  "grand_total": 93500,
  "payment": {
    "method": "qris",
    "qr_string": "00020101021226...",
    "expires_at": "2025-01-01T10:15:00Z"
  }
}
```

---

## 8. Keamanan & Anti-Abuse

| Area | Kontrol |
|---|---|
| QR meja | Token opaque acak, **rotatable/revocable**, tidak sekuensial |
| Geofence | Hybrid; simpan `lat/lng/accuracy/flag`; blokir hanya bila yakin jauh |
| Rate limit | Per device + per IP + per meja (mis. order max N/menit) |
| Sesi | Device-based; auto-close EOD (default 23:59) atau kasir; cek sesi masih valid |
| Blokir order | Device-level: tolak order baru selama ada order non-terminal (`bukan served/cancelled/expired`) |
| Voucher | Guard berlapis: `quota_used < quota_total` (authoritative, row-lock), `UNIQUE(voucher_id, device_id)`, `UNIQUE(voucher_id, session_id)`, `UNIQUE(voucher_id, order_id)`, cek `min_spend`/scope. Device/sesi bersifat best-effort (clear storage/incognito bisa menembus); batas kerugian tetap `quota_total` |
| Webhook | Verifikasi signature + idempotency + validasi nominal |
| Device | `device_id` UUID lokal; tidak untuk autentikasi kuat sendirian |
| Bridge | Idempotency by `order_no`; cek status sebelum bridge |
| Data pribadi | Tidak menyimpan data pelanggan (membership skipped) |

---

## 9. Non-Functional Requirements

| Aspek | Target |
|---|---|
| Waktu muat menu | < 2s pada 4G |
| Latency checkout | < 500ms (di luar gateway) |
| Order → notif kasir | < 3s (FCM) |
| Ketersediaan | 99.5% |
| Konsistensi | Bridge bersifat atomic (ACID) |
| Skalabilitas | SSE Hub dengan Redis pub/sub; stateless BFF |
| Observability | Log terstruktur, metrik order/bridge/webhook, alert kegagalan |
| Offline | Cart tetap bisa diisi offline; checkout butuh online |

---

## 10. Struktur Proyek yang Diusulkan

### BFF — `order-service/` (Node.js/Express)
```
order-service/
├─ src/
│  ├─ config/          # env, db pool, redis, firebase, gateway
│  ├─ db/              # koneksi MySQL (shared), query helpers
│  ├─ modules/
│  │  ├─ catalog/      # baca langsung products/variants/modifiers/taxes
│  │  ├─ tables/       # QR, sesi, device
│  │  ├─ cart/         # draft server
│  │  ├─ orders/       # order engine + state machine
│  │  ├─ pricing/      # pricing engine (tax/voucher)
│  │  ├─ vouchers/
│  │  ├─ banners/
│  │  ├─ payments/     # adapter gateway + webhook
│  │  ├─ bridge/       # tulis ke tabel POS (satu transaksi)
│  │  ├─ sse/          # SSE hub (+ Redis pub/sub)
│  │  └─ notifications/ # FCM dispatcher
│  ├─ middlewares/     # auth device, tenant resolver, rate limit, error
│  └─ utils/           # haversine, idempotency, logger
├─ migrations/         # (referensi saja; eksekusi via Laravel)
└─ package.json
```

### Migrasi Laravel (pemilik skema)
```
database/migrations/
├─ xxxx_create_ot_dining_tables_table.php
├─ xxxx_create_ot_table_sessions_table.php
├─ xxxx_create_ot_vouchers_table.php
├─ xxxx_create_ot_orders_table.php
├─ xxxx_create_ot_order_items_table.php
├─ xxxx_create_ot_order_item_modifiers_table.php
├─ xxxx_create_ot_order_payments_table.php
├─ xxxx_create_ot_order_status_logs_table.php
├─ xxxx_create_ot_voucher_redemptions_table.php
├─ xxxx_create_ot_banners_table.php
├─ xxxx_create_ot_outlet_settings_table.php
├─ xxxx_create_ot_payment_methods_table.php
└─ xxxx_add_geolocation_to_outlets_table.php
```

### PWA — `order-pwa/` (Vue 3)
```
order-pwa/
├─ src/
│  ├─ views/           # Menu, Cart, Checkout, Status, History
│  ├─ stores/          # Pinia (cart, session, order)
│  ├─ services/        # API client, SSE client, IndexedDB
│  ├─ router/          # route QR /o/:outlet/t/:token
│  └─ components/      # MenuCard, ModifierSheet, BannerCarousel
└─ package.json
```

---

## 11. Checklist Pra-Rilis (7 Outlet)

- [ ] Migrasi tabel `ot_*` (termasuk `ot_outlet_settings`, `ot_payment_methods`) + kolom geo `outlets` (via Laravel).
- [ ] User sistem bridge `order-table@system` dibuat.
- [ ] **Keputusan petty cash bridge** (§5.2) — dari tim keuangan.
- [ ] Mode stok default `status_only` terpasang (final; dapat diubah per outlet).
- [ ] Jam operasional per outlet terisi (final; setup sendiri).
- [ ] **Keputusan merchant QRIS** (§14 #3) — terpisah atau digabung EDC.
- [ ] Geofence: koordinat + radius 7 outlet.
- [ ] `ot_outlet_settings` terisi untuk 7 outlet (jam buka/tutup, stock_mode, auto_preparing_delay_seconds, session_close_time).
- [ ] `ot_payment_methods` terisi (`qris`, `pay_at_cashier`) untuk 7 outlet.
- [ ] Generate & cetak QR seluruh meja 7 outlet.
- [ ] Registrasi device kasir + FCM token per outlet.
- [ ] Konfigurasi merchant/credential payment per outlet (jika settlement per outlet).
- [ ] Admin console: meja/QR (rotate), voucher, banner, monitor order, log bridge.
- [ ] Kegagalan bridge: alert + mekanisme re-bridge.
- [ ] Dashboard monitoring: order, FCM, webhook, bridge.
- [ ] SOP kasir: handling order belum dibayar & void.

---

## 12. Roadmap

| Fase | Deliverable |
|---|---|
| A | Dokumen desain (ini) |
| B | Migrasi tabel order di Laravel + kolom geo |
| C | Kerangka BFF Node (modul, pricing, payment, bridge, SSE, FCM) |
| D | Kerangka PWA Vue (menu, cart, checkout, status SSE) |
| E | Presentasi HTML beranimasi (internal/management/BOD) |
| F | Verifikasi endpoint, simulasi webhook, uji pricing & bridge |

---

## 13. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Dua aplikasi tulis satu DB | Laravel pemilik migrasi; prefiks `ot_`; dokumentasi kontrak data |
| Bridge gagal sebagian | Transaksi DB atomic + status `pending` + re-bridge job |
| Salah pilih petty cash → laci kas kasir tidak balance | Keputusan §5.2 + petty cash non-cash terpisah (Opsi A) |
| GPS indoor tidak akurat | Geofence hybrid + audit + opsi override kasir |
| Sesi bentrok multi-device | Sesi device-based (1 device : 1 meja); filter riwayat per `device_id` lintas sesi |
| Gateway belum diputuskan | Adapter pattern, mudah tambah provider |
| Stok tidak akurat → refund berulang | Mode stok `status_only` + tombol manual "Habis" (bila disetujui) |
| Data stok POS tidak dipercaya | Jangan tampilkan angka stok; hanya status (bila disetujui) |

---

## 14. Pertanyaan Diskusi Internal (Bahan Presentasi)

Berikut pertanyaan yang dibahas bersama tim (operasional, keuangan, backoffice). **Status #1 dan #2 sudah final; #3 masih menunggu keputusan tim keuangan.**

### Pertanyaan 1 — Apakah stok perlu dimunculkan? **(FINAL)**
- **Konteks data:** Dari 2.860 varian di `variant_products`, **906 (31%) ber-stok raksasa** (hingga 9.999.999.997) dan ada yang negatif (−5.599). Ini menunjukkan stok dipakai sebagai angka "unlimited", bukan tracking riil.
- **Tujuan:** mencegah refund berulang akibat kehabisan stok padahal sistem masih menunjukkan banyak.
- **Keputusan:** implementasi **toggle per outlet** (`stock_mode = off | status_only | strict`), default `status_only` + tombol manual "Habis" yang bisa ditekan staff.
- **Tradeoff:** mode `strict` menuntut setup/opname setiap hari; aktifkan per outlet hanya bila operasional siap.

### Pertanyaan 2 — Akses menu: ikut jam shift, atau setup sendiri via backoffice? **(FINAL)**
- **Konteks data:** Tidak ada tabel jam operasional outlet. `note_receipt_schedulings` hanya jadwal promo produk. `configs` hanya 4 baris. `shift_sessions` baru 3 baris (belum diadopsi).
- **Keputusan:** setup sendiri via backoffice — jam buka/tutup per outlet di `ot_outlet_settings`, dapat ditutup manual (pause darurat) untuk kondisi tak terduga. Shift = siklus kas, bukan penanda akses menu.

### Pertanyaan 3 — QRIS payment gateway: rekeningnya digabung EDC atau terpisah? **(TERBUKA)**
- **Konteks data:** QRIS sudah terdaftar di `payments` (`id=2`). 30 hari terakhir: **QRIS 18.892 trx (Rp 1,197 M)** via EDC, Cash Rp 142 jt, DEBIT Rp 75 jt.
- **Isu:** EDC memakai **QRIS statis** (nominal diinput kasir, tanpa webhook), sedangkan order-table memerlukan **QRIS dinamis** (nominal terikat order, ada webhook). Menggabung keduanya ke satu merchant berpotensi **mengacaukan rekonsiliasi otomatis**.
- **Usulan:** merchant/rekening **terpisah** di level settlement; disatukan hanya di level pencatatan (`nama_tipe_pembayaran='QRIS'`, `category_payment_id=2`) agar laporan tetap menyatu.
- **Keputusan ada di tim keuangan** — penempatan dana QRIS order-table: merchant yang sama atau terpisah.

---

*Dokumen ini menjadi acuan tunggal (single source of truth) untuk implementasi. Perubahan keputusan harus diperbarui di bagian §2 (ADR) dan §14 (pertanyaan diskusi).*
