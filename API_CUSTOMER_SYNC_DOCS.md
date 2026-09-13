# Dokumentasi API: Customer Delta Sync (Mobile POS Android)

Dokumentasi ini digunakan sebagai panduan bagi client Mobile POS (Android) untuk menyinkronkan data customer/member dari server ke database lokal (Room / SQLite) secara efisien.

Tujuan utama: **menghindari fetching seluruh data customer setiap kali layar dibuka**. Fetching massal hanya terjadi saat sync awal, selanjutnya cukup mengambil perubahan (delta).

---

## 1. Spesifikasi Endpoint

- **URL:** `/api/v1/customers/sync`
- **Method:** `GET`
- **Headers:**
  ```http
  Authorization: Bearer <SANCTUM_TOKEN>
  Accept: application/json
  ```
- **Scope data:** **GLOBAL** (semua customer, tidak difilter per outlet).
- **Urutan data:** `updated_at ASC, id ASC` (stabil untuk pagination).

### Query Parameters

| Parameter | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| `updated_since` | `string` (ISO-8601) | Tidak | - | Ambil hanya data yang berubah sejak waktu ini. Contoh: `2026-09-11T10:00:00Z`. |
| `cursor` | `string` | Tidak | - | Opaque cursor dari response sebelumnya (`next_cursor`). Jangan diparse/dimodifikasi. |
| `limit` | `integer` | Tidak | `500` | Jumlah data per halaman. Min `1`, max `1000`. |

> **Catatan:** `cursor` lebih diprioritaskan daripada `updated_since` bila keduanya dikirim.

---

## 2. Alur Sinkronisasi

### A. Sync Awal (Initial Sync)

Panggil tanpa parameter apa pun, lalu ikuti `next_cursor` sampai `has_more = false`:

```
GET /api/v1/customers/sync?limit=500
GET /api/v1/customers/sync?limit=500&cursor=<next_cursor>
GET /api/v1/customers/sync?limit=500&cursor=<next_cursor>
...
```

Setelah `has_more = false`, simpan `server_time` dari response **terakhir** sebagai `last_sync_at` di device.

### B. Sync Berkala (Delta Sync)

Gunakan `last_sync_at` yang tersimpan sebagai `updated_since`:

```
GET /api/v1/customers/sync?updated_since=2026-09-11T10:00:00Z&limit=500
```

Tetap ikuti `next_cursor` bila `has_more = true`, lalu perbarui `last_sync_at` dengan `server_time` terakhir.

### C. Penanganan Soft Delete

Server **tetap mengirim** customer yang dihapus (soft delete) dengan:
- `is_deleted: true`
- `deleted_at`: timestamp penghapusan

Device **wajib menghapus** baris tersebut dari Room/SQLite lokal. Jika tidak, data lokal akan basi.

---

## 3. Contoh Response (HTTP 200 OK)

```json
{
  "status": "success",
  "data": [
    {
      "id": 9139,
      "name": "Budi Santoso",
      "phone": "081234567890",
      "email": "budi@example.com",
      "umur": 28,
      "tanggal_lahir": "1998-04-12",
      "domisili": "Jakarta",
      "gender": "laki-laki",
      "community_id": 3,
      "referral_id": null,
      "level_memberships_id": 2,
      "level": {
        "id": 2,
        "name": "Gold",
        "color": "#FFD700"
      },
      "point": 1250,
      "exp": 3400,
      "level_batch": 1,
      "user_id": 64,
      "user_name": "Daniel Stephen Mulumbot",
      "outlet_id": 6,
      "outlet_name": "UD.Djaya Jakal",
      "is_deleted": false,
      "deleted_at": null,
      "created_at": "2026-01-05T08:12:00.000000Z",
      "updated_at": "2026-09-11T09:58:12.000000Z"
    }
  ],
  "has_more": true,
  "next_cursor": "eyJ1cGRhdGVkX2F0IjoiMjAyNi0wOS0xMVQwOTo1ODoxMiswMDowMCIsImlkIjo5MTM5fQ",
  "server_time": "2026-09-11T10:05:00.000000Z"
}
```

### Penjelasan Field:

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | `integer` | ID customer (primary key untuk upsert lokal). |
| `name` | `string` | Nama customer. |
| `phone` | `string`/`null` | Nomor telepon. |
| `email` | `string`/`null` | Email. |
| `umur` | `integer`/`null` | Umur. |
| `tanggal_lahir` | `date`/`null` | Tanggal lahir (`YYYY-MM-DD`). |
| `domisili` | `string`/`null` | Domisili. |
| `gender` | `string`/`null` | `laki-laki` atau `perempuan`. |
| `community_id` | `integer`/`null` | ID komunitas. |
| `referral_id` | `integer`/`null` | ID customer yang mereferensikan. |
| `level_memberships_id` | `integer` | ID level membership. |
| `level` | `object`/`null` | Info level: `id`, `name`, `color`. |
| `point` | `integer` | Total poin. |
| `exp` | `integer` | Total EXP. |
| `level_batch` | `integer`/`null` | Batch level. |
| `user_id` | `integer`/`null` | ID user yang mendaftarkan customer. |
| `user_name` | `string`/`null` | Nama user yang mendaftarkan customer. |
| `outlet_id` | `integer`/`null` | ID outlet, diambil dari **index pertama** `outlet_id` milik user pembuat. |
| `outlet_name` | `string`/`null` | Nama outlet sesuai `outlet_id` di atas. |
| `is_deleted` | `boolean` | `true` = sudah dihapus, hapus dari DB lokal. |
| `deleted_at` | `timestamp`/`null` | Waktu penghapusan. |
| `created_at` | `timestamp` | Waktu dibuat. |
| `updated_at` | `timestamp` | Waktu terakhir diubah. |
| `has_more` | `boolean` | `true` = masih ada halaman berikutnya. |
| `next_cursor` | `string`/`null` | Cursor halaman berikutnya (`null` jika sudah habis). |
| `server_time` | `timestamp` | Waktu server; simpan sebagai `last_sync_at`. |

---

## 4. Catatan Implementasi Android (Room)

1. **Upsert berdasarkan `id`** (`@Insert(onConflict = REPLACE)`), karena baris yang sama bisa terkirim ulang saat boundary `updated_since` (`>=`).
2. **Hapus lokal** baris dengan `is_deleted = true`.
3. **Jangan parse `cursor`** — cukup simpan string dan kirim kembali apa adanya.
4. **Simpan `last_sync_at`** hanya setelah semua halaman selesai (`has_more = false`).
5. **Gunakan UTC** saat mengirim `updated_since` (`server_time` sudah dalam ISO-8601 UTC).
6. **Search lokal** di Room dianjurkan untuk pencarian cepat, karena data sudah tersedia offline. Server tidak perlu dipanggil setiap kali layar customer dibuka.
7. **Batasi field** — response ini sudah ringkas. Detail lengkap (reward, history, transaksi) tetap diambil dari `GET /api/v1/customers/{id}`.

---

## 5. Response Error

### Cursor Tidak Valid (HTTP 422)
```json
{
  "status": "error",
  "message": "Cursor tidak valid. Silakan mulai ulang sync tanpa cursor."
}
```

### Format `updated_since` Salah (HTTP 422)
```json
{
  "status": "error",
  "message": "Format updated_since tidak valid. Gunakan ISO-8601 (contoh: 2026-09-11T10:00:00Z)."
}
```

### Tidak Terautentikasi (HTTP 401)
```json
{
  "message": "Unauthenticated."
}
```
