# Catalog API (Mobile POS)

Dokumentasi endpoint API untuk **Catalog & Master Data** pada aplikasi Android/iOS POS Kasir.

Semua endpoint di bawah ini bersifat **protected** — wajib menyertakan Sanctum token di header.

**Base URL:** `{base_url}/api/v1/catalog`

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

> Outlet ditentukan **otomatis dari token login** — Android tidak perlu mengirim `outlet_id`.

---

## Daftar Endpoint

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| `GET` | `/catalog/categories` | Kategori + produk + varian |
| `GET` | `/catalog/modifiers` | Modifier group + item modifier |
| `GET` | `/catalog/discounts` | Daftar diskon |
| `GET` | `/catalog/sales-types` | Tipe penjualan (Dine In, Take Away, dll) |
| `GET` | `/catalog/pilihans` | Pilihan group + item pilihan |
| `GET` | `/catalog/taxes` | Data pajak |
| `GET` | `/catalog/payment-methods` | Kategori + metode pembayaran |
| `GET` | `/catalog/note-receipt-schedulings` | Catatan struk terjadwal |

---

## 1. Categories (Produk & Varian)

### Request

```http
GET {base_url}/api/v1/catalog/categories
```

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Minuman",
      "products": [
        {
          "id": 5,
          "name": "Kopi Susu Gula Aren",
          "category_id": 1,
          "photo": "kopi-susu.jpg",
          "description": "Kopi susu dengan gula aren asli",
          "exclude_tax": false,
          "outlet_id": 7,
          "variants": [
            {
              "id": 12,
              "product_id": 5,
              "name": "Small",
              "harga": 20000,
              "stok": 100
            },
            {
              "id": 13,
              "product_id": 5,
              "name": "Large",
              "harga": 25000,
              "stok": 80
            }
          ]
        }
      ]
    }
  ]
}
```

### Spesifikasi Field

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | int | ID kategori |
| `name` | string | Nama kategori |
| `products` | array | Daftar produk aktif di kategori ini |
| `products[].id` | int | ID produk |
| `products[].name` | string | Nama produk |
| `products[].photo` | string \| null | Nama file foto produk |
| `products[].description` | string \| null | Deskripsi produk |
| `products[].exclude_tax` | boolean | `true` jika produk dikecualikan dari pajak |
| `products[].variants` | array | Daftar varian produk |
| `products[].variants[].id` | int | ID varian |
| `products[].variants[].name` | string | Nama varian |
| `products[].variants[].harga` | int | Harga varian |
| `products[].variants[].stok` | int | Stok varian |

---

## 2. Modifiers

### Request

```http
GET {base_url}/api/v1/catalog/modifiers
```

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 3,
      "name": "Pilihan Es",
      "product_id": "[\"5\",\"6\"]",
      "outlet_id": 7,
      "is_required": false,
      "modifier": [
        {
          "id": 166,
          "modifiers_group_id": 3,
          "name": "Ice",
          "harga": 0,
          "stok": 999
        },
        {
          "id": 167,
          "modifiers_group_id": 3,
          "name": "Less Ice",
          "harga": 0,
          "stok": 999
        }
      ]
    }
  ]
}
```

### Spesifikasi Field

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | int | ID modifier group |
| `name` | string | Nama group modifier |
| `product_id` | string (JSON) | JSON array ID produk yang terkait. Parse terlebih dahulu. |
| `is_required` | boolean | `true` jika kasir wajib memilih modifier dari group ini |
| `modifier` | array | Daftar item modifier dalam group |
| `modifier[].id` | int | ID modifier |
| `modifier[].name` | string | Nama modifier |
| `modifier[].harga` | int | Harga tambahan modifier (0 jika gratis) |

---

## 3. Discounts

### Request

```http
GET {base_url}/api/v1/catalog/discounts
```

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 14,
      "name": "Diskon Voucher",
      "type_input": "fixed",
      "satuan_discount_custom": null,
      "amount": 10,
      "satuan": "percent",
      "outlet_id": 7
    }
  ]
}
```

### Spesifikasi Field

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | int | ID diskon |
| `name` | string | Nama diskon |
| `type_input` | string | Jenis input diskon (`fixed` / `custom`) |
| `amount` | numeric | Nilai diskon |
| `satuan` | string | Satuan diskon (`percent` / `rupiah`) |

---

## 4. Sales Types

### Request

```http
GET {base_url}/api/v1/catalog/sales-types
```

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Dine In",
      "outlet_id": 7,
      "status": true
    },
    {
      "id": 2,
      "name": "Take Away",
      "outlet_id": 7,
      "status": true
    }
  ]
}
```

---

## 5. Pilihans

### Request

```http
GET {base_url}/api/v1/catalog/pilihans
```

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Ukuran Cup",
      "product_id": "[\"5\",\"6\"]",
      "outlet_id": 7,
      "pilihans": [
        {
          "id": 1,
          "pilihan_group_id": 1,
          "name": "Regular",
          "harga": 0,
          "stok": 999
        },
        {
          "id": 2,
          "pilihan_group_id": 1,
          "name": "Large",
          "harga": 5000,
          "stok": 999
        }
      ]
    }
  ]
}
```

### Spesifikasi Field

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | int | ID pilihan group |
| `name` | string | Nama group pilihan |
| `product_id` | string (JSON) | JSON array ID produk yang terkait. Parse terlebih dahulu. |
| `pilihans` | array | Daftar item pilihan dalam group |
| `pilihans[].id` | int | ID pilihan |
| `pilihans[].name` | string | Nama pilihan |
| `pilihans[].harga` | int | Harga tambahan (0 jika gratis) |

---

## 6. Taxes

### Request

```http
GET {base_url}/api/v1/catalog/taxes
```

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 7,
      "name": "Pajak Restoran",
      "amount": 10,
      "satuan": "%",
      "outlet_id": 7
    }
  ]
}
```

