# Reward Sync API (Mobile POS)

Dokumentasi endpoint **delta sync** untuk menyimpan data claim/reward customer ke local Room DB aplikasi Android/iOS POS, sehingga validasi reward dapat dilakukan **secara offline** tanpa koneksi internet.

Tiga data yang di-sync di sini:

1. **Birthday claim** — pencatatan customer yang sudah claim birthday reward.
2. **Exp claim** — pencatatan customer yang sudah claim EXP milestone reward.
3. **Reward confirmation** — pencatatan reward level yang sudah diambil customer.

**Base URL:** `{base_url}/api/v1/customers`
**Method:** `GET` (semua endpoint bersifat **read-only**)
**Auth:** Wajib menyertakan Sanctum token di header.

```http
Authorization: Bearer {token}
Accept: application/json
```

> **Penting — kenapa data ini GLOBAL, bukan per outlet:**
> Ketiga endpoint di dokumen ini **tidak difilter `outlet_id`** — semuanya mengirim data dari **SELURUH outlet**. Tujuannya agar customer yang sudah claim reward di **outlet A tidak bisa claim lagi di outlet B**. Jangan asumsikan data ini hanya milik outlet dari token.
>
> **Catatan:** produk reward yang perlu di-cache mobile (birthday product, exp product, level reward product) ada di dokumen terpisah: **`CATALOG_REWARDS_API.md`**.

---

## Daftar Endpoint

| Endpoint | Keterangan |
|---|---|
| `GET /api/v1/customers/birthday-claims/sync` | Delta sync pencatatan claim birthday reward (lintas outlet). |
| `GET /api/v1/customers/exp-claims/sync` | Delta sync pencatatan claim EXP milestone reward (lintas outlet). |
| `GET /api/v1/customers/reward-confirmations/sync` | Delta sync reward level yang sudah diambil customer (lintas outlet). |

### Ringkasan kunci validasi (WAJIB)

| Data | Kunci validasi di Room | Arti |
|---|---|---|
| Birthday claim | `customer_id` + `age` | Customer hanya boleh claim sekali per umur. |
| Exp claim | `customer_id` + `exp` + `level_batch` | Customer hanya boleh claim sekali per milestone EXP (kelipatan 5000 terbesar) di tiap batch level. |
| Reward confirmation | `customer_id` + `reward_memberships_id` + `level_batch` | Customer hanya boleh mengambil reward yang sama sekali per batch level. |

### Format response (berlaku untuk ketiga endpoint)

Semua endpoint memakai bentuk response yang sama:

| Field | Tipe | Keterangan |
|---|---|---|
| `status` | string | `"success"` atau `"error"`. |
| `data` | array | Daftar baris. |
| `has_more` | boolean | `true` jika masih ada halaman berikutnya. |
| `next_cursor` | string \| null | Cursor halaman berikutnya (`null` jika habis). |
| `server_time` | string (ISO 8601) | Waktu server, dipakai sebagai `updated_since` sync berikutnya. |

### Format request (berlaku untuk ketiga endpoint)

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `updated_since` | string (ISO 8601) | Tidak | Ambil baris dengan `updated_at >= nilai ini` (delta sync). |
| `cursor` | string (opaque base64) | Tidak | Keyset pagination. Jangan diparse client — cukup simpan `next_cursor` dan kirim balik. |
| `limit` | int | Tidak | Jumlah baris per halaman. Default `500`, min `1`, max `1000`. |

**Aturan pemakaian:**
1. **Sync awal:** panggil tanpa `updated_since` dan tanpa `cursor`, lalu ikuti `next_cursor` selama `has_more` = `true`.
2. **Sync berkala:** simpan `server_time` dari response terakhir, kirim sebagai `updated_since` pada sync berikutnya.
3. Prioritas: `cursor` > `updated_since` > full sync.

---

## 1. Birthday Claim Sync (Delta Sync)

### `GET /api/v1/customers/birthday-claims/sync`

Delta sync pencatatan **customer yang sudah pernah claim birthday reward** ke cache lokal mobile (Room / SQLite).

**Sumber data:** tabel `birthday_reward_claims` (`customer_id`, `outlet_id`, `product_id`, `age`).

