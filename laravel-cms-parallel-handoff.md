# Temporary Handoff — CMS Order Table untuk Agent Laravel

> **Status:** temporary handoff untuk pekerjaan paralel CMS/backoffice.
>
> Dokumen ini tidak menggantikan [`laravel-migration-handoff.md`](laravel-migration-handoff.md), [`order-table-schema-audit-handoff.md`](order-table-schema-audit-handoff.md), atau [`system-design.md`](system-design.md). Gunakan ketiganya sebagai acuan skema dan domain.

## 1. Tujuan

Implementasikan modul CMS/backoffice untuk mengelola konfigurasi dan kebutuhan operasional Order Table, sementara backend Node mengerjakan pricing, payment lifecycle, worker, SSE, dan POS bridge.

CMS bertanggung jawab atas:

- Konfigurasi outlet Order Table.
- Meja dan QR.
- Mapping metode pembayaran.
- Voucher dan banner.
- Monitoring order/session/payment/bridge.
- Aksi operasional terbatas dengan permission dan audit log.

CMS **bukan** sumber kebenaran untuk pricing dan payment status.

## 2. Dokumen Wajib Dibaca

1. [`laravel-migration-handoff.md`](laravel-migration-handoff.md)
2. [`order-table-schema-audit-handoff.md`](order-table-schema-audit-handoff.md)
3. [`system-design.md`](system-design.md), terutama §2, §4, §5, §6, §7, dan §8
4. [`../README.md`](../README.md)

Jika contoh query/desain berbeda dari audit skema aktual, gunakan `order-table-schema-audit-handoff.md` sebagai fakta teknis.

## 3. Guardrail Wajib

1. Laravel adalah satu-satunya pemilik migration dan skema.
2. Jangan membaca atau menulis `open_bills` dan `item_open_bills`.
3. Jangan hard-delete order, session, payment, status log, atau voucher redemption.
4. CMS tidak menghitung authoritative pricing.
5. CMS tidak memproses payment webhook atau menandai QRIS paid secara manual.
6. CMS tidak menjalankan expiry payment/order atau auto `received` → `preparing`.
7. CMS tidak membuat customer session token.
8. CMS tidak menulis langsung ke `transactions`/`transaction_items`; POS bridge tetap milik Node.
9. Jangan mengedit nominal snapshot order (`subtotal`, pajak, diskon, modifier, total).
10. Semua aksi sensitif harus memiliki permission dan audit log.

## 4. Status Order Final

Enum database:

```text
draft
placed
received
preparing
served
cancelled
expired
```

Timeline customer:

```text
Diterima (received) → Disiapkan (preparing) → Disajikan (served)
```

Aturan:

- `draft`: cart/order draft, belum checkout.
- `placed`: checkout berhasil, menunggu pembayaran.
- `received`: pembayaran sudah diterima.
- `preparing`: otomatis dari `received` oleh Node worker (default 30 detik).
- `served`: pesanan sudah diantar; terminal sukses.
- `cancelled`: pembatalan manual dengan alasan.
- `expired`: payment melewati deadline.

CMS hanya boleh menyediakan aksi utama:

- `preparing` → `served`, manual oleh dapur/kasir.
- Cancel order dengan alasan dan permission khusus.
- Close session manual.

CMS tidak boleh mengubah `placed` → `received` atau menandai payment paid tanpa flow internal Node yang disetujui.

## 5. Prioritas P0 — Wajib untuk Uji End-to-End

### 5.1 Pengaturan Order per Outlet

Tabel: `ot_outlet_settings`.

Field:

- `outlet_id`
- `order_enabled`
- `stock_mode`: `off`, `status_only`, `strict`
- `open_time`
- `close_time`
- `forced_close`
- `service_fee_pct`
- `auto_preparing_delay_seconds` (default 30)
- `session_close_time` (default 23:59)

UI/behavior:

- Satu record per outlet.
- Tombol cepat **Pause Order / Buka Order** untuk `forced_close`.
- Tampilkan status efektif: enabled, forced close, atau di luar jam operasional.
- `stock_mode` default `status_only`.
- Validasi `open_time`/`close_time` dan nilai non-negatif.
- Jangan mengubah `auto_preparing_delay_seconds` atau session close time tanpa permission konfigurasi.

