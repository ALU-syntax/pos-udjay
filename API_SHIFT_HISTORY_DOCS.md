# Dokumentasi API: History Shift (Mobile POS Android)

Dokumentasi ini digunakan sebagai panduan bagi client Android untuk mengambil daftar riwayat shift / petty cash pada outlet yang aktif.

---

## 1. Spesifikasi Endpoint

- **URL:** `/api/v1/shift/history`
- **Method:** `GET`
- **Headers:**
  ```http
  Authorization: Bearer <SANCTUM_TOKEN>
  Accept: application/json
  ```
- **Query Parameters (Opsional):**
  - `limit`: Jumlah data per halaman (default: `30`, max: `100`).
  - `page`: Nomor halaman pagination (default: `1`).

---

## 2. Contoh Response

### Response Berhasil (HTTP 200 OK)
```json
{
  "status": "success",
  "data": [
    {
      "id": 14,
      "outlet_id": "1",
      "amount_awal": 100000,
      "amount_akhir": 250000,
      "user_started": {
        "id": 3,
        "name": "Budi Kasir"
      },
      "user_ended": {
        "id": 3,
        "name": "Budi Kasir"
      },
      "open": "2026-09-11 08:00:00",
      "close": "2026-09-11 16:00:00",
      "is_active": false,
      "status_label": "Selesai",
      "created_at": "2026-09-11T08:00:00.000000Z"
    },
    {
      "id": 15,
      "outlet_id": "1",
      "amount_awal": 100000,
      "amount_akhir": null,
      "user_started": {
        "id": 4,
        "name": "Siti Kasir"
      },
      "user_ended": null,
      "open": "2026-09-11 16:05:00",
      "close": null,
      "is_active": true,
      "status_label": "Masih Berjalan",
      "created_at": "2026-09-11T16:05:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 30,
    "total": 2
  }
}
```

### Penjelasan Field Data:
- `id`: ID shift / petty cash.
- `outlet_id`: ID outlet tempat shift berjalan.
- `amount_awal`: Saldo modal awal kasir.
- `amount_akhir`: Saldo akhir saat shift ditutup (`null` jika masih berjalan).
- `user_started`: Informasi kasir yang membuka shift.
- `user_ended`: Informasi kasir yang menutup shift (`null` jika belum ditutup).
- `open`: Waktu shift dibuka.
- `close`: Waktu shift ditutup (`null` jika masih berjalan).
- `is_active`: `true` jika shift sedang berjalan, `false` jika sudah selesai.
- `status_label`: Label status siap pakai ("Masih Berjalan" atau "Selesai").

---

## 3. Spesifikasi Endpoint: Detail History Shift

Endpoint ini digunakan untuk melihat rincian lengkap dari shift tertentu (informasi shift, ringkasan per metode pembayaran, dan rincian item yang terjual).

- **URL:** `/api/v1/shift/history/{id}`
- **Method:** `GET`
- **Headers:**
  ```http
  Authorization: Bearer <SANCTUM_TOKEN>
  Accept: application/json
  ```

### Contoh Response (HTTP 200 OK)
```json
{
  "status": "success",
  "data": {
    "shift": {
      "id": 14,
      "outlet_id": "1",
      "amount_awal": 100000,
      "amount_akhir": 250000,
      "user_started": {
        "id": 3,
        "name": "Budi Kasir"
      },
      "user_ended": {
        "id": 3,
        "name": "Budi Kasir"
      },
      "open": "2026-09-11 08:00:00",
      "close": "2026-09-11 16:00:00",
      "is_active": false,
      "status_label": "Selesai",
      "created_at": "2026-09-11T08:00:00.000000Z"
    },
    "payment_summary": [
      {
        "category_id": 1,
        "category_name": "Cash",
        "total_amount": 180000,
        "transaction_count": 5,
        "payments": [
          {
            "id": 1,
            "name": "Tunai",
            "transaction_count": 5,
            "total_amount": 180000
          }
        ]
      },
      {
        "category_id": 2,
        "category_name": "E-Wallet",
        "total_amount": 70000,
        "transaction_count": 2,
        "payments": [
          {
            "id": 2,
            "name": "QRIS",
            "transaction_count": 2,
            "total_amount": 70000
          }
        ]
      }
    ],
    "sold_items": [
      {
        "variant_id": 10,
        "name": "Kopi Susu Large",
        "harga": 18000,
        "total_transaction": 4,
        "total_amount": 72000,
        "product": {
          "id": 5,
          "nama_product": "Kopi Susu",
          "category": {
            "id": 2,
            "name": "Coffee"
          }
        }
      }
    ],
    "raw": {
      "listCategoryPayment": [ ... ]
    }
  }
}
```

