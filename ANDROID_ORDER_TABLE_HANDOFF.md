# Handoff Order Table Android dan Laravel

Dokumen ini menggambarkan implementasi yang benar-benar ada di repository ini. Fitur bayar di kasir dan pengiriman FCM tetap **nonaktif secara default**. Migration tidak dijalankan ke database live. Node.js tidak tersedia di repository ini, sehingga kontrak eksternal Node dan deployment Firebase tetap harus divalidasi oleh pemilik Node.

## Header Umum

Semua endpoint Laravel memakai prefix `/api/v1`, `Authorization: Bearer {sanctumToken}`, dan untuk endpoint Order Table:

```http
X-Device-Id: android-device-a
X-Shift-Session-Id: 901
Accept: application/json
Content-Type: application/json
```

Device harus cocok dengan `shift_sessions.android_id`; shift harus `ACTIVE`, milik user token, berada di outlet token, dan petty cash-nya masih terbuka. Outlet tidak boleh dipilih dari body/query untuk memperluas scope.

## Konfigurasi Laravel

Tambahkan ke environment server, tanpa memasukkan secret ke Android atau repository:

```dotenv
ORDER_TABLE_PAY_AT_CASHIER_ENABLED=false
ORDER_TABLE_NODE_ENABLED=false
ORDER_TABLE_NODE_URL=
ORDER_TABLE_NODE_TOKEN=
ORDER_TABLE_NODE_INTERNAL_API_KEY=
ORDER_TABLE_NODE_TIMEOUT=15
ORDER_TABLE_CASH_CATEGORY_ID=1
ORDER_TABLE_FCM_ENABLED=false
ORDER_TABLE_FCM_PROJECT_ID=
ORDER_TABLE_FCM_CREDENTIALS=/absolute/path/firebase-service-account.json
ORDER_TABLE_EVENT_KEY=
ORDER_TABLE_FCM_QUEUE_CONNECTION=database
ORDER_TABLE_FCM_QUEUE=order-table
```

`ORDER_TABLE_NODE_INTERNAL_API_KEY` hanya berada di server Laravel dan dikirim sebagai `X-Internal-Api-Key` ke Node. `ORDER_TABLE_EVENT_KEY` adalah key terpisah untuk request Node ke endpoint ingestion Laravel. File Firebase harus di luar public web root, readable oleh worker, dan tidak ikut backup/source control tanpa perlindungan.

Jalankan migration berikut hanya melalui prosedur deployment yang direview:

```text
2026_10_12_000001_create_order_table_cashier_tables.php
2026_10_12_000002_create_order_table_notification_outbox.php
```

Migration kedua membuat `order_table_events` dan `order_table_push_deliveries`. `QUEUE_CONNECTION=database` membutuhkan migration jobs standar project dan worker queue aktif.

## Login Dan Shift

```http
POST /api/v1/login
```

```json
{"username":"kasir01","pin":"123456","outlet_id":11}
```

Response sukses:

```json
{"status":"success","data":{"token":"sanctum-token","user":{"id":7,"name":"Kasir","username":"kasir01","outlet_id":11}}}
```

Setelah petty cash aktif, Android mengambil/membuat shift melalui endpoint shift yang sudah ada, lalu memakai `android_id` dan ID shift dari response. Logout:

```http
POST /api/v1/logout
```

Logout menghapus binding device/Firebase token untuk access token tersebut.

## Daftar Order

```http
GET /api/v1/order-table/orders?status=placed&payment_mode=pay_at_cashier&limit=50
```

`payment_mode` boleh `qris` atau `pay_at_cashier`; bila dihilangkan, kedua mode dikembalikan. `status` boleh `placed`, `received`, `preparing`, `served`, `expired`, `cancelled`, atau `draft`. `updated_since` inclusive. Untuk sync reliable gunakan `sync_until` yang dikembalikan response dan ulangi overlap berdasarkan `orderId`; cursor bukan snapshot lintas transaksi database.

Response:

