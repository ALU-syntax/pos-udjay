# Handoff Integrasi Laravel Order Table

## Status Sebenarnya

Backend Laravel untuk Android Kasir sudah memiliki jalur lokal yang dapat diuji: daftar/detail order, scope outlet/device/shift, claim pembayaran atomik, pembayaran berbasis snapshot OT, recovery setelah timeout, guard endpoint POS generic, settle adapter Node, CO/receipt/serve gate, dan FCM outbox/sender HTTP v1. Fitur produksi tetap mati default:

```dotenv
ORDER_TABLE_PAY_AT_CASHIER_ENABLED=false
ORDER_TABLE_FCM_ENABLED=false
```

Node.js tidak ada di repository ini. Node adalah dependency eksternal dan belum boleh dianggap terverifikasi. Tidak ada migration live yang dijalankan dan tidak ada commit dibuat.

## File Dan Migration

- `app/Services/OrderTable/CashierPaymentService.php`: lock, claim, snapshot validation, POS transaction, recovery, heartbeat.
- `app/Http/Controllers/Api/OrderTableCashierController.php`: HTTP API Android, CO, receipt, serve, settle.
- `app/Http/Middleware/OrderTableDevice.php`: Sanctum token, active user, device, shift, petty cash, outlet.
- `app/Services/OrderTable/OrderTableNodeClient.php`: internal API key, actor headers, idempotency, safe upstream mapping.
- `app/Http/Controllers/Api/OrderTableEventController.php`: authenticated Node event ingestion dan event dedup.
- `app/Jobs/SendOrderTablePush.php`, `app/Services/OrderTable/FcmSender.php`: durable delivery job, OAuth JWT RS256, HTTP v1, retry, invalid token cleanup.
- `app/Console/Commands/DispatchOrderTablePush.php`: mengambil outbox ke queue.
- `2026_10_12_000001_create_order_table_cashier_tables.php`: claim dan device binding.
- `2026_10_12_000002_create_order_table_notification_outbox.php`: event dan push delivery outbox.
- `ANDROID_ORDER_TABLE_HANDOFF.md`: kontrak Android detail berbahasa Indonesia, payload, recovery, Firebase, dan Node checklist.

Kedua migration harus dijalankan lewat deployment review biasa. Jangan menjalankan `migrate:fresh`, migration testing, atau tes MySQL pada database utama. Test baru memakai SQLite memory dan membuat skema terisolasi.

## Kontrak Ownership

Laravel memiliki `transactions` dan `transaction_items` ketika kasir membayar `pay_at_cashier`. Laravel tidak mengubah status `ot_orders` atau `ot_order_payments`; Node tetap pemilik status Order Table, QRIS gateway/webhook, SSE customer, expiry/cancel worker, dan perubahan `paid/received`. Android tidak membuat POS transaction dari FCM.

Untuk QRIS langsung, Laravel Android endpoint hanya membaca order. Tidak ada claim atau payment kasir untuk `qris`; bridge QRIS tetap Node-owned. `pay_at_cashier` hanya boleh dikoleksi melalui claim dan payment endpoint khusus.

Tidak ada customer, rewards, open bills, `item_open_bill_id`, atau data loyalty dalam jalur OT. POS generic payment menolak `reference_id` yang cocok dengan `ot_orders.order_no` sebelum validasi generic:

```json
{"status":"error","code":"ORDER_TABLE_PAYMENT_REQUIRED","message":"Gunakan endpoint pembayaran Order Table."}
```

## Model Pembayaran Dan Recovery

Claim mengunci `ot_orders` dan hanya satu row per order. Claim terikat ke user, token/device, active shift, dan outlet. Expiry 120 detik. Heartbeat memperpanjang claim paling jauh sampai `paymentDueAt`, tetapi hanya boleh dipakai saat kasir masih benar-benar memegang alur pembayaran.