> **Tujuan:** mencegah satu customer claim birthday reward **lebih dari sekali pada umur yang sama**. Karena customer yang sudah claim di **outlet A tidak boleh claim lagi di outlet B**, data ini ber-scope **GLOBAL (semua outlet)** dan **tidak difilter per outlet**.

**Kunci validasi di mobile:** `customer_id` + `age`.
Sebelum mengizinkan claim, mobile mengecek apakah sudah ada baris dengan `customer_id` yang sama dan `age` = umur customer saat ini (yang belum `is_deleted`). Server tetap memvalidasi ulang saat checkout di `POST /api/v1/transactions/pay`.

> **Catatan penting:** endpoint ini **read-only**. Pencatatan claim baru terjadi otomatis di server saat checkout (`TransactionController::pay`) ketika item memiliki catatan `"Birthday Reward"`.

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

## 2. Exp Claim Sync (Delta Sync)

### `GET /api/v1/customers/exp-claims/sync`

Delta sync pencatatan **customer yang sudah pernah claim EXP milestone reward** ke cache lokal mobile (Room / SQLite).

**Sumber data:** tabel `exp_reward_claims` (`customer_id`, `outlet_id`, `product_id`, `exp`, `level_batch`).

> **Tujuan:** mencegah satu customer claim EXP milestone reward **lebih dari sekali**. Karena customer yang sudah claim di **outlet A tidak boleh claim lagi di outlet B**, data ini ber-scope **GLOBAL (semua outlet)** dan **tidak difilter per outlet**.

**Kunci validasi di mobile:** `customer_id` + `exp` + `level_batch`.
Customer hanya boleh claim satu kali untuk tiap milestone EXP (**kelipatan 5000 terbesar** dari `customers.exp`) pada `level_batch` yang sama.

> **Catatan penting:** endpoint ini **read-only**. Pencatatan claim baru terjadi otomatis di server saat checkout (`TransactionController::pay`) ketika item memiliki catatan `"Exp Reward"`.

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

## 3. Reward Confirmation Sync (Delta Sync)

### `GET /api/v1/customers/reward-confirmations/sync`

Delta sync **reward level membership yang sudah diambil customer** ke cache lokal mobile (Room / SQLite).

**Sumber data:** tabel `reward_confirmations` (`customer_id`, `reward_memberships_id`, `level_membership_id`, `level_batch`, `outlet_id`, `user_id`, `snapshot`).

Endpoint ini punya **dua tujuan sekaligus**:

1. **Mencegah double claim level reward** — pengecekan dilakukan lokal di Room, sehingga customer yang sudah mengambil reward di **outlet A tidak bisa mengambil lagi di outlet B**.
2. **Menampilkan riwayat reward** yang sudah pernah diambil customer.

> Karena tujuannya mencegah double claim lintas outlet, data ini ber-scope **GLOBAL (semua outlet)** dan **tidak difilter per outlet**.

**Kunci validasi claim di mobile:** `customer_id` + `reward_memberships_id` + `level_batch`.
Customer hanya boleh mengambil reward yang sama **sekali per `level_batch`**.

