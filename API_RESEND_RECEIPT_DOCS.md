# Dokumentasi API: Resend Receipt (Mobile POS Android)

Dokumentasi ini digunakan sebagai panduan bagi client Mobile POS (Android) untuk mengrimkan ulang struk transaksi via email ke pelanggan.

---

## 1. Spesifikasi Endpoint

- **URL:** `/api/v1/transactions/{id}/resend-receipt`
- **Method:** `POST`
- **Headers:**
  ```http
  Authorization: Bearer <SANCTUM_TOKEN>
  Accept: application/json
  Content-Type: application/json
  ```
- **URL Parameter:**
  - `id`: ID Transaksi (`integer`)

---

## 2. Request Body (JSON)

Client mengirimkan email tujuan penerima struk:

```json
{
  "email": "customer@example.com"
}
```

### Penjelasan Field Request:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `email` | `string` | **Ya** | Alamat email penerima struk transaksi. |

---

## 3. Contoh Response

### Response Berhasil (HTTP 200 OK)
```json
{
  "status": "success",
  "message": "Receipt berhasil dikirim ke customer@example.com"
}
```

### Response Validasi Gagal (HTTP 422 Unprocessable Entity)
```json
{
  "message": "The email field must be a valid email address.",
  "errors": {
    "email": [
      "The email field must be a valid email address."
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