```json
{
  "status":"success",
  "data":[
    {
      "id":123,"orderId":123,"orderNo":"OT-ABC123","outletId":11,
      "status":"placed","paymentMode":"pay_at_cashier","paymentStatus":"unpaid",
      "subtotal":85000,"discountItemTotal":0,"voucherDiscount":0,"modifierTotal":5000,
      "taxTotal":8500,"taxBreakdown":[{"id":8,"name":"Pajak Restoran","amount":10,"total":8500}],
      "rounding":0,"grandTotal":93500,"posBridgeStatus":"pending","posTransactionId":null,
      "table":{"id":4,"code":"M-14","name":"Meja 14"},
      "items":[{"id":9,"productId":20,"variantId":31,"productName":"Kopi","variantName":"Large","unitPrice":40000,"qty":2,"modifierTotal":2500,"lineTotal":85000,"notes":null,"modifiers":[{"modifierId":164,"name":"Extra","harga":2500,"qty":1}]}],
      "payments":[{"id":55,"method":"cashier","amount":93500,"status":"pending","expiresAt":"2026-10-12T13:00:00+07:00","paidAt":null}],
      "placedAt":"2026-10-12T12:00:00+07:00","paymentDueAt":"2026-10-12T13:00:00+07:00","paidAt":null,"updatedAt":"2026-10-12T12:00:00+07:00"
    }
  ],
  "meta":{"server_time":"2026-10-12T12:01:00+07:00","sync_until":"2026-10-12T12:00:58+07:00","next_cursor":null,"has_more":false}
}
```

`GET /api/v1/order-table/orders/{id}` mengembalikan object order yang sama. `productId`, `variantId`, harga, modifier, pajak, diskon, dan grand total adalah snapshot server. Android tidak boleh mencari ulang item berdasarkan nama atau mengirim ulang total/item untuk mengubah snapshot.

## Claim Dan Pembayaran

Claim:

```http
POST /api/v1/order-table/orders/123/claim
{}
```

Response:

```json
{"status":"success","data":{"orderId":123,"posTransactionId":null,"paymentStatus":"unpaid","state":"claimed","collectionAllowed":true,"claimToken":"claim-uuid","claimExpiresAt":"2026-10-12T12:03:00+07:00","claimOwned":true,"settleRequired":false,"receiptAvailable":false,"coAvailable":false,"posBridgeStatus":"pending"}}
```

Claim atomic per order, satu claim aktif, default 120 detik, dan terikat user/device/shift/outlet. Fingerprint snapshot tersimpan pada claim; perubahan snapshot sebelum pay/heartbeat menghasilkan `409 SNAPSHOT_CHANGED`. Refresh dan konfirmasi ulang, kemudian release/acquire hanya bila uang belum diterima. Device lain mendapat `ORDER_CLAIMED`. Lepas claim:

```http
POST /api/v1/order-table/orders/123/claim
{"release":true,"claimToken":"claim-uuid"}
```

Heartbeat sebelum kasir selesai menerima uang:

```http
POST /api/v1/order-table/orders/123/claim/heartbeat
{"claimToken":"claim-uuid"}
```

Heartbeat memperpanjang claim yang masih aktif dan dimiliki device, paling jauh sampai `paymentDueAt`. Kirim selama kasir masih menangani pembayaran; heartbeat bukan bukti uang diterima dan tidak menghidupkan claim expired. Jika lease habis atau koneksi putus ketika penerimaan uang/settlement ambigu, hentikan collection, periksa `payment-state`, dan rekonsiliasi sebelum claim atau pembayaran baru. Jangan menerima uang kedua hanya karena claim lama expired.

Bayar:

```http
POST /api/v1/order-table/orders/123/pay
{"claimToken":"claim-uuid","paymentId":null,"nominalBayar":100000}
```

`paymentId:null` berarti kategori cash yang dikonfigurasi `ORDER_TABLE_CASH_CATEGORY_ID`. Bila metode POS nyata punya row `payments`, kirim `paymentId` integer aktif. Server membuat transaksi POS dari snapshot, memakai `reference_id=OT-ABC123`, meng-expand satu row `transaction_items` per unit, mengisi modifier JSON snapshot, dan mengurangi stok variant. Customer, rewards, open bills, dan `item_open_bill_id` tidak dipakai.

Response sukses:

```json
{"status":"success","data":{"orderId":123,"posTransactionId":582700,"paymentStatus":"unpaid","state":"pos_committed","collectionAllowed":false,"claimToken":null,"claimExpiresAt":"2026-10-12T12:03:00+07:00","claimOwned":true,"settleRequired":true,"receiptAvailable":true,"coAvailable":false,"posBridgeStatus":"pending"}}
```

Setelah commit POS, claim expiry tidak pernah membuka collection kedua. Jika request timeout, panggil payment-state, jangan membuat transaksi kedua:

```http
GET /api/v1/order-table/orders/123/payment-state
```

Lalu settle dengan ID yang sama:

```http
POST /api/v1/order-table/orders/123/settle
{"posTransactionId":582700}
```

Laravel hanya memvalidasi reference/outlet/nominal dan meneruskan request ke Node. Node tetap pemilik status `ot_orders` dan `ot_order_payments`; transaksi POS dan perubahan Node bukan distributed transaction.

## CO, Receipt, Dan Serve

CO hanya setelah Order Table `paymentStatus=paid`:

```http
GET /api/v1/order-table/orders/123/co
```

Receipt hanya jika transaksi POS lokal tersedia:

```http
GET /api/v1/order-table/orders/123/receipt
```

Response ringkas:

```json
{"status":"success","data":{"order":{"orderId":123,"status":"received","paymentStatus":"paid"},"transaction":{"id":582700,"reference_id":"OT-ABC123","total":93500,"nominal_bayar":100000,"change":6500,"nama_tipe_pembayaran":"Cash"},"items":[{"product_id":20,"variant_id":31,"harga":40000,"modifier_id":"[{\"id\":164,\"nama\":\"Extra\",\"harga\":2500}]"}]}}
```

Serve hanya menerima `paid` dengan status `received`, `preparing`, atau `served`:

```http
POST /api/v1/order-table/orders/123/serve
{}
```

Serve `served` adalah idempotent. Audit serve terpisah dari payment claim. Node harus tetap menolak serve unpaid di bawah lock sendiri.

### Routing Printer

Snapshot OT dan payload item tidak memiliki snapshot kategori atau tujuan printer. Jangan menebak berdasarkan nama, variant, atau urutan item. Android boleh memakai mapping `productId` dari katalog Room tersinkron dan konfigurasi printer lokal yang tersedia; ini bukan kategori historis snapshot. Jika mapping tidak ada, gunakan fallback manual yang disetujui operasional atau tahan cetak dengan peringatan. Laravel tidak menetapkan printer default/routing dapur-bar. Pencetakan tetap memakai renderer/printer Android; `dataStruk` tidak diwajibkan.

## Error Recovery

```json
{"status":"error","code":"ORDER_CLAIMED","message":"ORDER_CLAIMED"}
```

| HTTP/code | Tindakan |
|---|---|
| 401 `DEVICE_TOKEN_REQUIRED`, `INTERNAL_AUTH_REQUIRED` | Login/request internal ulang |
| 403 `ACTIVE_SHIFT_REQUIRED`, `DEVICE_BINDING_MISMATCH` | Pilih shift/device yang sah |
| 409 `CASHIER_MODE_REQUIRED` | QRIS tidak boleh dibayar lewat kasir |
| 409 `ORDER_CLAIMED` | Refresh payment-state, jangan collect kedua |
| 409 `ORDER_NOT_PAYABLE`, `PAYMENT_NOT_PENDING` | Refresh order; jangan retry buta |
| 409 `CLAIM_EXPIRED_OR_NOT_OWNED` | Claim baru hanya bila uang belum diterima |
| 409 `PAYMENT_RECONCILIATION_REQUIRED` | Hentikan collection dan audit POS |
| 409 `SNAPSHOT_CHANGED` | Snapshot berubah setelah claim; refresh dan konfirmasi ulang tanpa collection kedua |
| 409 `FCM_TOKEN_IN_USE` | Token Firebase terikat device lain; jangan pindahkan secara paksa |
| 409 `CLAIM_NOT_OWNED`, `ORDER_NOT_SERVEABLE` | Tolak release/serve, refresh state |
| 422 `DEVICE_CONTEXT_REQUIRED`, `PAYMENT_INPUT_INVALID` | Perbaiki header/device atau metode/nominal |
| 503 `FCM_DISABLED`, `CASHIER_PAYMENT_DISABLED` | Fitur belum diaktifkan di server |
| 409 `ORDER_NOT_PAID`, `POS_TRANSACTION_REQUIRED` | Status/receipt belum siap |
| 422 `SNAPSHOT_INVALID`, `SNAPSHOT_MAPPING_INVALID` | Jangan ubah payload atau retry; eskalasi Node/data |
| 503 `ORDER_TABLE_UNAVAILABLE`, `ORDER_TABLE_RETRY_REQUIRED` | Untuk transaksi yang sudah commit, retry settle dengan ID sama |

