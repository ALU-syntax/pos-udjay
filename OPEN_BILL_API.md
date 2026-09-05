# Open Bill API (Android Kasir)

Dokumentasi endpoint API untuk fitur **Open Bill & Split Bill** pada aplikasi Android kasir.

**Base URL:** `{base_url}/api/v1/open-bills`  
**Auth:** Semua endpoint wajib menyertakan Sanctum token di header.

```
Authorization: Bearer {token}
Accept: application/json
```

> Outlet ditentukan **otomatis dari token login** — Android tidak perlu mengirim `outlet_id`.

> **Masa berlaku token:** sliding expiration 24 jam. Setiap request terautentikasi otomatis memperpanjang token +24 jam, jadi **tidak perlu refresh token / fitur login ulang** selama device dipakai rutin. Token baru kadaluwarsa jika device tidak mengirim request selama > 24 jam.

---

## Daftar Endpoint

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| `GET` | `/open-bills` | Daftar bill yang masih terbuka |
| `GET` | `/open-bills/{id}` | Detail open bill + isi item |

---

## 1. List Open Bill

### Request

```
GET {base_url}/api/v1/open-bills?q={search}
```

### Query Params

| Param | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `q` | string | Tidak | Cari bill berdasarkan nama (case-insensitive `LIKE`) |

### Response Sukses (HTTP 200)

```json
{
    "status": "success",
    "data": [
        {
            "id": 12,
            "name": "Meja 3",
            "queue_order": 2,
            "customer_id": 5,
            "customer_name": "Budi",
            "user": {
                "id": 2,
                "name": "Kasir A"
            },
            "item_count": 3,
            "total": 50000,
            "created_at": "2026-09-05T10:00:00.000000Z",
            "created_at_human": "5 menit lalu"
        }
    ]
}
```

### Response Error (HTTP 422)

```json
{
    "status": "error",
    "message": "User tidak memiliki outlet yang terdaftar."
}
```

### Spesifikasi Field

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | int | ID open bill |
| `name` | string | Nama bill (contoh: "Meja 3", "Take Away") |
| `queue_order` | int | Meja urutan saat ini (bertambah tiap bill di-update) |
| `customer_id` | int \| null | ID pelanggan, `null` jika tanpa pelanggan |
| `customer_name` | string \| null | Nama pelanggan, `null` jika tanpa pelanggan |
| `user.id` | int | ID user yang membuat bill (kasir) |
| `user.name` | string | Nama user/kasir |
| `item_count` | int | Jumlah item yang tersisa di bill |
| `total` | int | Total sisa tagihan = sum `result_total` seluruh item |
| `created_at` | datetime | Waktu bill dibuat (ISO 8601) |
| `created_at_human` | string | Waktu relatif siap tampil ("5 menit lalu") |

### Catatan Flow Android
1. Panggil endpoint ini saat membuka layar **Billing Management / Open Bill**.
2. Tampilkan list (nama bill, kasir, waktu, total).
3. Saat user memilih salah satu bill, panggil `GET /open-bills/{id}` untuk mengambil item-nya.

---

## 2. Detail Open Bill

### Request

```
GET {base_url}/api/v1/open-bills/{id}
```

### Response Sukses (HTTP 200)

```json
{
    "status": "success",
    "data": {
        "id": 12,
        "name": "Meja 3",
        "queue_order": 2,
        "customer": {
            "id": 5,
            "name": "Budi"
        },
        "user": {
            "id": 2,
            "name": "Kasir A"
        },
        "total": 50000,
        "created_at": "2026-09-05T10:00:00.000000Z",
        "created_at_human": "5 menit lalu",
        "items": [
            {
                "id": 40,
                "open_bill_id": 12,
                "tmp_id": "abc123",
                "product_id": "5",
                "variant_id": "4",
                "nama_product": "Americano",
                "nama_variant": "Large",
                "harga": 25000,
                "quantity": 2,
                "qty_terbayar": 0,
                "result_total": 50000,
                "catatan": null,
                "exclude_tax": false,
                "sales_type": "1",
                "diskon": [],
                "modifier": [],
                "pilihan": [],
                "promo": []
            }
        ]
    }
}
```

### Response Error (HTTP 404)

```json
{
    "status": "error",
    "message": "Open bill tidak ditemukan."
}
```

### Spesifikasi Field Item

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | int | ID item open bill (dipakai untuk proses bayar/split) |
| `open_bill_id` | int | ID open bill |
| `tmp_id` | string | ID unik item di sisi client (dibuat saat transaksi dibuat) |
| `product_id` | string | ID produk |
| `variant_id` | string | ID variant produk |
| `nama_product` | string | Nama produk |
| `nama_variant` | string | Nama variant |
| `harga` | int | Harga satuan |
| `quantity` | int | Jumlah tersisa (sudah dikurangi yang dipisah/dibayar) |
| `qty_terbayar` | int | Jumlah yang sudah dibayar/dipisah |
| `result_total` | int | Total baris = `harga * quantity` |
| `catatan` | string \| null | Catatan item |
| `exclude_tax` | bool | `true` = item tidak dikenakan pajak |
| `sales_type` | string \| null | ID sales type, `null` jika tidak ada |
| `diskon` | array | Daftar diskon item ini |
| `modifier` | array | Daftar modifier (tiap item: `{id, nama, harga}`) |
| `pilihan` | array | Daftar pilihan (tiap item: `{id, nama, harga}`) |
| `promo` | array | Daftar promo yang menempel di item |

### Catatan Flow Android
1. Setelah dapat bill via list, panggil endpoint ini.
2. Miliki data item, lalu muat ke cart (posisi & urutan bebas, pakai `tmp_id` sebagai key).
3. Saat bayar, kirim item ini (dengan `item_open_bill_id` / `tmp_id`) ke `POST /api/v1/kasir/bayar`.
4. Field JSON (`diskon`, `modifier`, `pilihan`, `promo`) **sudah berupa array** — tidak perlu parse string JSON lagi.

---

## Aturan & Batasan

1. **Hanya bill milik outlet user** — outlet ditentukan dari token; bill outlet lain tidak akan muncul.
2. **Hanya bill yang masih terbuka** — bill yang sudah dibayar di-soft-delete (`deleted_at` terisi) dan tidak dikembalikan.
3. **Bill yang dihapus permanen** (`delete_permanen`) juga tidak dikembalikan.
4. Endpoint bersifat **read-only** — membuat bill baru (`POST`) dan menambah item (`POST /api/v1/kasir/open-bill`, `update-bill-item`) masih dalam pengembangan.
5. `total` di list dan `data.total` di detail dihitung dari item yang **tersisa** (belum dibayar/dipisah).

---

## Contoh Penggunaan (Retrofit/Ktor)

```kotlin
interface OpenBillApi {

    @GET("api/v1/open-bills")
    suspend fun listOpenBills(
        @Header("Authorization") token: String,
        @Query("q") search: String? = null
    ): Response<OpenBillListResponse>

    @GET("api/v1/open-bills/{id}")
    suspend fun getOpenBill(
        @Header("Authorization") token: String,
        @Path("id") id: Int
    ): Response<OpenBillDetailResponse>
}
```