Acceptance criteria:

- Operator bisa membuat/update setting outlet.
- Duplicate `outlet_id` ditolak.
- Semua perubahan tercatat pada audit log.

### 5.2 Koordinat dan Geofence Outlet

Tabel: `outlets`.

Field:

- `latitude`
- `longitude`
- `geofence_radius_m` (default 150)

UI/behavior:

- Input manual atau map picker.
- Preview marker dan radius.
- Latitude: `-90..90`.
- Longitude: `-180..180`.
- Radius: bilangan positif; rekomendasi batas UI `50..2000` meter.
- Outlet tanpa koordinat menghasilkan geofence `unknown` di Node, bukan otomatis ditolak.

### 5.3 Manajemen Meja dan QR

Tabel: `ot_dining_tables`.

Field:

- Outlet.
- `code`, misalnya `M-14`.
- `name`.
- `capacity`.
- `qr_active`.
- `qr_token`.
- Soft delete.

Fitur:

- List/filter per outlet.
- Create/edit meja.
- Generate token opaque menggunakan random cryptographic generator.
- Rotate token.
- Aktif/nonaktif QR.
- Preview/download/cetak QR.
- Soft delete meja.

URL QR:

```text
/o/{outletSlug}/t/{qrToken}
```

Aturan:

- Operator tidak mengetik token secara manual.
- Token tidak boleh ID sekuensial.
- Rotation langsung membatalkan QR cetak lama.
- Tampilkan confirmation dialog sebelum rotate/nonaktif/delete.
- Jangan hard-delete meja yang memiliki session/order.

Acceptance criteria:

- `qr_token` unik.
- QR hasil cetak membuka URL sesuai format.
- Token lama gagal setelah rotation.
- Semua rotate/disable dicatat audit log.

### 5.4 Konfigurasi Metode Pembayaran

Tabel: `ot_payment_methods`.

Gunakan konfigurasi **per outlet**, jangan global `outlet_id = NULL` pada tahap awal.

QRIS default development:

```text
code = qris
payment_id = 2
category_payment_id = 2
nama_tipe_pembayaran = QRIS
qris_expiry_minutes = 15
payment_due_minutes = NULL
enabled = 1
```

Bayar kasir kandidat development:

```text
code = pay_at_cashier
payment_id = NULL
category_payment_id = 1
nama_tipe_pembayaran = Cash
qris_expiry_minutes = NULL
payment_due_minutes = 60
enabled = 1
```

Catatan:

- `payments.id = 2` adalah QRIS.
- `category_payments.id = 2` adalah EDC.
- `category_payments.id = 1` adalah Cash.
- Mapping bayar kasir masih harus dikonfirmasi sebelum production.
- CMS mencegah duplicate `(outlet_id, code)`.
- Jangan simpan API key/payment secret di tabel ini.

## 6. Prioritas P1 — Konten dan Promo

### 6.1 Voucher

Tabel:

- `ot_vouchers`
- `ot_voucher_redemptions` (monitor read-only)

CRUD voucher:

- Outlet atau global.
- Kode.
- Tipe `percent`/`fixed`.
- Nilai.
- Minimum spend.
- Maximum discount.
- Total quota.
- Quota per device.
- Quota per session.
- Periode berlaku.
- Scope `all`, `category`, `product`.
- Scope IDs.
- Status aktif.

Validasi:

- Kode uppercase dan trim.
- Kode unique global sesuai constraint sekarang.
- Percent `1..100`.
- `max_discount` hanya relevan untuk percent.
- Scope IDs dipilih dari katalog; operator tidak mengedit JSON mentah.
- `quota_used` read-only.
- Reset quota, jika nanti dibuat, wajib permission khusus + audit log.

Monitor redemption:

- Order number.
- Outlet/session.
- Device ID dimasking.
- Discount amount.
- IP dimasking.
- User agent.
- Created time.

Node melakukan validasi dan reservation quota secara atomic. CMS tidak boleh menambah/menghapus redemption secara manual.