Generic `POST /api/v1/transactions/pay` menolak `reference_id` yang sudah menjadi `ot_orders.order_no` dengan `409 ORDER_TABLE_PAYMENT_REQUIRED`, sebelum validasi checkout generic. POS normal tetap memakai endpoint lama.

## FCM MVP Laravel

### Node ke Laravel

```http
POST /api/v1/internal/order-table/events
X-Internal-Api-Key: {ORDER_TABLE_EVENT_KEY}
Content-Type: application/json
```

```json
{"eventId":"uuid","orderId":123,"outletId":11,"type":"order.placed"}
```

Type yang diterima: `order.placed`, `order.settle_requested`, `order.paid`, `order.expired`, `order.cancelled`, `order.served`, `order.bridge_updated`, `order.status_changed`. Perubahan bridge/status setelah paid memakai eventId baru, khususnya QRIS yang paid sebelum POS bridge selesai. Android refresh detail/payment-state/receipt availability; paid tidak berarti receipt POS sudah ada. `eventId` deduplicated secara unique; event ID yang sama dengan isi berbeda menjadi `409 EVENT_ID_CONFLICT`. Laravel lock order, menyimpan event dan snapshot recipient dalam transaction, lalu response:

```json
{"status":"success","data":{"eventId":"uuid","accepted":true}}
```

FCM tidak dikirim dalam request Node. Outbox menjaga event walaupun worker mati.

### Android Token

```http
POST /api/v1/order-table/device/fcm-token
{"fcmToken":"firebase-registration-token"}
```

```json
{"status":"success","data":{"registered":true}}
```

Token disimpan terenkripsi, hash digunakan untuk dedup, terikat Sanctum token/device/shift/outlet, dan token invalid `UNREGISTERED` dihapus. Logout atau:

```http
DELETE /api/v1/order-table/device/fcm-token
```

akan menonaktifkan delivery. Android harus memperlakukan FCM sebagai wake-up hint dan melakukan GET queue/detail; payload push bukan source of truth.

### Firebase HTTP v1 Setup

1. Buat/confirm Firebase project dan aktifkan Firebase Cloud Messaging API.
2. Buat service account dengan izin minimal mengirim FCM pada project tersebut.
3. Simpan JSON service account di luar repository/public directory dengan permission worker-only.
4. Set `ORDER_TABLE_FCM_PROJECT_ID`, `ORDER_TABLE_FCM_CREDENTIALS`, dan `ORDER_TABLE_FCM_ENABLED=true` hanya setelah queue worker siap.
5. Jalankan `php artisan queue:work database --queue=order-table` pada worker terpisah.
6. Jalankan scheduler Laravel sehingga `order-table:dispatch-push` tiap menit, atau jalankan command oleh supervisor.
7. Pastikan clock server benar; sender membuat JWT RS256 dengan `openssl`, menukar OAuth token, lalu POST ke `https://fcm.googleapis.com/v1/projects/{project}/messages:send`.
8. Jangan masukkan private key atau OAuth credential ke FCM payload/log.

Sender me-retry transient errors dengan exponential backoff, menghapus token hanya saat Firebase mengembalikan `UNREGISTERED`, dan mempertahankan token pada `INVALID_ARGUMENT` karena bisa berarti payload salah. Failed delivery dapat direqueue dengan `php artisan order-table:dispatch-push --retry-failed`.

