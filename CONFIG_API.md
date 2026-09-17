# Config API (Mobile POS)

Dokumentasi endpoint API untuk mengambil **value config** aplikasi (validasi, batas nilai, feature flag, dan lain sebagainya) yang dibutuhkan aplikasi Android/iOS POS — dirancang agar bisa **disimpan offline di local Room DB** dan di-consume tanpa koneksi internet.

Setiap config memiliki kolom **`type`** (`string`, `integer`, `float`, `boolean`, `json`). Server **sudah men-cast `value` sesuai `type`**, sehingga mobile tidak perlu menebak tipe data sendiri.

**Base URL:** `{base_url}/api/v1`
**Method:** `GET` (semua endpoint bersifat **read-only**)
**Auth:** Wajib menyertakan Sanctum token di header.

```http
Authorization: Bearer {token}
Accept: application/json
```

> **Catatan:**
> - Config bersifat **global aplikasi**, **bukan per outlet**. Tidak ada parameter `outlet_id` / filter outlet.
> - Tidak ada `id` pada response. **Primary key di Room adalah `name`** (sudah `unique` di server).
> - Config hanya bisa diakses **setelah login** (`auth:sanctum`). Endpoint ini tidak public.
> - Tidak ada endpoint `sync`/delta — seluruh config berukuran kecil, cukup di-**upsert** seluruhnya.

---

## Daftar Endpoint

| Endpoint | Keterangan |
|---|---|
| `GET /api/v1/configs` | Semua config (untuk sync ke Room). |
| `GET /api/v1/configs?name={name}` | Config terfilter berdasarkan name (bisa multiple, pemisah koma). |
| `GET /api/v1/configs/{name}` | Satu config. HTTP 404 bila tidak ditemukan. |

**Rekomendasi:** panggil `GET /api/v1/configs` **tanpa parameter** sekali saat aplikasi connect / sebelum masuk mode offline, lalu simpan semua ke Room. Endpoint `?name=` / `{name}` dipakai untuk refresh selektif satu atau beberapa config.

---

## 1. Get All Configs

### `GET /api/v1/configs`

