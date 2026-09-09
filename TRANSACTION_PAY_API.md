# Transaction / Pay API (Mobile POS)

Dokumentasi endpoint API untuk transaksi pembayaran (**Checkout / Bayar**) pada aplikasi Android/iOS POS. Endpoint ini dirancang ramah untuk **koneksi latar belakang (background sync)** dan **offline-first queue**.

**Base URL:** `{base_url}/api/v1/transactions/pay`  
**Method:** `POST`  
**Auth:** Wajib menyertakan Sanctum token di header.

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

> **Catatan:**
> - `outlet_id` dan `user_id` diambil **otomatis dari token login user**.
> - Endpoint ini mengimplementasikan **Idempotency** menggunakan `reference_id` (UUID transaksi lokal mobile) untuk mencegah double transaksi saat retry otomatis di background.

---

## Endpoint Specification

### `POST /api/v1/transactions/pay`

### Header Request
| Header | Nilai | Wajib |
|---|---|---|
| `Authorization` | `Bearer {token}` | Ya |
| `Content-Type` | `application/json` | Ya |
| `Accept` | `application/json` | Ya |

---

### Request Body (JSON)

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `reference_id` | string (UUID) | Rekomendasi | ID unik transaksi dari mobile (cth: `UUIDv4`). Berfungsi sebagai **Idempotency Key**. Jika terjadi retry background hit, server tidak akan menduplikasi transaksi melainkan mengembalikan hasil transaksi yang sudah ada. Disimpan ke kolom `reference_id` di tabel `transactions`. |
| `patty_cash_id` | int | Ya | ID Petty Cash / shift kasir yang sedang aktif. |
| `customer_id` | int \| null | Tidak | ID Customer/Member jika ada. Jika diisi, poin, EXP, dan tier membership akan diperbarui serta email dikirimkan. |
| `bill_id` | int \| null | Tidak | ID Open Bill jika transaksi berasal dari open bill. Kirim `0` atau `null` jika direct payment. |
| `split_bill` | boolean | Tidak | `true` jika bayar sebagian dari Open Bill, `false` jika bayar lunas seluruh Open Bill. Default: `false`. |
| `total` | numeric | Ya | Total akhir tagihan transaksi setelah diskon/pajak/rounding. |
| `nominal_bayar` | numeric | Ya | Uang yang dibayarkan oleh pelanggan. |
| `change` | numeric | Ya | Nominal kembalian. |
| `category_payment_id`| int | Tidak | ID Category Payment (default `1` jika tidak dikirim). |
| `tipe_pembayaran` | int \| null | Tidak | ID Payment method yang dipilih. |
| `nama_tipe_pembayaran`| string | Tidak | Nama metode pembayaran (cth: `"Cash"`, `"QRIS"`, `"BCA Debit"`). Default: `"Cash"`. |
| `total_pajak` | array \| null | Tidak | Array rincian pajak (cth: `[{"id":7,"name":"Pajak Restoran","amount":10,"satuan":"%","total":2465}]`). |
| `diskon_all_item` | array \| null | Tidak | Array diskon nota/global (cth: `[{"id":14,"nama":"Diskon Voucher","satuan":"rupiah","value":0}]`), default: `[]`. |
| `rounding` | numeric \| null | Tidak | Nilai pembulatan nominal transaksi. |
| `tanda_rounding` | string \| null | Tidak | Tanda pembulatan (`"+"` atau `"-"`). |
| `potongan_point` | numeric | Tidak | Jumlah poin customer yang ditukarkan/dipakai untuk potongan harga. |
| `catatan_transaksi` | string \| null | Tidak | Catatan umum pada transaksi. |
| `created_at` | string (ISO 8601) | Tidak | Waktu transaksi dibuat di device saat offline (cth: `"2026-09-09T14:30:00Z"`). Jika dikirim, transaksi akan dicatat sesuai waktu asli offline. |
| `items` | array of object | Ya | Minimal 1 item produk yang dibeli. |

#### Struktur Setiap Element di `items`:
| Field Item | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `product_id` | int \| null | Tidak | ID Product. |
| `variant_id` | int \| null | Tidak | ID Variant Product (jika ada variant, stok variant ini akan otomatis di-decrement). |
| `harga` | numeric | Ya | Harga satuan produk. |
| `quantity` | int | Ya | Jumlah item yang dibeli (min: 1). |
| `catatan` | string \| null | Tidak | Catatan per item (cth: `"Level Pedas 2"`, `"Birthday Reward"`, `"Level Reward"`, `"Exp Reward"`). |
| `sales_type_id` | int \| null | Tidak | ID jenis penjualan (Dine In / Take Away). |
| `promo_id` | array \| string \| null | Tidak | Promo ID / array promo (default: `[]`). |
| `reward` | boolean | Tidak | `true` jika item merupakan reward/free item (disimpan sebagai `1`/`0`). Default `false`. |
| `tmp_id` | string \| null | Tidak | Diperlukan jika transaksi berasal dari `split_bill` Open Bill untuk mencocokkan item bill. |
| `discount_id` | array \| null | Tidak | Array objek diskon per item (cth: `[{"id":"19","nama":"Diskon Voucher Event","result":3300,"satuan":"percent","value":15}]`), default: `[]`. |
| `modifier_id` | array \| null | Tidak | Array objek modifier/topping terpilih (cth: `[{"id":"166","nama":"Cup Ice","harga":0}]`), default: `[]`. |

