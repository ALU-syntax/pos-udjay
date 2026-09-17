# Catalog Rewards API (Mobile POS)

Dokumentasi endpoint API untuk mengambil **produk reward** yang dibutuhkan aplikasi Android/iOS POS agar dapat **disimpan secara offline di local Room DB** dan di-consume tanpa koneksi internet.

Endpoint ini dirancang **tanpa perlu join tambahan di sisi mobile** — semua detail produk (nama, harga, foto, kategori, varian) dan detail reward sudah dikirim lengkap dari server.

**Base URL:** `{base_url}/api/v1/catalog`
**Method:** `GET` (semua endpoint bersifat **read-only**)
**Auth:** Wajib menyertakan Sanctum token di header.

```http
Authorization: Bearer {token}
Accept: application/json
```

> **Catatan:**
> - `outlet_id` diambil **otomatis dari token login user** (`outletIds()[0]`), jadi mobile tidak perlu mengirim parameter outlet.
> - Jika user tidak memiliki outlet terdaftar, server mengembalikan **HTTP 422**.
> - Seluruh data dikirim dengan urutan `id` ascending agar konsisten saat di-upsert ke Room.
> - **Pencatatan claim/reward** (birthday claim, exp claim, reward confirmation) ada di dokumen terpisah: **`REWARD_SYNC_API.md`**.

---

## Daftar Endpoint

| Endpoint | Keterangan |
|---|---|
| `GET /api/v1/catalog/product-birthday-rewards` | Produk reward ulang tahun customer (birthday reward). |
| `GET /api/v1/catalog/product-exp-rewards` | Produk reward berdasarkan milestone EXP (mis. reward tiap 5000 EXP). |
| `GET /api/v1/catalog/reward-memberships` | Produk reward per level membership (mis. benchmark 6000 EXP → Duta). |

---

## 1. Product Birthday Reward

### `GET /api/v1/catalog/product-birthday-rewards`

Mengambil produk reward **ulang tahun** untuk outlet user. Sumber data: tabel `product_birthday_rewards` (kolom `outlet_id`, `product_id`, `product_name`).

> Data ini dipakai mobile untuk menampilkan reward birthday yang bisa diklaim customer pada rentang tanggal lahir tertentu (lihat pengecekan `isBirthdayInRange` pada detail customer).

### Header Request
| Header | Nilai | Wajib |
|---|---|---|
| `Authorization` | `Bearer {token}` | Ya |
| `Accept` | `application/json` | Ya |

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "product_name": "Free Cheese Cake",
      "product_id": 626,
      "outlet_id": 1,
      "created_at": "2026-01-30T03:26:10.000000Z",
      "updated_at": "2026-01-30T03:26:10.000000Z",
      "product": {
        "id": 626,
        "name": "Free Cheese Cake",
        "category_id": 32,
        "photo": null,
        "description": "Gratis 1 Reward Cheese Cake",
        "exclude_tax": 0,
        "outlet_id": 1,
        "category": {
          "id": 32,
          "name": "Membership"
        },
        "variants": [
          {
            "id": 875,
            "product_id": 626,
            "name": "Free Cheese Cake",
            "harga": 0,
            "stok": 999974
          }
        ]
      }
    }
  ]
}
```

#### Penjelasan Field Utama

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | int | ID record `product_birthday_rewards` (primary key untuk Room). |
| `product_name` | string | Nama produk reward (denormalisasi dari produk). |
| `product_id` | int | ID produk reward, relasi ke `product.id`. |
| `outlet_id` | int | ID outlet pemilik data (dari token user). |
| `created_at` / `updated_at` | string (ISO 8601) | Timestamp server, dipakai untuk mendeteksi perubahan saat sync. |
| `product` | object | Detail produk lengkap beserta `category` dan `variants`. |

**Catatan:** reward birthday disimpan **satu record per outlet** (diambil record pertama). Harga produk reward biasanya `0` dengan stok besar (mis. `999974`), dan varian wajib ada agar mobile bisa menampilkan produk saat klaim offline.

---

## 2. Product Exp Reward

### `GET /api/v1/catalog/product-exp-rewards`

Mengambil daftar produk reward EXP untuk outlet user. Sumber data: tabel `product_exp_rewards` (kolom `outlet_id`, `product_id`, `product_name`).

### Header Request
| Header | Nilai | Wajib |
|---|---|---|
| `Authorization` | `Bearer {token}` | Ya |
| `Accept` | `application/json` | Ya |

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "product_name": "Free Kopi Susu Djaya",
      "product_id": 619,
      "outlet_id": 1,
      "created_at": "2026-01-30T03:22:38.000000Z",
      "updated_at": "2026-07-31T05:10:51.000000Z",
      "product": {
        "id": 619,
        "name": "Free Kopi Susu Djaya",
        "category_id": 32,
        "photo": null,
        "description": "Gratis 1 Signature Kopi Susu",
        "exclude_tax": 0,
        "outlet_id": 1,
        "category": {
          "id": 32,
          "name": "Membership"
        },
        "variants": [
          {
            "id": 867,
            "product_id": 619,
            "name": "Free Kopi Susu Djaya",
            "harga": 0,
            "stok": 999722
          }
        ]
      }
    }
  ]
}
```