Mengambil seluruh config. Dipakai untuk sinkronisasi awal ke Room.

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
      "name": "feature_multi_outlet",
      "value": false,
      "type": "boolean",
      "description": "Flag fitur multi outlet",
      "updated_at": "2026-09-17T07:53:55.000000Z"
    },
    {
      "name": "password_min_length",
      "value": 8,
      "type": "integer",
      "description": "Panjang minimal password untuk validasi di mobile",
      "updated_at": "2026-09-17T07:53:55.000000Z"
    },
    {
      "name": "pin_length",
      "value": 6,
      "type": "integer",
      "description": "Panjang PIN kasir untuk validasi di mobile",
      "updated_at": "2026-09-17T07:53:55.000000Z"
    },
    {
      "name": "transaction_sync_limit",
      "value": 100,
      "type": "integer",
      "description": "Jumlah maksimal data per request sinkronisasi transaksi",
      "updated_at": "2026-09-17T07:53:55.000000Z"
    }
  ],
  "missing": []
}
```

#### Penjelasan Field Response

| Field | Tipe | Keterangan |
|---|---|---|
| `status` | string | Selalu `"success"` pada response 200. |
| `data` | array | Daftar config, diurutkan `name` ascending. |
| `data[].name` | string | Key config (**unique**, jadikan primary key Room). |
| `data[].value` | dynamic | Nilai config, **sudah di-cast sesuai `type`**. Lihat tabel Casting. |
| `data[].type` | string | Tipe data: `string`, `integer`, `float`, `boolean`, `json`. |
| `data[].description` | string \| null | Deskripsi config untuk ditampilkan di UI (opsional). |
| `data[].updated_at` | string (ISO 8601) | Timestamp server, dipakai untuk mendeteksi perubahan. |
| `missing` | array&lt;string&gt; | Nama config yang diminta tapi tidak ada di server. Kosong bila request tanpa `name`. |

---

## 2. Get Configs By Name (Filter)

### `GET /api/v1/configs?name=password_min_length`

### `GET /api/v1/configs?name=password_min_length,pin_length`

Mengambil config berdasarkan `name`. Mendukung **satu atau banyak** name sekaligus dengan pemisah koma.

### Query Parameter
| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `name` | string | Tidak | Satu name, atau beberapa name dipisah koma. Spasi di sekitar koma otomatis di-trim. Bila kosong, seluruh config dikembalikan. |

### Response Sukses (HTTP 200)

Request: `GET /api/v1/configs?name=password_min_length,pin_length`

```json
{
  "status": "success",
  "data": [
    {
      "name": "password_min_length",
      "value": 8,
      "type": "integer",
      "description": "Panjang minimal password untuk validasi di mobile",
      "updated_at": "2026-09-17T07:53:55.000000Z"
    },
    {
      "name": "pin_length",
      "value": 6,
      "type": "integer",
      "description": "Panjang PIN kasir untuk validasi di mobile",
      "updated_at": "2026-09-17T07:53:55.000000Z"
    }
  ],
  "missing": []
}
```

### Contoh Ada Name Yang Tidak Ditemukan

Request: `GET /api/v1/configs?name=password_min_length,tidak_ada`

```json
{
  "status": "success",
  "data": [
    {
      "name": "password_min_length",
      "value": 8,
      "type": "integer",
      "description": "Panjang minimal password untuk validasi di mobile",
      "updated_at": "2026-09-17T07:53:55.000000Z"
    }
  ],
  "missing": ["tidak_ada"]
}
```

> **Penting:** name yang tidak ditemukan **bukan error** (tetap HTTP 200). Cek array `missing` untuk tahu config mana yang tidak ada di server. Gunakan ini untuk membersihkan config stale di Room.

---

## 3. Get Config By Name (Single)

### `GET /api/v1/configs/{name}`

Contoh: `GET /api/v1/configs/pin_length`

### Path Parameter
| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `name` | string | Ya | Name config. Hanya menerima `A-Z a-z 0-9 _ . -`. |

### Response Sukses (HTTP 200)

```json
{
  "status": "success",
  "data": {
    "name": "pin_length",
    "value": 6,
    "type": "integer",
    "description": "Panjang PIN kasir untuk validasi di mobile",
    "updated_at": "2026-09-17T07:53:55.000000Z"
  }
}
```

> Perhatikan: `data` di sini berupa **object**, bukan array.

### Response Gagal (HTTP 404)

```json
{
  "status": "error",
  "message": "Config 'tidak_ada_config' tidak ditemukan."
}
```

---

## Tabel Casting `value`

Server men-cast `value` sesuai `type` sebelum dikirim:

| `type` | `value` di DB (raw) | `value` di JSON response | Tipe JSON |
|---|---|---|---|
| `string` | `"10"` | `"10"` | string |
| `integer` | `"8"` | `8` | number |
| `float` | `"11.5"` | `11.5` | number |
| `boolean` | `"1"` / `"0"` | `true` / `false` | boolean |
| `json` | `{"max":5}` | `{"max":5}` | object |
| `json` | `[1,2,3]` | `[1,2,3]` | array |
| _(apa pun)_ | `null` | `null` | null |

### Perilaku Penting

1. **Cast gagal → fallback ke string mentah.** Bila `type=integer` tapi value di DB bukan angka (mis. `"abc"`), server mengembalikan `"abc"` sebagai string, **bukan** `0` dan bukan error. Mobile harus tetap aman menangani kasus ini.
2. **Boolean dikirim sebagai boolean JSON asli** (`true`/`false`), bukan `1`/`0`. Di DB disimpan `"1"`/`"0"`.
3. **`json` dikirim sebagai object/array asli**, bukan string berisi JSON. Jadi di mobile bentuknya nested JSON, bukan `"{\"max\":5}"`.
4. **Tipe `string` tetap string** walaupun isinya angka. Contoh `type=string` dengan value `"10"` dikirim sebagai `"10"` (string), **bukan** `10`.

---

## Response Error

### 1. Token Tidak Valid / Kedaluwarsa (HTTP 401)

Berlaku untuk semua endpoint.

```json
{
  "message": "Unauthenticated."
}
```

### 2. Config Tidak Ditemukan (HTTP 404)

Hanya pada `GET /api/v1/configs/{name}`.

```json
{
  "status": "error",
  "message": "Config 'nama_config' tidak ditemukan."
}
```

---

## Panduan Integrasi Mobile POS (Android)

### 1. Masalah Utama: `value` Bertipe Dinamis

Field `value` bisa berupa **number, boolean, string, object, array, atau null** tergantung `type`. Jadi **jangan** deklarasikan `value` sebagai `Int`, `String`, atau `Boolean` langsung — akan gagal deserialisasi.

Deklarasikan sebagai `JsonElement` (Gson / kotlinx.serialization), lalu cast manual memakai `type`.

### 2. DTO (Gson)

```kotlin
import com.google.gson.JsonElement
import com.google.gson.annotations.SerializedName

