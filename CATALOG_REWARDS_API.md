# Catalog Rewards API (Mobile POS)

Dokumentasi endpoint API catalog untuk mengambil data **reward membership** yang dibutuhkan aplikasi Android/iOS POS agar dapat **disimpan secara offline di local Room DB** dan di-consume tanpa koneksi internet.

Endpoint ini dirancang **tanpa perlu join tambahan di sisi mobile** — semua detail produk (nama, harga, foto, kategori, varian) dan detail reward sudah dikirim lengkap dari server.

**Base URL:** `{base_url}/api/v1/catalog`  
**Method:** `GET`  
**Auth:** Wajib menyertakan Sanctum token di header.

```http
Authorization: Bearer {token}
Accept: application/json
```

> **Catatan:**
> - `outlet_id` diambil **otomatis dari token login user** (`outletIds()[0]`), jadi mobile tidak perlu mengirim parameter outlet.
> - Jika user tidak memiliki outlet terdaftar, server mengembalikan **HTTP 422**.
> - Seluruh data dikirim dengan urutan `id` ascending agar konsisten saat di-upsert ke Room.

---

## Daftar Endpoint

| Endpoint | Keterangan |
|---|---|
| `GET /api/v1/catalog/product-birthday-rewards` | Produk reward ulang tahun customer (birthday reward). |
| `GET /api/v1/catalog/product-exp-rewards` | Produk reward berdasarkan milestone EXP (mis. reward tiap 5000 EXP). |
| `GET /api/v1/catalog/reward-memberships` | Produk reward per level membership (mis. benchmark 6000 EXP → Duta). |
| `GET /api/v1/customers/birthday-claims/sync` | **Delta sync** pencatatan claim birthday reward (lintas outlet). |
| `GET /api/v1/customers/exp-claims/sync` | **Delta sync** pencatatan claim EXP milestone reward (lintas outlet). |

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

## 4. Birthday Claim Sync (Delta Sync)

### `GET /api/v1/customers/birthday-claims/sync`

Delta sync pencatatan **customer yang sudah pernah claim birthday reward** ke cache lokal mobile (Room / SQLite).

**Sumber data:** tabel `birthday_reward_claims` (`customer_id`, `outlet_id`, `product_id`, `age`).

> **Tujuan:** mencegah satu customer claim birthday reward **lebih dari sekali pada umur yang sama**. Karena customer yang sudah claim di **outlet A tidak boleh claim lagi di outlet B**, data ini ber-scope **GLOBAL (semua outlet)** dan **tidak difilter per outlet**.

**Kunci validasi di mobile:** `customer_id` + `age`.
Sebelum mengizinkan claim, mobile mengecek apakah sudah ada baris dengan `customer_id` yang sama dan `age` = umur customer saat ini (yang belum `is_deleted`). Server tetap memvalidasi ulang saat checkout di `POST /api/v1/transactions/pay`.

> **Catatan penting:** endpoint ini **read-only**. Pencatatan claim baru terjadi otomatis di server saat checkout (`TransactionController::pay`) ketika item memiliki catatan `"Birthday Reward"`.

### Query Parameters

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `updated_since` | string (ISO 8601) | Tidak | Ambil baris dengan `updated_at >= nilai ini`. Dipakai untuk sync berkala (kirim `server_time` dari response sebelumnya). |
| `cursor` | string (opaque base64) | Tidak | Keyset pagination. Jangan diparse client — cukup simpan `next_cursor` dan kirim balik. |
| `limit` | int | Tidak | Jumlah baris per halaman. Default `500`, min `1`, max `1000`. |