---

### Contoh Payload Request

```json
{
  "reference_id": "7b8e1f5a-e392-491c-b715-d4193b2a95c1",
  "patty_cash_id": 1,
  "customer_id": 10,
  "bill_id": 0,
  "split_bill": false,
  "total": 55000,
  "nominal_bayar": 100000,
  "change": 45000,
  "category_payment_id": 1,
  "tipe_pembayaran": 1,
  "nama_tipe_pembayaran": "Cash",
  "total_pajak": [
    {
      "id": 7,
      "name": "Pajak Restoran",
      "amount": 10,
      "satuan": "%",
      "total": 2465
    }
  ],
  "diskon_all_item": [
    {
      "id": 14,
      "nama": "Diskon Voucher",
      "satuan": "rupiah",
      "value": 0
    }
  ],
  "rounding": 0,
  "tanda_rounding": "+",
  "potongan_point": 0,
  "catatan_transaksi": "Pelanggan reguler",
  "created_at": "2026-09-09T14:30:00Z",
  "items": [
    {
      "product_id": 5,
      "variant_id": 12,
      "harga": 25000,
      "quantity": 2,
      "catatan": "Less ice",
      "sales_type_id": 1,
      "promo_id": null,
      "reward": false,
      "tmp_id": "item-temp-1",
      "discount_id": [
        {
          "id": "19",
          "nama": "Diskon Voucher Event",
          "result": 3300,
          "satuan": "percent",
          "value": 15
        }
      ],
      "modifier_id": [
        {
          "id": "166",
          "nama": "Cup Ice",
          "harga": 0
        }
      ]
    }
  ]
}
```

---

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "id": 124,
  "change": 45000,
  "metode": "Cash",
  "message": "Transaksi Berhasil",
  "pelanggan": {
    "id": 10,
    "name": "John Doe",
    "email": "johndoe@example.com",
    "point": 550,
    "exp": 1200
  },
  "dataStruk": {
    "status": true,
    "transaction": {
      "id": 124,
      "outlet_id": 1,
      "user_id": 3,
      "customer_id": 10,
      "total": 55000,
      "nominal_bayar": 100000,
      "change": 45000,
      "nama_tipe_pembayaran": "Cash",
      "reference_id": "7b8e1f5a-e392-491c-b715-d4193b2a95c1",
      "receipt_number": null,
      "created_at": "2026-09-09T14:30:00.000000Z",
      "tax": []
    },
    "transactionItems": [
      {
        "variant_id": 12,
        "total_count": 2,
        "product_id": 5,
        "catatan": "Less ice",
        "reward_item": false,
        "harga": 25000,
        "modifier": [
          "Extra Shot"
        ],
        "total_transaction": 60000,
        "product": {
          "id": 5,
          "name": "Kopi Susu Gula Aren"
        },
        "variant": {
          "id": 12,
          "name": "Large",
          "harga": 25000
        }
      }
    ],
    "user": {
      "id": 3,
      "name": "Kasir 1"
    },
    "device": "Android",
    "noteReceiptScheduler": [],
    "pelanggan": {
      "id": 10,
      "name": "John Doe"
    }
  }
}
```

---

### Response Error

#### 1. Validasi Input Gagal (HTTP 422)
```json
{
  "message": "The items field is required.",
  "errors": {
    "items": [
      "The items field is required."
    ]
  }
}
```

#### 2. Outlet Tidak Ditemukan Pada Token (HTTP 422)
```json
{
  "status": "error",
  "message": "User tidak memiliki outlet yang terdaftar."
}
```

#### 3. Request Paralel / Double Hit Sedang Berjalan (HTTP 429)
```json
{
  "status": "warning",
  "message": "Transaksi sedang diproses di server. Silakan tunggu."
}
```

---

## Panduan Integrasi Mobile POS (Android/iOS)

### 1. Offline Mode & Background Sync
- Saat kasir menyelesaikan pembayaran tanpa koneksi internet:
  1. Generate `reference_id` lokal via `UUID.randomUUID().toString()`.
  2. Simpan transaksi ke SQLite / Room DB lokal bersama timestamp `created_at`.
  3. Cetak struk offline di printer thermal menggunakan data lokal.
- Ketika koneksi internet terhubung kembali (misal via WorkManager / Background Service):
  1. Kirim transaksi dengan payload di atas ke endpoint `POST /api/v1/transactions/pay`.
  2. Jika koneksi terputus saat request sedang dikirim dan mobile melakukan retry, server mendeteksi `reference_id` yang sama dan tidak akan menduplikasi transaksi ataupun memotong stok ulang.
  3. Update status transaksi di database lokal menjadi `SYNCED`.