data class ConfigListResponse(
    val status: String,
    val data: List<ConfigDto>,
    val missing: List<String> = emptyList()
)

data class ConfigDetailResponse(
    val status: String,
    val data: ConfigDto
)

data class ConfigDto(
    val name: String,
    val value: JsonElement?,
    val type: String,
    val description: String?,
    @SerializedName("updated_at") val updatedAt: String?
)
```

### 3. Room Entity & DAO

Simpan `value` sebagai **raw JSON string** (`JsonElement.toString()`), agar bisa menampung semua tipe tanpa konverter tambahan.

```kotlin
@Entity(tableName = "configs")
data class ConfigEntity(
    @PrimaryKey val name: String,
    val value: String?,      // raw JSON, mis. "8", "true", "\"10\"", "{\"max\":5}"
    val type: String,        // "integer" | "float" | "boolean" | "json" | "string"
    val description: String?,
    val updatedAt: String?
)

@Dao
interface ConfigDao {
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(items: List<ConfigEntity>)

    @Query("SELECT * FROM configs WHERE name = :name LIMIT 1")
    suspend fun find(name: String): ConfigEntity?

    @Query("DELETE FROM configs WHERE name IN (:names)")
    suspend fun deleteByNames(names: List<String>)
}
```

### 4. Helper Casting di Sisi Mobile

Helper ini membaca ulang raw JSON dan mengembalikan tipe yang benar. Aman terhadap value yang tidak valid (mengembalikan `null`).

```kotlin
import com.google.gson.JsonParser

object ConfigValue {
    private val parser = JsonParser()

    fun asString(raw: String?): String? {
        val el = raw?.let { runCatching { parser.parse(it) }.getOrNull() } ?: return null
        if (el.isJsonNull) return null
        return if (el.isJsonPrimitive && el.asJsonPrimitive.isString) el.asString
        else el.toString()
    }

    fun asInt(raw: String?): Int? =
        raw?.let { runCatching { parser.parse(it).asInt }.getOrNull() }

    fun asLong(raw: String?): Long? =
        raw?.let { runCatching { parser.parse(it).asLong }.getOrNull() }

    fun asFloat(raw: String?): Float? =
        raw?.let { runCatching { parser.parse(it).asFloat }.getOrNull() }

    fun asBoolean(raw: String?): Boolean? =
        raw?.let { runCatching { parser.parse(it).asBoolean }.getOrNull() }

    fun asJson(raw: String?): JsonElement? =
        raw?.let { runCatching { parser.parse(it) }.getOrNull() }
}
```

Contoh pemakaian di repository:

```kotlin
class ConfigRepository(private val dao: ConfigDao) {

    suspend fun refresh(api: ConfigApi) {
        val response = api.getAllConfigs()
        dao.upsertAll(response.data.map { it.toEntity() })
        // name yang tidak ada di server -> bersihkan dari Room
        if (response.missing.isNotEmpty()) dao.deleteByNames(response.missing)
    }

    suspend fun getInt(name: String, default: Int): Int {
        val entity = dao.find(name) ?: return default
        if (entity.type != "integer") return default
        return ConfigValue.asInt(entity.value) ?: default
    }

    suspend fun getBoolean(name: String, default: Boolean): Boolean {
        val entity = dao.find(name) ?: return default
        if (entity.type != "boolean") return default
        return ConfigValue.asBoolean(entity.value) ?: default
    }

    suspend fun getString(name: String, default: String): String {
        val entity = dao.find(name) ?: return default
        return ConfigValue.asString(entity.value) ?: default
    }
}