**Aturan pemakaian:**
1. **Sync awal:** panggil tanpa `updated_since` dan tanpa `cursor`, lalu ikuti `next_cursor` selama `has_more` = `true`.
2. **Sync berkala:** simpan `server_time` dari response terakhir, kirim sebagai `updated_since` pada sync berikutnya.
3. Prioritas: `cursor` > `updated_since` > full sync.

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
      "customer_id": 12,
      "customer_name": "Budi Santoso",
      "outlet_id": 1,
      "outlet_name": "Ud. Djaya Coffee House - Cimanggu",
      "product_id": 626,
      "product_name": "Free Cheese Cake",
      "age": 27,
      "is_deleted": false,
      "deleted_at": null,
      "created_at": "2026-07-12T04:15:00.000000Z",
      "updated_at": "2026-07-12T04:15:00.000000Z"
    }
  ],
  "has_more": false,
  "next_cursor": null,
  "server_time": "2026-09-17T03:00:00.000000Z"
}
```

#### Penjelasan Field Utama

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | int | ID record `birthday_reward_claims` (primary key untuk Room). |
| `customer_id` | int | ID customer yang claim. |
| `customer_name` | string \| null | Nama customer (dikirim agar mobile tidak perlu join di Room). |
| `outlet_id` | int | ID outlet tempat claim dilakukan. |
| `outlet_name` | string \| null | Nama outlet tempat claim (untuk ditampilkan, mis. "sudah claim di outlet X"). |
| `product_id` | int | ID produk reward yang diambil. |
| `product_name` | string \| null | Nama produk reward. |
| `age` | int | Umur customer **saat claim**. Dipakai bersama `customer_id` sebagai kunci validasi "sudah claim tahun ini". |
| `is_deleted` | boolean | `true` jika baris sudah di-soft delete di server. |
| `deleted_at` | string \| null | Waktu soft delete. Dipakai mobile untuk menandai/menghapus baris lokal. |
| `created_at` | string (ISO 8601) | Waktu claim dibuat. |
| `updated_at` | string (ISO 8601) | Waktu terakhir baris berubah (dasar delta sync). |

#### Metadata Response

| Field | Tipe | Keterangan |
|---|---|---|
| `has_more` | boolean | `true` jika masih ada halaman berikutnya. |
| `next_cursor` | string \| null | Cursor untuk halaman berikutnya (`null` jika sudah habis). |
| `server_time` | string (ISO 8601) | Waktu server saat response dibuat, dipakai sebagai `updated_since` sync berikutnya. |

> **Soft delete:** tabel `birthday_reward_claims` mendukung soft delete (`deleted_at`). Baris yang dihapus tetap dikirim dengan `is_deleted` = `true` agar mobile dapat menghapus/menandai data lokalnya.

---

## 5. Exp Claim Sync (Delta Sync)

### `GET /api/v1/customers/exp-claims/sync`

Delta sync pencatatan **customer yang sudah pernah claim EXP milestone reward** ke cache lokal mobile (Room / SQLite).

**Sumber data:** tabel `exp_reward_claims` (`customer_id`, `outlet_id`, `product_id`, `exp`, `level_batch`).

> **Tujuan:** mencegah satu customer claim EXP milestone reward **lebih dari sekali**. Karena customer yang sudah claim di **outlet A tidak boleh claim lagi di outlet B**, data ini ber-scope **GLOBAL (semua outlet)** dan **tidak difilter per outlet**.

**Kunci validasi di mobile:** `customer_id` + `exp` + `level_batch`.
Customer hanya boleh claim satu kali untuk tiap milestone EXP (**kelipatan 5000 terbesar** dari `customers.exp`) pada `level_batch` yang sama.

> **Catatan penting:** endpoint ini **read-only**. Pencatatan claim baru terjadi otomatis di server saat checkout (`TransactionController::pay`) ketika item memiliki catatan `"Exp Reward"`.

### Query Parameters

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `updated_since` | string (ISO 8601) | Tidak | Ambil baris dengan `updated_at >= nilai ini`. Dipakai untuk sync berkala (kirim `server_time` dari response sebelumnya). |
| `cursor` | string (opaque base64) | Tidak | Keyset pagination. Jangan diparse client — cukup simpan `next_cursor` dan kirim balik. |
| `limit` | int | Tidak | Jumlah baris per halaman. Default `500`, min `1`, max `1000`. |

**Aturan pemakaian:**
1. **Sync awal:** panggil tanpa `updated_since` dan tanpa `cursor`, lalu ikuti `next_cursor` selama `has_more` = `true`.
2. **Sync berkala:** simpan `server_time` dari response terakhir, kirim sebagai `updated_since` pada sync berikutnya.
3. Prioritas: `cursor` > `updated_since` > full sync.

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
      "id": 7,
      "customer_id": 12,
      "customer_name": "Budi Santoso",
      "outlet_id": 1,
      "outlet_name": "Ud. Djaya Coffee House - Cimanggu",
      "product_id": 619,
      "product_name": "Free Kopi Susu Djaya",
      "exp": 5000,
      "level_batch": 1,
      "is_deleted": false,
      "deleted_at": null,
      "created_at": "2026-08-02T06:20:00.000000Z",
      "updated_at": "2026-08-02T06:20:00.000000Z"
    }
  ],
  "has_more": false,
  "next_cursor": null,
  "server_time": "2026-09-17T03:00:00.000000Z"
}
```

