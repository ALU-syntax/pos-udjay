# Dokumentasi API: Simpan Open Bill (Mobile POS Android)

Dokumentasi ini ditujukan sebagai panduan integrasi bagi client Mobile POS (Android) untuk menyimpan open bill baru.

---

## 1. Spesifikasi Endpoint

- **URL:** `/api/v1/open-bills`
- **Method:** `POST`
- **Headers:**
  ```http
  Authorization: Bearer <SANCTUM_TOKEN>
  Accept: application/json
  Content-Type: application/json
  ```

---

## 2. Struktur Request Body (JSON)

Client Android disarankan mengirimkan payload dalam format JSON terstruktur berikut:

```json
{
  "name": "Meja 5",
  "customer_id": 12,
  "items": [
    {
      "product_id": 10,
      "variant_id": 25,
      "nama_product": "Kopi Susu Gula Aren",
      "nama_variant": "Large",
      "harga": 18000,
      "quantity": 2,
      "result_total": 36000,
      "catatan": "Less sugar, extra ice",
      "sales_type": "Dine In",
      "tmp_id": "tmp_1726058400_abc1",
      "exclude_tax": false,
      "diskon": [
        {
          "id": 1,
          "nama": "Diskon Member",
          "potongan": 2000
        }
      ],
      "modifier": [
        {
          "id": 5,
          "nama": "Extra Shot",
          "harga": 4000
        }
      ],
      "pilihan": [],
      "promo": []
    }
  ]
}
```

### Penjelasan Field Request:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `name` | `string` | **Ya** | Nama/label bill (misal: "Meja 5", "Pelanggan Budi"). |
| `customer_id` | `integer` | Tidak | ID Customer jika bill dikaitkan ke customer / member. |
| `items` | `array` | **Ya** | List item/produk yang ada di keranjang. |
| `items.*.product_id` | `integer`/`string` | Tidak* | ID Produk (*jika tidak dikirim nama produk, ID ini akan digunakan untuk resolve nama). |
| `items.*.variant_id` | `integer`/`string` | Tidak* | ID Varian Produk. |
| `items.*.nama_product` | `string` | Tidak | Nama produk. |
| `items.*.nama_variant` | `string` | Tidak | Nama varian produk. |
| `items.*.harga` | `integer`/`numeric` | **Ya** | Harga satuan produk/varian. |
| `items.*.quantity` | `integer` | **Ya** | Jumlah item yang dipesan (minimal 1). |
| `items.*.result_total` | `integer`/`numeric` | Tidak | Total harga item (jika kosong, dihitung otomatis dari `harga * quantity`). |
| `items.*.catatan` | `string` | Tidak | Catatan khusus pesanan item. |
| `items.*.sales_type` | `string` | Tidak | Tipe penjualan (misal: "Dine In", "Take Away"). |
| `items.*.tmp_id` | `string` | Tidak | Unique temporary ID item dari client mobile. |
| `items.*.exclude_tax` | `boolean` | Tidak | Default: `false`. |
| `items.*.diskon` | `array`/`json` | Tidak | Daftar diskon item. |
| `items.*.modifier` | `array`/`json` | Tidak | Daftar modifier/topping tambahan. |
| `items.*.pilihan` | `array`/`json` | Tidak | Daftar pilihan/opsi item. |
| `items.*.promo` | `array`/`json` | Tidak | Daftar promo yang diterapkan pada item. |

---

## 3. Kompatibilitas Format Legacy (Form-Data / Parallel Array)

Endpoint ini juga mendukung format request legacy jika client menggunakan `multipart/form-data` dengan array paralel:

- `name`: String
- `customer_id`: Integer
- `tmpId[]`: Array String
- `idProduct[]`: Array ID
- `idVariant[]`: Array ID
- `namaProduct[]`: Array String
- `namaVariant[]`: Array String
- `harga[]`: Array Number
- `quantity[]`: Array Number
- `resultTotal[]`: Array Number
- `catatan[]`: Array String
- `salesType[]`: Array String
- `exclude_tax[]`: Array Boolean
- `diskon[]`: Array JSON string
- `modifier[]`: Array JSON string
- `pilihan[]`: Array JSON string
- `promo[]`: Array JSON string

---

## 4. Contoh Response

### Response Berhasil (HTTP 201 Created)
```json
{
  "status": "success",
  "message": "Open bill berhasil disimpan.",
  "data": {
    "id": 45,
    "name": "Meja 5",
    "queue_order": 1,
    "customer": {
      "id": 12,
      "name": "John Doe"
    },
    "user": {
      "id": 3,
      "name": "Kasir 1"
    },
    "total": 36000,
    "created_at": "2026-09-11T17:45:00.000000Z",
    "created_at_human": "1 second ago",
    "items": [
      {
        "id": 120,
        "open_bill_id": 45,
        "tmp_id": "tmp_1726058400_abc1",
        "product_id": "10",
        "variant_id": "25",
        "nama_product": "Kopi Susu Gula Aren",
        "nama_variant": "Large",
        "harga": 18000,
        "quantity": 2,
        "qty_terbayar": 0,
        "result_total": 36000,
        "catatan": "Less sugar, extra ice",
        "exclude_tax": false,
        "sales_type": "Dine In",
        "diskon": [
          {
            "id": 1,
            "nama": "Diskon Member",
            "potongan": 2000
          }
        ],
        "modifier": [
          {
            "id": 5,
            "nama": "Extra Shot",
            "harga": 4000
          }
        ],
        "pilihan": [],
        "promo": []
      }
    ]
  }
}
```

### Response Validasi Gagal (HTTP 422 Unprocessable Entity)
```json
{
  "message": "The name field is required.",
  "errors": {
    "name": [
      "The name field is required."
    ]
  }
}
```

### Response Error Outlet / Server (HTTP 422 / 500)
```json
{
  "status": "error",
  "message": "User tidak memiliki outlet yang terdaftar."
}
```