> **Catatan penting:** endpoint ini **read-only**. Pencatatan reward baru terjadi otomatis di server saat checkout (`TransactionController::pay`) ketika item memiliki catatan `"Level Reward"`.

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
      "id": 15,
      "customer_id": 12,
      "customer_name": "Budi Santoso",
      "level_membership_id": 2,
      "level_membership_name": "Duta",
      "reward_memberships_id": 3,
      "reward_name": "Free Keychain - Duta",
      "level_batch": 1,
      "outlet_id": 1,
      "outlet_name": "Ud. Djaya Coffee House - Cimanggu",
      "user_id": 5,
      "user_name": "Kasir Cimanggu",
      "snapshot": {
        "product_id": 727,
        "product_name": "Free Keychain - Duta",
        "level_membership_id": 2,
        "level_membership_name": "Duta"
      },
      "is_deleted": false,
      "deleted_at": null,
      "created_at": "2026-08-20T07:12:00.000000Z",
      "updated_at": "2026-08-20T07:12:00.000000Z"
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
| `id` | int | ID record `reward_confirmations` (primary key untuk Room). |
| `customer_id` | int | ID customer yang mengambil reward. |
| `customer_name` | string \| null | Nama customer (dikirim agar mobile tidak perlu join di Room). |
| `level_membership_id` | int | ID level membership saat reward diambil. |
| `level_membership_name` | string \| null | Nama level membership. |
| `reward_memberships_id` | int | ID reward pada tabel `reward_memberships`. Bagian dari kunci validasi. |
| `reward_name` | string \| null | Nama reward level. |
| `level_batch` | int \| null | Batch level saat reward diambil. Bagian dari kunci validasi. |
| `outlet_id` | int \| null | ID outlet tempat reward diserahkan. |
| `outlet_name` | string \| null | Nama outlet (untuk ditampilkan, mis. "sudah diambil di outlet X"). |
| `user_id` | int | ID kasir yang menyerahkan reward. |
| `user_name` | string \| null | Nama kasir yang menyerahkan reward. |
| `snapshot` | object \| null | Snapshot data produk reward saat penyerahan (`product_id`, `product_name`, `level_membership_id`, `level_membership_name`). |
| `is_deleted` | boolean | `true` jika baris sudah di-soft delete di server. |
| `deleted_at` | string \| null | Waktu soft delete. Dipakai mobile untuk menandai/menghapus baris lokal. |
| `created_at` | string (ISO 8601) | Waktu reward diambil. |
| `updated_at` | string (ISO 8601) | Waktu terakhir baris berubah (dasar delta sync). |

> **Catatan `snapshot`:** nilainya adalah JSON dari server dan dikirim apa adanya (sudah di-decode menjadi object). Cocok untuk ditampilkan sebagai detail produk saat reward diambil, walau produknya kemudian berubah/terhapus.

#### Metadata Response

| Field | Tipe | Keterangan |
|---|---|---|
| `has_more` | boolean | `true` jika masih ada halaman berikutnya. |
| `next_cursor` | string \| null | Cursor untuk halaman berikutnya (`null` jika sudah habis). |
| `server_time` | string (ISO 8601) | Waktu server saat response dibuat, dipakai sebagai `updated_since` sync berikutnya. |

> **Soft delete:** tabel `reward_confirmations` mendukung soft delete (`deleted_at`). Baris yang dihapus tetap dikirim dengan `is_deleted` = `true` agar mobile dapat menghapus/menandai data lokalnya.

---

## Response Error

Berlaku untuk ketiga endpoint sync di dokumen ini.

### 1. Token Tidak Valid / Kedaluwarsa (HTTP 401)

```json
{
  "message": "Unauthenticated."
}
```

### 2. Cursor Tidak Valid (HTTP 422)

```json
{
  "status": "error",
  "message": "Cursor tidak valid. Silakan mulai ulang sync tanpa cursor."
}
```

### 3. Format `updated_since` Tidak Valid (HTTP 422)

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

1. Panggil ketiga endpoint sync di dokumen ini (section 1–3), plus endpoint produk reward di `CATALOG_REWARDS_API.md`, saat aplikasi connect / sebelum masuk mode offline.
2. Simpan hasilnya ke Room dengan `@Insert(onConflict = OnConflictStrategy.REPLACE)` berdasarkan `id`.
3. Untuk setiap baris yang `deleted_at != null`, tandai `isDeleted = true` di Room (jangan hard delete agar data lama pada transaksi offline tetap valid).

### 2. Relasi di Room

Karena data sudah denormalisasi lengkap, cukup buat entitas:

- `ProductBirthdayRewardEntity(id, product_name, productId, outletId, ...)`
- `ProductExpRewardEntity(id, product_name, productId, outletId, ...)`
- `RewardMembershipEntity(id, rewardMembershipId, productId, outletId, deletedAt, ...)`
- `BirthdayClaimEntity(id, customerId, customerName, outletId, outletName, productId, productName, age, isDeleted, deletedAt, ...)`
- `ExpClaimEntity(id, customerId, customerName, outletId, outletName, productId, productName, exp, levelBatch, isDeleted, deletedAt, ...)`
- `RewardConfirmationEntity(id, customerId, customerName, levelMembershipId, levelMembershipName, rewardMembershipsId, rewardName, levelBatch, outletId, outletName, userId, userName, snapshot, isDeleted, deletedAt, ...)`
- `ProductEmbedded` (nested object `product`) — dipakai bersama oleh tiga entitas catalog
- `LevelMembershipEmbedded` (nested `reward_membership.level_membership`)