private fun ConfigDto.toEntity() = ConfigEntity(
    name = name,
    value = value?.toString(),   // simpan raw JSON
    type = type,
    description = description,
    updatedAt = updatedAt
)
```

> **Catatan `value?.toString()`:** untuk `type=string` dengan value `10`, hasilnya `"\"10\""` (ada kutip). Itulah sebabnya `asString()` di atas memeriksa `isJsonPrimitive.isString` sebelum memutuskan apakah perlu melepas kutip. Jangan pakai `el.asString` untuk semua tipe.

### 5. Contoh Pemakaian untuk Validasi

```kotlin
val minPasswordLength = configRepository.getInt("password_min_length", default = 8)
val pinLength         = configRepository.getInt("pin_length", default = 6)
val multiOutlet       = configRepository.getBoolean("feature_multi_outlet", default = false)

fun isValidPassword(input: String) = input.length >= minPasswordLength
fun isValidPin(input: String) = input.length == pinLength
```

### 6. Strategi Sinkronisasi

1. Panggil `GET /api/v1/configs` (tanpa parameter) **setelah login** dan setiap kali aplikasi connect.
2. `upsert` seluruh `data` ke Room berdasarkan `name` (`OnConflictStrategy.REPLACE`).
3. Bila memakai `?name=`, hapus dari Room name yang muncul di array `missing`.
4. Karena response kecil dan tidak ada delta sync, **selalu replace seluruh tabel** adalah strategi yang paling sederhana dan aman.
5. Selalu sediakan **default value di mobile** untuk setiap config yang dipakai. Jangan asumsikan config selalu ada, karena:
   - config bisa belum di-seed di server,
   - bisa saja dihapus admin,
   - aplikasi harus tetap jalan saat pertama kali install tanpa koneksi.

### 7. Catatan Penting

- **Jangan hardcode** batas validasi (panjang password, panjang PIN, dsb) di mobile. Ambil dari endpoint ini agar bisa diubah tanpa rilis APK baru.
- Config bersifat **global**, jadi tidak perlu di-scope per outlet saat menyimpan di Room.
- `description` hanya untuk keperluan tampilan/debug, bukan untuk logika.
- Bila satu config **kritis** untuk jalannya aplikasi, sediakan fallback default di mobile, bukan menolak jalan.

---

## Catatan untuk Backend

### Menambah / Mengubah Config

Config dikelola di server (belum ada endpoint admin CRUD). Cara menambah:

**Via seeder** — `database/seeders/ConfigSeeder.php`:

```php
[
    'name'        => 'pin_length',
    'type'        => 'integer',
    'value'       => '6',
    'description' => 'Panjang PIN kasir untuk validasi di mobile',
],
```

Seeder memakai `firstOrCreate`, jadi menjalankannya ulang **tidak** menimpa value yang sudah diubah.

**Via helper model:**

```php
use App\Models\Config;

Config::setValue('pin_length', 6, 'Panjang PIN kasir', 'integer');
Config::setValue('feature_multi_outlet', true, 'Flag fitur multi outlet', 'boolean');
Config::setValue('tax_rules', ['max' => 11], 'Aturan pajak', 'json');
```

`setValue()` otomatis men-serialize: `boolean` → `'1'`/`'0'`, array/object → JSON string.

**Membaca value (sudah di-cast):**

```php
Config::getValue('pin_length', 6);              // int 6
Config::getValue('feature_multi_outlet', false); // bool
Config::getValue('belum_ada', 'default');        // 'default'
```

### Struktur Tabel `configs`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | — |
| `name` | string | **unique**, key config. |
| `type` | string | `string` (default), `integer`, `float`, `boolean`, `json`. |
| `value` | text, nullable | Nilai mentah; disimpan sebagai string/JSON. |
| `description` | text, nullable | Deskripsi. |
| `created_at` / `updated_at` | timestamp | — |

### Endpoint Internal

| Method | URL | Handler |
|---|---|---|
| GET | `/api/v1/configs` | `Api\ConfigController@index` |
| GET | `/api/v1/configs/{name}` | `Api\ConfigController@show` |

Route terdaftar di `routes/api.php` di dalam group `auth:sanctum` + `token.expiry`.