### Spesifikasi Field

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | int | ID pajak |
| `name` | string | Nama pajak |
| `amount` | numeric | Nilai pajak |
| `satuan` | string | Satuan pajak (`%` atau `rupiah`) |

---

## 7. Payment Methods

### Request

```http
GET {base_url}/api/v1/catalog/payment-methods
```

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Cash",
      "status": true,
      "payment": []
    },
    {
      "id": 2,
      "name": "Non Cash",
      "status": true,
      "payment": [
        {
          "id": 2,
          "name": "QRIS",
          "category_payment_id": 2,
          "status": true
        },
        {
          "id": 3,
          "name": "BCA Debit",
          "category_payment_id": 2,
          "status": true
        }
      ]
    }
  ]
}
```

### Spesifikasi Field

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | int | ID kategori pembayaran |
| `name` | string | Nama kategori (cth: `"Cash"`, `"Non Cash"`) |
| `payment` | array | Daftar metode pembayaran dalam kategori ini |
| `payment[].id` | int | ID metode pembayaran — dikirim sebagai `tipe_pembayaran` saat bayar |
| `payment[].name` | string | Nama metode pembayaran — dikirim sebagai `nama_tipe_pembayaran` saat bayar |

---

## 8. Note Receipt Schedulings

Catatan struk terjadwal yang ditampilkan saat mobile mencetak struk. Data ini didesain untuk **disimpan di local DB mobile** — mobile yang mengatur kapan note ditampilkan berdasarkan jam, tanpa perlu hit API ini setiap kali print.

### Request

```http
GET {base_url}/api/v1/catalog/note-receipt-schedulings
```

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Promo Malam",
      "message": "Diskon 10% untuk pembelian di atas Rp 100.000",
      "start": "18:00:00",
      "end": "22:00:00",
      "outlet_id": 7,
      "product_id": [5, 12, 20],
      "status": true,
      "updated_at": "2026-09-09T10:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Selamat Datang",
      "message": "Terima kasih telah berkunjung! Tunjukkan struk ini untuk diskon berikutnya.",
      "start": "08:00:00",
      "end": "22:00:00",
      "outlet_id": 7,
      "product_id": null,
      "status": true,
      "updated_at": "2026-09-08T08:00:00.000000Z"
    }
  ]
}
```

### Spesifikasi Field

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | int | ID note receipt scheduling |
| `name` | string | Nama/label jadwal catatan |
| `message` | string | Teks catatan yang dicetak di struk |
| `start` | string (time) | Waktu mulai berlaku, format `HH:mm:ss` |
| `end` | string (time) | Waktu berakhir, format `HH:mm:ss` |
| `outlet_id` | int | ID outlet |
| `product_id` | array \| null | JSON array ID produk yang terkait. `null` atau `[]` berarti berlaku untuk **semua produk**. |
| `status` | boolean | `true` jika aktif |
| `updated_at` | string (ISO 8601) | Waktu terakhir data diubah — gunakan untuk deteksi perlu re-sync atau tidak |

### Strategi Integrasi Mobile (Offline-First)

```
Saat Login / Sync Awal:
  → Hit GET /api/v1/catalog/note-receipt-schedulings
  → Simpan seluruh data ke local DB (Room/SQLite)

Saat Print Struk:
  → Query local DB saja, TANPA hit API
  → Filter: status = true AND start <= currentTime <= end
  → Filter product_id:
      - Jika product_id null atau kosong ([]) → tampilkan untuk semua transaksi
      - Jika product_id berisi array → tampilkan hanya jika ada intersection
        dengan product_id dari item transaksi

Strategi Re-Sync:
  → Bandingkan updated_at terbaru dari server vs lokal
  → Jika berbeda → lakukan sync ulang
  → Disarankan: cek saat awal shift / buka app
```

### Contoh Logic Filter di Android (Kotlin Pseudocode)

```kotlin
val currentTime = LocalTime.now()

val applicableNotes = localDb.noteReceiptSchedulings
    .filter { note ->
        val start = LocalTime.parse(note.start)
        val end   = LocalTime.parse(note.end)
        note.status && currentTime >= start && currentTime <= end
    }
    .filter { note ->
        val productIds = note.product_id // List<Int>? dari local DB
        productIds.isNullOrEmpty() ||
            productIds.any { it in transactionProductIds }
    }
```

---

## Response Error Umum

Semua endpoint catalog mengembalikan format error berikut jika token tidak valid atau outlet tidak ditemukan:

### Token Tidak Valid / Tidak Ada (HTTP 401)

```json
{
  "message": "Unauthenticated."
}
```

### Outlet Tidak Terdaftar (HTTP 422)

```json
{
  "status": "error",
  "message": "User tidak memiliki outlet yang terdaftar."
}
```

---

## Rekomendasi Sync Strategy Mobile

| Endpoint | Kapan Sync | Frekuensi |
|----------|-----------|-----------|
| `/catalog/categories` | Login, awal shift | Setiap buka shift |
| `/catalog/modifiers` | Login, awal shift | Setiap buka shift |
| `/catalog/discounts` | Login, awal shift | Setiap buka shift |
| `/catalog/sales-types` | Login | Jarang berubah |
| `/catalog/pilihans` | Login, awal shift | Setiap buka shift |
| `/catalog/taxes` | Login | Jarang berubah |
| `/catalog/payment-methods` | Login | Jarang berubah |
| `/catalog/note-receipt-schedulings` | Login, awal shift | Cek `updated_at`, sync jika berubah |