#### Penjelasan Field Utama

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | int | ID record `exp_reward_claims` (primary key untuk Room). |
| `customer_id` | int | ID customer yang claim. |
| `customer_name` | string \| null | Nama customer (dikirim agar mobile tidak perlu join di Room). |
| `outlet_id` | int | ID outlet tempat claim dilakukan. |
| `outlet_name` | string \| null | Nama outlet tempat claim (untuk ditampilkan, mis. "sudah claim di outlet X"). |
| `product_id` | int | ID produk reward yang diambil. |
| `product_name` | string \| null | Nama produk reward. |
| `exp` | int | Milestone EXP saat claim (kelipatan 5000). Bagian dari kunci validasi. |
| `level_batch` | int | Batch level customer saat claim. Bagian dari kunci validasi. |
| `is_deleted` | boolean | `true` jika baris sudah di-soft delete di server. |
| `deleted_at` | string \| null | Waktu soft delete. Dipakai mobile untuk menandai/menghapus baris lokal. |
| `created_at` | string (ISO 8601) | Waktu claim dibuat. |
| `updated_at` | string (ISO 8601) | Waktu terakhir baris berubah (dasar delta sync). |

#### Metadata Response

| Field | Tipe | Keterangan |
|---|---|---|
| `has_more` | boolean | `true` jika masih ada halaman berikutnya. |
| `next_cursor` | string \| null | Cursor untuk halaman berikutnya (`null` jika sudah habis). |
| `server_time` | string (ISO 8601) | Waktu server saat response dibuat, dipakai sebagai `updated_since` sync berikutnya. |

> **Soft delete:** tabel `exp_reward_claims` mendukung soft delete (`deleted_at`). Baris yang dihapus tetap dikirim dengan `is_deleted` = `true` agar mobile dapat menghapus/menandai data lokalnya.

---

## Response Error

### 1. Outlet Tidak Ditemukan Pada Token (HTTP 422)

Berlaku untuk ketiga endpoint catalog di atas (section 1–3).

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

### 3. Cursor Tidak Valid (HTTP 422)

Khusus endpoint claim sync (section 4–5).

```json
{
  "status": "error",
  "message": "Cursor tidak valid. Silakan mulai ulang sync tanpa cursor."
}
```

### 4. Format `updated_since` Tidak Valid (HTTP 422)

Khusus endpoint claim sync (section 4–5).

```json
{
  "status": "error",
  "message": "Format updated_since tidak valid. Gunakan ISO-8601 (contoh: 2026-09-11T10:00:00Z)."
}
```

---

## Panduan Integrasi Mobile POS (Android/iOS)

### 1. Strategi Sinkronisasi ke Room

Endpoint ini **read-only** dan **idempotent**. Karena setiap record memiliki primary key stabil (`id`) beserta `updated_at` / `deleted_at`, gunakan pola **upsert + soft delete**:

1. Panggil ketiga endpoint catalog (section 1–3) dan kedua endpoint claim sync (section 4–5) saat aplikasi connect / sebelum masuk mode offline.
2. Simpan hasilnya ke Room dengan `@Insert(onConflict = OnConflictStrategy.REPLACE)` berdasarkan `id`.
3. Untuk setiap baris yang `deleted_at != null`, tandai `isDeleted = true` di Room (jangan hard delete agar data lama pada transaksi offline tetap valid).

### 2. Relasi di Room

Karena data sudah denormalisasi lengkap, cukup buat entitas:

- `ProductBirthdayRewardEntity(id, product_name, productId, outletId, ...)`
- `ProductExpRewardEntity(id, product_name, productId, outletId, ...)`
- `RewardMembershipEntity(id, rewardMembershipId, productId, outletId, deletedAt, ...)`
- `BirthdayClaimEntity(id, customerId, customerName, outletId, outletName, productId, productName, age, isDeleted, deletedAt, ...)`
- `ExpClaimEntity(id, customerId, customerName, outletId, outletName, productId, productName, exp, levelBatch, isDeleted, deletedAt, ...)`
- `ProductEmbedded` (nested object `product`) — dipakai bersama oleh tiga entitas catalog
- `LevelMembershipEmbedded` (nested `reward_membership.level_membership`)