Setelah insert `transactions` dan item POS berhasil, `order_table_payment_claims.transaction_id` diisi di transaction database yang sama. Transaksi tersebut menjadi bukti permanen collection. Claim yang expired tidak pernah memberi izin collect kedua. Retry harus memanggil `payment-state` lalu settle memakai ID yang sama. Jika evidence hilang, terhapus, konflik, total berbeda, atau ditemukan lebih dari satu reference, API berhenti dengan `PAYMENT_RECONCILIATION_REQUIRED`.

Tidak ada sistem software yang dapat membuktikan uang fisik sudah atau belum diterima saat device offline tepat ketika lease habis. Heartbeat mengurangi window risiko; SOP harus menghentikan collection kedua sampai rekonsiliasi bila kondisi fisik ambigu.

## Snapshot Dan Pajak

Validasi lokal yang aman:

- product aktif harus berada di outlet order;
- variant wajib ada untuk item OT dan harus `variant_products.product_id` yang sama;
- item tidak cancelled, qty positif dan dibatasi, harga/modifier/discount/tax non-negatif;
- modifier snapshot dihitung dari `ot_order_item_modifiers`, lalu ditulis sebagai JSON POS;
- subtotal dasar dan `modifier_total` dicocokkan dengan item snapshot;
- total payment berasal dari `ot_orders.grand_total`, bukan body Android.

Kode tidak lagi memaksakan persamaan `subtotal + tax_total` universal. System design menyatakan modifier sudah masuk subtotal, sedangkan schema OT tidak memiliki flag tax-inclusive yang versioned. `tax_breakdown` hanya divalidasi format dan diteruskan ke `transactions.total_pajak`. Semantics inclusive/exclusive tax harus dipastikan Node sebelum production.

## FCM Outbox

Node mengirim:

```http
POST /api/v1/internal/order-table/events
X-Internal-Api-Key: {ORDER_TABLE_EVENT_KEY}
```

```json
{"eventId":"uuid","orderId":123,"outletId":11,"type":"order.placed"}
```

Event type yang diizinkan: `order.placed`, `order.settle_requested`, `order.paid`, `order.expired`, `order.cancelled`, `order.served`, `order.bridge_updated`, `order.status_changed`. Update bridge/status setelah paid harus memakai eventId baru agar Android refresh receipt QRIS setelah POS bridge selesai/gagal. Response accepted:

```json
{"status":"success","data":{"eventId":"uuid","accepted":true}}
```

Unique `eventId` membuat retry idempotent. Reuse event ID dengan order/type berbeda menghasilkan `409 EVENT_ID_CONFLICT`. Transaction ingestion mengunci order, menyimpan event, dan snapshot recipient aktif dengan FCM token. Request tidak mengirim FCM langsung.

Android mendaftarkan token:

```http
POST /api/v1/order-table/device/fcm-token
{"fcmToken":"registration-token"}
```

Laravel menyimpan token terenkripsi dan hash. Token terikat access token/device/outlet/shift. Logout dan `DELETE /api/v1/order-table/device/fcm-token` mematikan delivery. `UNREGISTERED` menghapus token; `INVALID_ARGUMENT` tidak otomatis menghapus karena dapat berarti payload rusak. Rotasi token pada device yang sama menonaktifkan registration lama.

`order-table:dispatch-push` mengantrekan delivery; scheduler menjalankannya tiap menit. Worker `queue:work database --queue=order-table` memanggil Firebase HTTP v1. OAuth dibuat dengan JWT RS256 via OpenSSL, tanpa package Firebase tambahan. Transient failures memakai exponential backoff dan status pending; setelah 10 attempts menjadi failed dan dapat diulang:

```sh
php artisan order-table:dispatch-push --retry-failed
```

