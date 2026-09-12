# Dokumentasi API: Refund Transaksi (Mobile POS Android)

Dokumentasi ini digunakan sebagai panduan bagi client Mobile POS (Android) untuk memproses pengembalian dana (refund) transaksi atau sebagian item pesanan.

---

## 1. Spesifikasi Endpoint

- **URL:** `/api/v1/transactions/refund`
- **Method:** `POST`
- **Headers:**
  ```http
  Authorization: Bearer <SANCTUM_TOKEN>
  Accept: application/json
  Content-Type: application/json
  ```

---

## 2. Request Body (JSON)

```json
{
  "transaction_id": 128,
  "payment_method": "Cash",
  "nominal_refund": 36000,
  "catatan": "Pelanggan salah pesan item",
  "list_item": [
    {
      "variant_id": 25,
      "quantity": 2,
      "harga": 18000,
      "catatan": "Less sugar, extra ice",
      "modifier": [],
      "discount": []
    }
  ]
}
```

### Penjelasan Field Request:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `transaction_id` | `integer` | **Ya** | ID transaksi yang akan di-refund. |
| `payment_method` | `string` | **Ya** | Metode refund (contoh: "Cash", "Transfer", "QRIS"). |
| `nominal_refund` | `numeric` | **Ya** | Total uang yang dikembalikan ke pelanggan. |
| `catatan` | `string` | **Ya** | Alasan / catatan pengajuan refund. |
| `list_item` | `array` | **Ya** | Daftar item yang ingin di-refund. Bisa berupa array of objects atau JSON string. |
| `list_item.*.variant_id` | `integer` | Tidak* | ID varian produk yang di-refund (wajib untuk item berbasis katalog). |
| `list_item.*.quantity` | `integer` | **Ya** | Jumlah kuantitas item yang di-refund. |
| `list_item.*.harga` | `numeric` | Tidak* | Harga item (terutama digunakan jika item kustom/non-varian). |
| `list_item.*.catatan` | `string` | Tidak | Catatan pada item saat transaksi dibuat. |
| `list_item.*.modifier` | `array`/`string` | Tidak | Modifier pada item transaksi. |
| `list_item.*.discount` | `array`/`string` | Tidak | Diskon pada item transaksi. |

---

## 3. Contoh Response

### Response Berhasil (HTTP 200 OK)
```json
{
  "status": "success",
  "message": "Refund berhasil diproses.",
  "transaction": {
    "id": 128,
    "outlet_id": 1,
    "user_id": 3,
    "total": 36000,
    "created_time": "14:30",
    "created_tanggal": "11-09-2026",
    "item_transaction": [
      {
        "id": 340,
        "transaction_id": 128,
        "variant_id": 25,
        "refund_at": "2026-09-11 14:45:00",
        "refund_transaction_id": 12,
        "product": {
          "id": 10,
          "nama_product": "Kopi Susu Gula Aren"
        },
        "variant": {
          "id": 25,
          "name": "Large",
          "harga": 18000
        }
      }
    ],
    "refund_transactions": [
      {
        "id": 12,
        "transaction_id": 128,
        "payment_method": "Cash",
        "nominal_refund": 36000,
        "catatan": "Pelanggan salah pesan item",
        "created_time": "14:45",
        "created_tanggal": "11-09-2026"
      }
    ]
  }
}
```

### Response Validasi Gagal (HTTP 422 Unprocessable Entity)
```json
{
  "message": "The transaction id field is required.",
  "errors": {
    "transaction_id": [
      "The transaction id field is required."
    ]
  }
}
```

### Response Transaksi Tidak Ditemukan (HTTP 404 Not Found)
```json
{
  "status": "error",
  "message": "Transaksi tidak ditemukan."
}
```