Gunakan `@Embedded` / `@Relation` pada Room, **tanpa perlu join** tambahan saat runtime. Nama-nama entitas (`customer_name`, `outlet_name`, `product_name`) sudah dikirim server sehingga tidak perlu lookup tabel lain.

### 3. Sinkronisasi Hapus Data

Karena server mengirim `deleted_at` pada reward, produk, kategori, varian, level, birthday claim, dan exp claim:

- Saat menerima `deleted_at` terisi, set flag lokal.
- Saat reward dihapus di server, produk reward juga ikut terhitung (kategori `Membership`).

### 4. Validasi Claim Birthday di Mobile

Alur yang disarankan: sebelum kasir menawarkan birthday reward, cek Room dengan:

```sql
SELECT * FROM birthday_claim
WHERE customer_id = :customerId
  AND age = :currentAge
  AND is_deleted = 0
LIMIT 1;
```

- Jika ada hasil → customer **sudah pernah claim** pada umur tersebut, tombol claim harus disembunyikan/dinonaktifkan.
- Karena data ini **global lintas outlet**, claim di outlet A otomatis memblokir claim di outlet B setelah sync berjalan.
- Server tetap memvalidasi ulang saat checkout, jadi data Room yang belum ter-sync tidak akan menghasilkan double claim di database.

### 5. Validasi Claim EXP Milestone di Mobile

> ⚠️ **WAJIB DIBACA AGENT MOBILE:** untuk EXP reward, mobile **tidak bisa** hanya membandingkan `customer_id` seperti birthday claim. Ada **dua nilai pembanding tambahan** yang harus dihitung dan dibandingkan dengan data customer hasil `customers/sync`, yaitu **`exp`** dan **`level_batch`**.

Langkah validasi:

1. Hitung milestone EXP yang bisa di-claim dari EXP customer saat ini (data customer dari endpoint `GET /api/v1/customers/sync`):
   ```kotlin
   val claimableExp = if (customer.exp >= 5000) (customer.exp / 5000) * 5000 else 0
   ```
   Milestone adalah **kelipatan 5000 terbesar** (bukan nilai EXP mentah).
2. Cek apakah sudah ada claim untuk customer tersebut pada milestone tsb **dan** `level_batch` yang sama:

```sql
SELECT * FROM exp_claim
WHERE customer_id = :customerId
  AND exp = :claimableExp
  AND level_batch = :customerLevelBatch
  AND is_deleted = 0
LIMIT 1;
```

- Jika ada hasil → customer **sudah claim milestone EXP tersebut pada `level_batch` saat ini**, tombol claim harus disembunyikan/dinonaktifkan.
- Jika customer **naik level** (dari endpoint `customers/sync`), `level_batch` berubah → milestone di batch baru menjadi bisa di-claim kembali. Karena itulah `level_batch` ikut disimpan di Room.
- EXP yang diperoleh **di bawah 5000** tidak menghasilkan claim (guard `exp >= 5000`).
- Karena data ini **global lintas outlet**, claim di outlet A otomatis memblokir claim di outlet B setelah sync berjalan.
- Server tetap memvalidasi ulang saat checkout.

### 6. Catatan Penting untuk Reward

- Harga produk reward biasanya `0`, jadi validasi total tagihan tetap berasal dari server saat `POST /api/v1/transactions/pay`.
- Stok varian (`stok`) ikut dikirim, namun **stok reward tidak dimutasi oleh transaksi biasa** — tetap andalkan server sebagai sumber kebenaran.
- `level_membership.benchmark` menunjukan EXP minimal untuk membersihkan reward tersebut (contoh `6000`).
- Untuk **birthday reward**, kelayakan klaim tetap dihitung di server (rentang tanggal lahir + minimal 30 hari sejak registrasi) dan divalidasi ulang saat checkout — mobile cukup menyimpan produk reward-nya saja.
- Untuk **birthday claim**, kunci validasinya adalah `customer_id` + `age` (bukan tanggal), sesuai logika server. Claim dicatat otomatis saat checkout.
- Untuk **exp claim**, kunci validasinya adalah `customer_id` + `exp` (milestone, kelipatan 5000 terbesar) + `level_batch` — **wajib pembandingan dengan `exp` & `level_batch` customer saat ini**, sesuai logika server (`TransactionController`). Claim dicatat otomatis saat checkout.