Gunakan `@Embedded` / `@Relation` pada Room, **tanpa perlu join** tambahan saat runtime. Nama-nama entitas (`customer_name`, `outlet_name`, `product_name`) sudah dikirim server sehingga tidak perlu lookup tabel lain.

### 3. Sinkronisasi Hapus Data

Karena server mengirim `deleted_at` pada reward, produk, kategori, varian, level, birthday claim, exp claim, dan reward confirmation:

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

### 6. Validasi Reward Level (Reward Confirmation) di Mobile

> ⚠️ **WAJIB DIBACA AGENT MOBILE:** reward level punya **dua fungsi** di mobile — mencegah double claim **dan** menampilkan riwayat. Gunakan `reward-confirmations/sync` (section 3) untuk keduanya.

**A. Mencegah double claim** — sebelum kasir menawarkan reward level, cek Room dengan kunci `customer_id` + `reward_memberships_id` + `level_batch`:

```sql
SELECT * FROM reward_confirmation
WHERE customer_id = :customerId
  AND reward_memberships_id = :rewardMembershipsId
  AND level_batch = :customerLevelBatch
  AND is_deleted = 0
LIMIT 1;
```

- `reward_memberships_id` didapat dari data reward level customer (endpoint `customers/{id}` atau `reward-memberships` di `CATALOG_REWARDS_API.md`).
- `level_batch` diambil dari data customer hasil `customers/sync`.
- Jika ada hasil → customer **sudah mengambil reward tersebut pada `level_batch` saat ini**, tombol claim harus disembunyikan/dinonaktifkan.
- Jika customer **naik level** (`level_batch` berubah), reward level menjadi bisa diambil kembali. Karena itulah `level_batch` ikut disimpan.
- Karena data ini **global lintas outlet**, pengambilan di outlet A otomatis memblokir outlet B setelah sync berjalan.
- Server tetap memvalidasi ulang saat checkout.

**B. Menampilkan riwayat reward** — tampilkan daftar `reward_confirmation` milik customer (urut `created_at` desc), lengkap dengan `reward_name`, `level_membership_name`, `outlet_name`, `user_name`, dan `snapshot.product_name` — semuanya sudah tersedia tanpa join.

### 7. Catatan Penting untuk Reward

- Harga produk reward biasanya `0`, jadi validasi total tagihan tetap berasal dari server saat `POST /api/v1/transactions/pay`.
- Stok varian (`stok`) ikut dikirim, namun **stok reward tidak dimutasi oleh transaksi biasa** — tetap andalkan server sebagai sumber kebenaran.
- `level_membership.benchmark` menunjukan EXP minimal untuk membersihkan reward tersebut (contoh `6000`).
- Untuk **birthday reward**, kelayakan klaim tetap dihitung di server (rentang tanggal lahir + minimal 30 hari sejak registrasi) dan divalidasi ulang saat checkout — mobile cukup menyimpan produk reward-nya saja.
- Untuk **birthday claim**, kunci validasinya adalah `customer_id` + `age` (bukan tanggal), sesuai logika server. Claim dicatat otomatis saat checkout.
- Untuk **exp claim**, kunci validasinya adalah `customer_id` + `exp` (milestone, kelipatan 5000 terbesar) + `level_batch` — **wajib pembandingan dengan `exp` & `level_batch` customer saat ini**, sesuai logika server (`TransactionController`). Claim dicatat otomatis saat checkout.
- Untuk **reward confirmation (level reward)**, kunci validasinya adalah `customer_id` + `reward_memberships_id` + `level_batch`. Kolom `snapshot` menyimpan data produk saat penyerahan, jadi riwayat tetap akurat walau produknya kemudian berubah atau dihapus. Pencatatan otomatis saat checkout.
