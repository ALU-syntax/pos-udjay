# Dokumentasi API: Catalog Categories (Mobile POS Android)

Dokumentasi ini digunakan sebagai panduan bagi client Mobile POS (Android) untuk mengambil daftar kategori beserta produk aktif dan variannya.

---

## 1. Spesifikasi Endpoint

- **URL:** `/api/v1/catalog/categories`
- **Method:** `GET`
- **Headers:**
  ```http
  Authorization: Bearer <SANCTUM_TOKEN>
  Accept: application/json
  ```

---

## 2. Catatan Penting

- Outlet diambil otomatis dari token user yang login.
- Kategori tanpa produk aktif **tidak** disertakan dalam response.
- Produk diurutkan berdasarkan nama ascending, begitu juga variannya.
- Field `reward_categories` menandakan apakah kategori tersebut digunakan sebagai **kategori produk reward** (membership). Nilainya `true` atau `false`.

---

## 3. Contoh Response (HTTP 200 OK)

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Kopi Susu",
      "reward_categories": false,
      "products": [
        {
          "id": 10,
          "name": "Kopi Susu Gula Aren",
          "category_id": 1,
          "photo": "products/kopi-susu.jpg",
          "description": "Kopi susu dengan gula aren asli",
          "exclude_tax": false,
          "outlet_id": 1,
          "variants": [
            {
              "id": 25,
              "product_id": 10,
              "name": "Large",
              "harga": 18000,
              "stok": 100
            },
            {
              "id": 24,
              "product_id": 10,
              "name": "Regular",
              "harga": 15000,
              "stok": 120
            }
          ]
        }
      ]
    },
    {
      "id": 32,
      "name": "Membership",
      "reward_categories": true,
      "products": [
        {
          "id": 88,
          "name": "Free Coffee Reward",
          "category_id": 32,
          "photo": null,
          "description": "Reward gratis 1 kopi",
          "exclude_tax": false,
          "outlet_id": 1,
          "variants": [
            {
              "id": 190,
              "product_id": 88,
              "name": "Default",
              "harga": 0,
              "stok": null
            }
          ]
        }
      ]
    }
  ]
}
```

### Penjelasan Field:

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | `integer` | ID kategori. |
| `name` | `string` | Nama kategori. |
| `reward_categories` | `boolean` | `true` jika kategori khusus produk reward/membership, `false` jika kategori produk biasa. |
| `products` | `array` | Daftar produk aktif pada kategori tersebut. |
| `products[].id` | `integer` | ID produk. |
| `products[].name` | `string` | Nama produk. |
| `products[].category_id` | `integer` | ID kategori pemilik produk. |
| `products[].photo` | `string`/`null` | Path foto produk. |
| `products[].description` | `string`/`null` | Deskripsi produk. |
| `products[].exclude_tax` | `boolean` | Menandakan produk bebas pajak atau tidak. |
| `products[].outlet_id` | `integer` | ID outlet produk. |
| `products[].variants` | `array` | Daftar varian produk. |
| `products[].variants[].id` | `integer` | ID varian. |
| `products[].variants[].product_id` | `integer` | ID produk induk varian. |
| `products[].variants[].name` | `string` | Nama varian. |
| `products[].variants[].harga` | `integer` | Harga varian. |
| `products[].variants[].stok` | `integer`/`null` | Stok varian. |

---

## 4. Response Error

### Tidak Ada Outlet Terdaftar (HTTP 422)
```json
{
  "status": "error",
  "message": "User tidak memiliki outlet yang terdaftar."
}
```
