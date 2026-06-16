<?php

namespace App\Enums;

enum ProcurementMode: int
{
    case OFFLINE = 1;
    case ONLINE = 2;
    case BOTH = 3;

    public function label(): string
    {
        return match ($this) {
            self::OFFLINE => 'Offline',
            self::ONLINE => 'Online',
            self::BOTH => 'Keduanya',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $mode) => $mode->value, self::cases());
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $mode) {
            $options[$mode->value] = $mode->label();
        }

        return $options;
    }

    public static function labelFor(self|int|string|null $value): string
    {
        if ($value instanceof self) {
            return $value->label();
        }

        if ($value === null || $value === '') {
            return '-';
        }

        return self::tryFrom((int) $value)?->label() ?? '-';
    }
}