#### Penjelasan Field Utama

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | int | ID record `product_exp_rewards` (primary key untuk Room). |
| `product_name` | string | Nama produk reward (denormalisasi dari produk). |
| `product_id` | int | ID produk reward, relasi ke `product.id`. |
| `outlet_id` | int | ID outlet pemilik data (dari token user). |
| `created_at` / `updated_at` | string (ISO 8601) | Timestamp server, dipakai untuk mendeteksi perubahan saat sync. |
| `product` | object | Detail produk lengkap beserta `category` dan `variants`. |

**Catatan varian:** `variants[].harga` dan `variants[].stok` sudah termasuk sehingga mobile bisa langsung menampilkan & memotong stok lokal. Reward umumnya bernilai `harga: 0` dan stok besar (mis. `999722`).

---

## 3. Reward Membership (Level Reward)

### `GET /api/v1/catalog/reward-memberships`

Mengambil daftar reward per level membership untuk outlet user.

**Sumber data:** tabel pivot `reward_level_membership_products` (`reward_membership_id`, `product_id`, `outlet_id`).

> **Kenapa pakai tabel pivot, bukan join nama produk?**
> Tabel `reward_memberships` tidak menyimpan `product_id`. Produk reward dibuat per outlet dengan nama yang sama, sehingga pencocokan **nama → nama** berisiko menarik produk milik outlet lain atau duplikat antar kategori.
>
> Pivot `reward_level_membership_products` sudah menyimpan `product_id` + `outlet_id` secara presisi, sehingga:
> - Product yang dikirim **dijamin milik outlet tersebut** (divalidasi lewat `outlet_id`).
> - Walaupun nama produk sama antar outlet, `product_id` tetap tepat.