### 6.2 Banner

Tabel: `ot_banners`.

Field:

- Outlet atau global.
- Title.
- Image URL/upload.
- Action type: `url`, `internal`, `product`, `category`, `voucher`, `promo`.
- Action value.
- Position.
- Sort order.
- Start/end time.
- Status.

Aturan UI:

- `product`: select produk.
- `category`: select kategori.
- `voucher`: select voucher.
- `url`: validasi URL.
- `internal`: select route dari allowlist.
- Jangan meminta operator mengetik ID atau JSON manual.

## 7. Prioritas P1 — Monitoring Operasional

### 7.1 Order Monitor

Read dari:

- `ot_orders`
- `ot_order_items`
- `ot_order_item_modifiers`
- `ot_order_payments`
- `ot_order_status_logs`

Filter:

- Outlet.
- Status order.
- Payment status/mode.
- Bridge status.
- Order number.
- Table.
- Device (masked).
- Date range.

Detail order:

- Snapshot item, variant, modifier, notes.
- Pricing breakdown read-only.
- Payment data read-only.
- Status timeline.
- Bridge status dan POS transaction ID.
- Geofence audit.

Aksi yang diperbolehkan:

- Mark `preparing` → `served` melalui endpoint internal Node.
- Cancel order dengan alasan melalui endpoint internal Node.

Jangan update kolom status langsung dari Eloquent/query CMS. Semua transisi harus lewat endpoint internal Node agar state machine, status log, SSE, dan audit tetap konsisten.

### 7.2 Session Monitor

Tabel: `ot_table_sessions`.

Tampilkan:

- Device ID dimasking.
- Outlet/table.
- Opened/closed time.
- Status.
- Close reason/by.
- Order terkait.

Aksi:

- Close session manual melalui endpoint internal Node.
- Tidak ada hard delete.

### 7.3 Payment Monitor

Tabel: `ot_order_payments`.

Read-only untuk:

- Method.
- Gateway reference.
- Amount.
- Status.
- Expiry/paid time.
- Callback metadata yang aman untuk ditampilkan.

Jangan tampilkan credential atau signature secret. Jangan izinkan edit `gateway_ref`, amount, raw callback, atau paid status.

## 8. Prioritas P2 — Bridge Monitor

CMS dapat menyiapkan UI, tetapi re-bridge belum diaktifkan sebelum keputusan petty cash dan bridge user selesai.

Fitur:

- Filter `pos_bridge_status`: `pending`, `linked`, `failed`.
- POS transaction ID.
- Error terakhir jika nanti disimpan/dikirim Node.
- Tombol re-bridge dengan permission khusus.
- Audit siapa yang meminta re-bridge.

Re-bridge harus memanggil endpoint internal Node. CMS tidak menulis langsung `transactions`/`transaction_items` dan tidak mengedit `pos_transaction_id`.

## 9. Bridge User dan Petty Cash (Masih Terbuka)

User `order-table@system` belum boleh dibuat otomatis sampai ditentukan:

- Name.
- Username.
- Email.
- Role.
- Status.
- Password random kuat.
- Cakupan outlet pada JSON/LONGTEXT `outlet_id`.

Petty cash belum final:

- Petty cash virtual Order Table per outlet (rekomendasi awal).
- Petty cash kasir aktif.
- Model lain dari keuangan/backoffice.

Ejaan aktual:

- `transactions.patty_cash_id` (NOT NULL).
- `shift_sessions.petty_cash_id`.

Jangan aktifkan POS bridge production sebelum keputusan tertulis.

## 10. Permission CMS

Rekomendasi permission:

```text
order-table.settings.view
order-table.settings.update
order-table.tables.view
order-table.tables.manage
order-table.qr.rotate
order-table.payment-methods.view
order-table.payment-methods.manage
order-table.vouchers.view
order-table.vouchers.manage
order-table.banners.view
order-table.banners.manage
order-table.orders.view
order-table.orders.serve
order-table.orders.cancel
order-table.sessions.view
order-table.sessions.close
order-table.bridge.view
order-table.bridge.retry
```