Firebase deployment: aktifkan FCM API, buat service account minimal-send, simpan JSON di luar repository/public, set `ORDER_TABLE_FCM_PROJECT_ID` dan absolute `ORDER_TABLE_FCM_CREDENTIALS`, pastikan clock/worker/scheduler benar, lalu baru set `ORDER_TABLE_FCM_ENABLED=true`. Jangan kirim private key/OAuth token ke Android atau log.

## Node Handoff Yang Wajib

1. Node harus memakai `eventId` stabil saat mengirim perubahan domain ke ingestion Laravel. Node tidak boleh mengirim event yang sama dengan payload berbeda.
2. Node harus memvalidasi `X-Internal-Api-Key` Laravel, `X-Actor-Id`, `X-Actor-Name`, dan idempotency key; error body gateway/SQL tidak boleh diteruskan.
3. Settle Node harus idempotent untuk order + POS transaction ID dan menolak reference/outlet/amount mismatch.
4. Expiry/cancel worker Node harus koordinasi dengan evidence POS commit. Setelah transaction POS committed, expiry tidak boleh membuka pembayaran kedua.
5. Node harus lock state transition `unpaid -> paid -> received`; cashier mode tidak boleh masuk QRIS bridge dan QRIS tidak boleh masuk cashier settlement.
6. Node harus menjelaskan tax-inclusive/exclusive semantics, reliable sync revision/watermark, dan ownership stock decrement.
7. Node harus memilih satu owner event/outbox; jangan membuat FCM sender kedua yang mengirim event duplikat ke outlet.
8. QRIS gateway, webhook signature, SSE customer, dan distributed settlement tetap eksternal dan tidak diklaim selesai oleh Laravel.

## Error Utama

`401 DEVICE_TOKEN_REQUIRED/INTERNAL_AUTH_REQUIRED`, `403 ACTIVE_SHIFT_REQUIRED/DEVICE_BINDING_MISMATCH`, `409 CASHIER_MODE_REQUIRED/ORDER_CLAIMED/ORDER_NOT_PAYABLE/PAYMENT_NOT_PENDING/CLAIM_EXPIRED_OR_NOT_OWNED/PAYMENT_RECONCILIATION_REQUIRED/ORDER_NOT_PAID/POS_TRANSACTION_REQUIRED`, `422 SNAPSHOT_INVALID/SNAPSHOT_MAPPING_INVALID`, dan `503 ORDER_TABLE_UNAVAILABLE/ORDER_TABLE_RETRY_REQUIRED` dipetakan tanpa SQL, stack trace, token, atau upstream secret. Android harus refresh state sebelum retry; payment POS yang sudah commit selalu retry settle dengan transaction ID lama.

## Verifikasi Aktual

Perintah yang dijalankan dengan Herd PHP `8.2.25`:

```sh
DB_CONNECTION=sqlite DB_DATABASE=:memory: \
  "/Users/mac/Library/Application Support/Herd/bin/php82" vendor/bin/phpunit \
  tests/Feature/OrderTable/CashierHttpTest.php \
  tests/Feature/OrderTable/OrderTableFcmTest.php \
  tests/Feature/OrderTable/CashierPaymentIsolationTest.php
```

Suite mencakup generic guard HTTP, successful cash/non-cash payment, POS replay, variant/mapping/arithmetic failure, claim competitor, heartbeat/expiry, outlet/shift/auth, both modes/pagination, paid gates, Node headers/error redaction, event auth/dedup/conflict, encrypted FCM registration, queue, OAuth signing, fake HTTP v1 success/retry, invalid-token cleanup, logout, token rotation, and active-recipient filtering.

SQLite tidak membuktikan parallel lock behavior InnoDB. Full Node contract, real Firebase delivery, live migration, queue supervisor, and production reconciliation remain explicit release checks.

Hasil aktual terakhir: **31 tests / 206 assertions lulus**. Claim menyimpan fingerprint snapshot; pay/heartbeat menolak perubahan snapshot dengan `409 SNAPSHOT_CHANGED`. FCM bersifat at-least-once dan Android wajib dedup `eventId`.