### Header Request
| Header | Nilai | Wajib |
|---|---|---|
| `Authorization` | `Bearer {token}` | Ya |
| `Accept` | `application/json` | Ya |

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": [
    {
      "id": 10,
      "reward_membership_id": 2,
      "product_id": 727,
      "outlet_id": 1,
      "deleted_at": null,
      "created_at": "2026-01-30T03:34:44.000000Z",
      "updated_at": "2026-01-30T03:34:44.000000Z",
      "product": {
        "id": 727,
        "name": "Free Keychain - Duta",
        "category_id": 32,
        "photo": null,
        "description": null,
        "exclude_tax": 0,
        "outlet_id": 1,
        "deleted_at": null,
        "category": {
          "id": 32,
          "name": "Membership"
        },
        "variants": [
          {
            "id": 1068,
            "product_id": 727,
            "name": "Free Keychain - Duta",
            "harga": 0,
            "stok": -19,
            "deleted_at": null
          }
        ]
      },
      "reward_membership": {
        "id": 2,
        "level_membership_id": 2,
        "name": "Free Keychain - Duta",
        "description": "Gratis 1 Keychain Duta saat 6000 EXP",
        "icon": "fa-classic fa-solid fa-0 fa-fw",
        "deleted_at": null,
        "level_membership": {
          "id": 2,
          "name": "Duta",
          "benchmark": 6000,
          "color": "#4d805c",
          "deleted_at": null
        }
      }
    }
  ]
}
```

#### Penjelasan Field Utama

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | int | ID record pivot `reward_level_membership_products` (primary key untuk Room). |
| `reward_membership_id` | int | ID reward pada tabel `reward_memberships`. |
| `product_id` | int | ID produk reward, relasi ke `product.id`. |
| `outlet_id` | int | ID outlet pemilik data (dari token user). |
| `deleted_at` | string \| null | Soft delete pivot — **wajib dipakai mobile** untuk menghapus/menandai baris di Room. |
| `product` | object | Detail produk lengkap (`category` + `variants`, masing-masing membawa `deleted_at`). |
| `reward_membership` | object | Detail reward: `name`, `description`, `icon`, `deleted_at`, + `level_membership`. |
| `reward_membership.level_membership` | object | Detail level: `name`, `benchmark` (EXP minimal), `color`, `deleted_at`. |

> **Perilaku filter:**
> - **Semua level dikirim** (tanpa filter `is_active`).
> - Data yang sudah **soft delete tetap dikirim** (`withTrashed()`) beserta nilai `deleted_at`, lengkap sampai ke produk, kategori, varian, reward, dan level — supaya mobile tahu baris mana yang harus dihapus dari Room.
> - Baris pivot yang produknya tidak sinkron (tidak ditemukan / bukan milik outlet tersebut) otomatis di-skip oleh server.

---

## Response Error

### 1. Outlet Tidak Ditemukan Pada Token (HTTP 422)

Berlaku untuk ketiga endpoint di atas.

```json
{
  "status": "error",
  "message": "User tidak memiliki outlet yang terdaftar."
}
```

### 2. Token Tidak Valid / Kedaluwarsa (HTTP 401)

```json
{
  "message": "Unauthenticated."
}
```

---

## Panduan Integrasi Mobile POS (Android/iOS)

### 1. Strategi Sinkronisasi ke Room

Endpoint ini **read-only** dan **idempotent**. Karena setiap record memiliki primary key stabil (`id`) beserta `updated_at` / `deleted_at`, gunakan pola **upsert + soft delete**:

1. Panggil ketiga endpoint catalog (section 1–3), plus endpoint sync di `REWARD_SYNC_API.md`, saat aplikasi connect / sebelum masuk mode offline.
2. Simpan hasilnya ke Room dengan `@Insert(onConflict = OnConflictStrategy.REPLACE)` berdasarkan `id`.
3. Untuk setiap baris yang `deleted_at != null`, tandai `isDeleted = true` di Room (jangan hard delete agar data lama pada transaksi offline tetap valid).

### 2. Relasi di Room

Karena data sudah denormalisasi lengkap, cukup buat entitas:

- `ProductBirthdayRewardEntity(id, product_name, productId, outletId, ...)`
- `ProductExpRewardEntity(id, product_name, productId, outletId, ...)`
- `RewardMembershipEntity(id, rewardMembershipId, productId, outletId, deletedAt, ...)`
- `ProductEmbedded` (nested object `product`) — dipakai bersama oleh ketiga entitas
- `LevelMembershipEmbedded` (nested `reward_membership.level_membership`)

Gunakan `@Embedded` / `@Relation` pada Room, **tanpa perlu join** tambahan saat runtime. Entitas untuk data claim/reward ada di `REWARD_SYNC_API.md`.

### 3. Sinkronisasi Hapus Data

Karena server mengirim `deleted_at` pada reward, produk, kategori, varian, dan level:

- Saat menerima `deleted_at` terisi, set flag lokal.
- Saat reward dihapus di server, produk reward juga ikut terhitung (kategori `Membership`).

### 4. Catatan Penting untuk Reward

- Harga produk reward biasanya `0`, jadi validasi total tagihan tetap berasal dari server saat `POST /api/v1/transactions/pay`.
- Stok varian (`stok`) ikut dikirim, namun **stok reward tidak dimutasi oleh transaksi biasa** — tetap andalkan server sebagai sumber kebenaran.
- `reward_membership.level_membership.benchmark` menunjukan EXP minimal untuk membersihkan reward level tersebut (contoh `6000`).
- Untuk **birthday reward**, kelayakan klaim tetap dihitung di server (rentang tanggal lahir + minimal 30 hari sejak registrasi) dan divalidasi ulang saat checkout — mobile cukup menyimpan produk reward-nya saja.
- **Validasi claim (birthday / exp / level reward)** tidak dibahas di dokumen ini — lihat `REWARD_SYNC_API.md`.