Audit log wajib untuk:

- Pause/buka outlet.
- Perubahan jam/geofence/payment method.
- Generate/rotate/nonaktif QR.
- Create/update/disable voucher dan banner.
- Serve/cancel order.
- Close session.
- Re-bridge.

## 11. Kontrak Integrasi CMS → Node

Untuk aksi status/operasional, CMS memanggil endpoint internal Node (kontrak final menyusul):

```text
POST /api/v1/internal/orders/{id}/serve
POST /api/v1/internal/orders/{id}/cancel
POST /api/v1/internal/sessions/{id}/close
POST /api/v1/internal/orders/{id}/rebridge
```

Endpoint internal tidak memakai credential customer. Gunakan auth aplikasi kasir/backoffice yang terpisah, permission per aksi, idempotency key, dan audit actor.

Sebelum endpoint internal selesai, buat monitor read-only dan tombol disabled/feature-flagged. Jangan mengimplementasikan update langsung sebagai workaround.

## 12. Urutan Implementasi Paralel

1. Outlet settings + geolocation.
2. Dining table + QR generate/rotate/download.
3. Payment method configuration.
4. Voucher CRUD + redemption monitor.
5. Banner CRUD.
6. Order/payment/session monitor read-only.
7. Permission + audit log.
8. Integrasi aksi serve/cancel/close melalui Node.
9. Bridge monitor UI.
10. Re-bridge setelah kontrak dan keputusan bisnis final.

## 13. Definition of Done CMS Tahap Awal

- [ ] Outlet settings CRUD + pause order.
- [ ] Geolocation outlet CRUD dengan validasi.
- [ ] Dining table CRUD, QR generate/rotate/download, soft delete.
- [ ] Payment method CRUD per outlet.
- [ ] Voucher CRUD dengan scope selector dan quota read-only.
- [ ] Banner CRUD dengan action selector.
- [ ] Order/session/payment monitor read-only.
- [ ] Permission terpisah untuk setiap domain.
- [ ] Audit log untuk aksi sensitif.
- [ ] Tidak ada query aplikasi ke `open_bills`/`item_open_bills`.
- [ ] Tidak ada hard delete order/session/payment/redemption.
- [ ] Tidak ada update langsung status/payment/bridge dari CMS.
- [ ] Feature test Laravel untuk permission, validation, QR rotation, dan CRUD konfigurasi.

## 14. Prompt Siap-Tempel untuk Agent Laravel

```text
Baca terlebih dahulu:

1. docs/laravel-cms-parallel-handoff.md
2. docs/laravel-migration-handoff.md
3. docs/order-table-schema-audit-handoff.md
4. docs/system-design.md (§2, §4, §5, §6, §7, §8)

Implementasikan modul CMS Order Table secara bertahap:

P0:
- ot_outlet_settings + geolocation outlet
- ot_dining_tables + QR generate/rotate/download
- ot_payment_methods per outlet

P1:
- voucher dan banner CRUD
- order/session/payment monitor read-only
- permission + audit log

P2:
- bridge monitor UI; aksi re-bridge tetap disabled sampai kontrak Node final

Guardrail:
- Jangan membaca/menulis open_bills dan item_open_bills.
- Jangan menghitung pricing atau mengubah nominal order.
- Jangan memproses payment/webhook/expiry/auto-preparing.
- Jangan menandai QRIS paid secara manual.
- Jangan menulis transactions/transaction_items langsung dari CMS.
- Jangan hard-delete order/session/payment/redemption.
- Aksi serve/cancel/close/rebridge harus melalui endpoint internal Node, bukan update DB langsung.
- Semua aksi sensitif memerlukan permission dan audit log.

Mulai dari P0. Sebelum coding, audit pola CRUD, permission, audit log, QR/image
upload, dan testing yang sudah dipakai repository Laravel. Laporkan rancangan route,
controller/service, policy/permission, form request, dan test sebelum implementasi.
```

---

*Hapus atau arsipkan dokumen temporary ini setelah kontrak CMS/Node final dipindahkan ke dokumentasi permanen.*