## Kewajiban Node Yang Belum Bisa Dikerjakan Di Repository Ini

- Implementasi Node harus POST event di atas dengan eventId stabil setelah perubahan domain, bukan membuat transaksi Android dari FCM.
- Node harus memvalidasi `X-Internal-Api-Key` dari Laravel settle dan actor headers, idempotent untuk pasangan order/POS transaction, serta tidak mengembalikan SQL/gateway secret.
- Worker expiry/cancel Node harus berkoordinasi dengan bukti `order_table_payment_claims.transaction_id`/POS reference; setelah POS commit tidak boleh membuka second collection.
- Node harus menjaga lock/status transition `unpaid -> paid -> received`, QRIS webhook/bridge hanya untuk `qris`, dan tidak bridge `pay_at_cashier`.
- Node harus menyediakan event outbox/retry atau memanggil ingestion Laravel yang memiliki outbox ini; ownership event tidak boleh digandakan.
- Node harus mengonfirmasi apakah `tax_total` inclusive atau exclusive. Laravel tidak lagi memaksakan formula `subtotal - discount - voucher + modifier + tax + rounding`; design/schema hanya membuktikan modifier sudah masuk subtotal dan tidak menyediakan tax-inclusion flag. Laravel memvalidasi snapshot non-negatif, mapping variant, modifier total, subtotal dasar, dan format tax breakdown, tetapi tidak menghitung ulang tax yang semantics-nya belum versioned.
- Implementasi FCM Node, SSE customer, QRIS gateway, dan distributed settlement tetap dependency eksternal.

## Verifikasi Aktual

Dengan Herd PHP `8.2.25`, tanpa database live:

```sh
DB_CONNECTION=sqlite DB_DATABASE=:memory: php82 vendor/bin/phpunit \
  tests/Feature/OrderTable/CashierHttpTest.php \
  tests/Feature/OrderTable/OrderTableFcmTest.php \
  tests/Feature/OrderTable/CashierPaymentIsolationTest.php
```

Test mencakup guard generic HTTP, payment cash/non-cash snapshot, variant wajib, arithmetic/mapping failure tanpa POS write, claim competitor, heartbeat/expiry, outlet/shift/auth gates, pagination dua mode, paid-only outputs, Node header/error redaction, FCM event auth/dedup/conflict, queue dispatch, OAuth RS256, HTTP fake delivery, retry, invalid-token cleanup, logout, token rotation, dan recipient filtering.

SQLite tidak membuktikan lock concurrency InnoDB. Parallel MySQL test, full production Node contract, real Firebase delivery, migration deployment, dan worker supervision tetap release checks. Tidak ada claim bahwa dependency Node sudah terimplementasi.

Hasil suite terakhir: **31 tests, 206 assertions, semua lulus**. Test juga memaksa kegagalan insert POS dan outbox dan membuktikan rollback stock/transaction/event, retry aman, fingerprint snapshot, lease worker yang ditinggalkan, serta event tanpa recipient tidak ditarget ulang saat replay.

Delivery FCM bersifat at-least-once: crash setelah Firebase menerima pesan tetapi sebelum outbox ditandai sent dapat menghasilkan push ulang. Android dedup berdasarkan `eventId`. Retry maksimum 10 delivery attempts; penjadwalan backoff 60 detik hingga 3600 detik. Worker memeriksa ulang token, user, outlet, shift, petty cash dan hash registration tepat sebelum kirim. Logout/rotasi setelah HTTP send dimulai tidak bisa membatalkan pesan yang sudah in-flight.

Sync: simpan `meta.sync_until` pada page pertama dan kirim nilai sama pada semua cursor page. Setelah page terakhir, gunakan watermark tersebut sebagai `updated_since` inclusive pada siklus berikutnya; jangan memakai `server_time`. Cutoff default mundur dua detik. Jaminan ini membutuhkan Node memperbarui `updated_at` secara monoton pada setiap perubahan dan tidak menghapus order secara fisik. Update backdated, transaksi Node panjang yang commit terlambat, atau hard delete tetap membutuhkan full reconciliation/versioned change-feed Node.
