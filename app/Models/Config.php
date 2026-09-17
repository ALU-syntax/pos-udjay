<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Tipe config yang didukung beserta validasinya.
     */
    public const SUPPORTED_TYPES = ['string', 'integer', 'float', 'boolean', 'json'];

    /**
     * Cast value mentah (string dari DB) ke tipe sesuai kolom type.
     *
     * Dipakai oleh API agar mobile menerima tipe yang benar tanpa harus
     * menebak sendiri. Jika cast gagal (data tidak valid), value dikembalikan
     * apa adanya sebagai string agar tidak memutus response.
     */
    public function castedValue(): mixed
    {
        return static::cast($this->type, $this->value);
    }

    /**
     * Cast value mentah ke tipe tertentu.
     */
    public static function cast(?string $type, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false
                ? (int) $value
                : (string) $value,
            'float' => is_numeric($value) ? (float) $value : (string) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                ?? (string) $value,
            'json' => static::castJson($value),
            default => (string) $value,
        };
    }

    /**
     * Decode JSON; kembalikan string asli jika bukan JSON yang valid.
     */
    protected static function castJson(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /**
     * Ambil value config berdasarkan name, sudah di-cast sesuai type.
     *
     * @param  mixed  $default  Nilai default jika config tidak ditemukan
     */
    public static function getValue(string $name, mixed $default = null): mixed
    {
        $config = static::where('name', $name)->first();

        return $config?->castedValue() ?? $default;
    }

    /**
     * Simpan/perbarui value config berdasarkan name.
     *
     * @param  string|null  $type  Salah satu dari SUPPORTED_TYPES; jika null,
     *                             tipe existing dipertahankan (atau 'string').
     */
    public static function setValue(
        string $name,
        mixed $value,
        ?string $description = null,
        ?string $type = null
    ): static {
        $attributes = [
            'value' => static::serializeValue($value),
        ];

        if ($description !== null) {
            $attributes['description'] = $description;
        }

        if ($type !== null) {
            $attributes['type'] = $type;
        }

        return static::updateOrCreate(['name' => $name], $attributes);
    }

    /**
     * Ubah value PHP menjadi string yang disimpan di kolom value.
     */
    protected static function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Susun payload config untuk dikonsumsi mobile.
     */
    public function toApiArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->castedValue(),
            'type' => $this->type,
            'description' => $this->description,
            'updated_at' => $this->updated_at,
        ];
    }
}
