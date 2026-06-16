<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $channelTypeIds = $this->seedChannelTypes($now);

            $rows = array_merge(
                $this->parseFirstSheet($this->rawSheetOne()),
                $this->parseSecondSheet($this->rawSheetTwo())
            );

            $supplierRows = [];
            $supplierMeta = [];

            foreach ($rows as $row) {
                $supplierNames = $this->splitSupplierNames($row['supplier_raw'] ?? '');

                if (empty($supplierNames)) {
                    continue;
                }

                foreach ($supplierNames as $supplierName) {
                    $mode = $this->normalizeMode(
                        $row['mode_raw'] ?? null,
                        $row['order_place'] ?? null
                    );

                    $normalizedRow = array_merge($row, [
                        'supplier' => $supplierName,
                        'row_mode' => $mode,
                    ]);

                    $supplierRows[$supplierName][] = $normalizedRow;

                    if (!isset($supplierMeta[$supplierName])) {
                        $supplierMeta[$supplierName] = [
                            'modes' => [],
                            'materials' => [],
                        ];
                    }

                    if ($mode !== null) {
                        $supplierMeta[$supplierName]['modes'][$mode] = true;
                    }

                    $materialName = $this->clean($row['material_name'] ?? '');
                    if ($materialName !== '') {
                        $supplierMeta[$supplierName]['materials'][$materialName] = true;
                    }
                }
            }

            ksort($supplierRows);

            $supplierIds = [];
            foreach ($supplierRows as $supplierName => $rowsForSupplier) {
                $materials = array_keys($supplierMeta[$supplierName]['materials'] ?? []);
                sort($materials);

                $supplierIds[$supplierName] = $this->upsertSupplier(
                    $supplierName,
                    $this->resolveSupplierMode(array_keys($supplierMeta[$supplierName]['modes'] ?? [])),
                    $materials,
                    $now
                );
            }

            foreach ($supplierRows as $supplierName => $rowsForSupplier) {
                $supplierId = $supplierIds[$supplierName];

                foreach ($rowsForSupplier as $row) {
                    $this->seedContactFromRow($supplierId, $row, $now);
                    $this->seedChannelFromRow($supplierId, $row, $channelTypeIds, $now);
                }
            }

            $this->seedOperationalHours($supplierRows, $supplierIds, $now);
        });
    }

    private function seedChannelTypes($now): array
    {
        $names = [
            'WhatsApp',
            'Application',
            'Marketplace',
            'Offline Store',
            'By Request',
            'Other',
        ];

        $ids = [];

        foreach ($names as $name) {
            $existing = DB::table('supplier_channel_types')->where('name', $name)->first();

            if ($existing) {
                DB::table('supplier_channel_types')
                    ->where('id', $existing->id)
                    ->update(['updated_at' => $now]);

                $ids[$name] = $existing->id;
            } else {
                $ids[$name] = DB::table('supplier_channel_types')->insertGetId([
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return $ids;
    }

    private function upsertSupplier(string $name, string $mode, array $materials, $now): int
    {
        $existing = DB::table('suppliers')->where('name', $name)->first();
        $notes = 'Import awal dari spreadsheet supplier bahan baku. Bahan terkait: '
            . Str::limit(implode(', ', $materials), 1800, '...');

        $payload = [
            'procurement_mode' => $mode,
            'is_active' => true,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if (!$existing || blank($existing->code)) {
            $payload['code'] = $this->makeSupplierCode($name);
        }

        if (!$existing || blank($existing->notes)) {
            $payload['notes'] = $notes;
        }

        if ($existing) {
            DB::table('suppliers')->where('id', $existing->id)->update($payload);
            return (int) $existing->id;
        }

        $payload['name'] = $name;
        $payload['created_at'] = $now;

        return (int) DB::table('suppliers')->insertGetId($payload);
    }

    private function makeSupplierCode(string $supplierName): string
    {
        return 'SUP-' . strtoupper(substr(md5(Str::lower($supplierName)), 0, 8));
    }

    private function seedContactFromRow(int $supplierId, array $row, $now): void
    {
        $contactName = $this->clean($row['contact_name'] ?? '');
        $phones = $this->splitPhones($row['cp'] ?? '');

        if (empty($phones) && $contactName === '') {
            return;
        }

        if (empty($phones)) {
            $this->upsertContact($supplierId, $contactName, null, null, $now);
            return;
        }

        foreach ($phones as $phone) {
            $this->upsertContact($supplierId, $contactName !== '' ? $contactName : null, $phone, $phone, $now);
        }
    }

    private function upsertContact(int $supplierId, ?string $name, ?string $phone, ?string $whatsappPhone, $now): void
    {
        $query = DB::table('supplier_contacts')
            ->where('supplier_id', $supplierId)
            ->whereNull('deleted_at');

        $phone === null ? $query->whereNull('phone') : $query->where('phone', $phone);
        $whatsappPhone === null ? $query->whereNull('whatsapp_phone') : $query->where('whatsapp_phone', $whatsappPhone);

        if ($name !== null) {
            $query->where(function ($q) use ($name) {
                $q->where('name', $name)->orWhereNull('name');
            });
        }

        $existing = $query->first();
        $hasPrimary = DB::table('supplier_contacts')
            ->where('supplier_id', $supplierId)
            ->whereNull('deleted_at')
            ->where('is_primary', true)
            ->exists();

        $payload = [
            'name' => $name,
            'phone' => $phone,
            'whatsapp_phone' => $whatsappPhone,
            'position' => null,
            'is_active' => true,
            'notes' => null,
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('supplier_contacts')->where('id', $existing->id)->update($payload);
            return;
        }

        $payload['supplier_id'] = $supplierId;
        $payload['is_primary'] = !$hasPrimary;
        $payload['created_at'] = $now;

        DB::table('supplier_contacts')->insert($payload);
    }

    private function seedChannelFromRow(int $supplierId, array $row, array $channelTypeIds, $now): void
    {
        $channelTypeName = $this->detectChannelType($row['order_place'] ?? '', $row['cp'] ?? '', $row['row_mode'] ?? null);
        $channelTypeId = $channelTypeIds[$channelTypeName] ?? $channelTypeIds['Other'];

        $channelName = $this->normalizeChannelName($row['order_place'] ?? '', $channelTypeName, $row['supplier'] ?? '');
        $cp = $this->clean($row['cp'] ?? '');
        $phones = $this->splitPhones($cp);
        $url = $this->extractUrl($cp);

        if (empty($phones)) {
            $this->upsertChannel(
                supplierId: $supplierId,
                channelTypeId: $channelTypeId,
                channelName: $channelName,
                identifier: $url ? null : ($cp !== '' && $cp !== '-' ? $cp : null),
                url: $url,
                address: $channelTypeName === 'Offline Store' ? $channelName : null,
                now: $now
            );

            return;
        }

        foreach ($phones as $phone) {
            $this->upsertChannel(
                supplierId: $supplierId,
                channelTypeId: $channelTypeId,
                channelName: $channelName,
                identifier: $phone,
                url: $url,
                address: $channelTypeName === 'Offline Store' ? $channelName : null,
                now: $now
            );
        }
    }

    private function upsertChannel(
        int $supplierId,
        int $channelTypeId,
        ?string $channelName,
        ?string $identifier,
        ?string $url,
        ?string $address,
        $now
    ): void {
        $query = DB::table('supplier_order_channels')
            ->where('supplier_id', $supplierId)
            ->where('channel_type_id', $channelTypeId)
            ->whereNull('deleted_at');

        $channelName === null ? $query->whereNull('channel_name') : $query->where('channel_name', $channelName);
        $identifier === null ? $query->whereNull('identifier') : $query->where('identifier', $identifier);
        $url === null ? $query->whereNull('url') : $query->where('url', $url);

        $existing = $query->first();
        $hasPrimary = DB::table('supplier_order_channels')
            ->where('supplier_id', $supplierId)
            ->whereNull('deleted_at')
            ->where('is_primary', true)
            ->exists();

        $payload = [
            'channel_name' => $channelName,
            'identifier' => $identifier,
            'url' => $url,
            'address' => $address,
            'is_active' => true,
            'notes' => null,
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('supplier_order_channels')->where('id', $existing->id)->update($payload);
            return;
        }

        $payload['supplier_id'] = $supplierId;
        $payload['channel_type_id'] = $channelTypeId;
        $payload['is_primary'] = !$hasPrimary;
        $payload['created_at'] = $now;

        DB::table('supplier_order_channels')->insert($payload);
    }

    private function seedOperationalHours(array $supplierRows, array $supplierIds, $now): void
    {
        $sessions = [];

        foreach ($supplierRows as $supplierName => $rows) {
            foreach ($rows as $row) {
                $days = $this->daysFromSchedule($row['schedule'] ?? '');

                if (empty($days)) {
                    continue;
                }

                [$openTime, $closeTime, $is24Hours] = $this->parseTimeRange($row['hours'] ?? '');

                foreach ($days as $day) {
                    $key = implode('|', [
                        $day,
                        $openTime ?? 'NULL',
                        $closeTime ?? 'NULL',
                        $is24Hours ? '24' : '0',
                    ]);

                    $sessions[$supplierName][$day][$key] = [
                        'day_of_week' => $day,
                        'open_time' => $openTime,
                        'close_time' => $closeTime,
                        'is_24_hours' => $is24Hours,
                        'notes' => $this->clean($row['hours'] ?? '') === '' || $this->clean($row['hours'] ?? '') === '-'
                            ? 'Jam operasional belum diisi pada data awal.'
                            : null,
                    ];
                }
            }
        }

        foreach ($sessions as $supplierName => $sessionsByDay) {
            $supplierId = $supplierIds[$supplierName];

            foreach ($sessionsByDay as $day => $daySessions) {
                $daySessions = array_values($daySessions);

                usort($daySessions, function (array $a, array $b): int {
                    return strcmp((string) $a['open_time'], (string) $b['open_time'])
                        ?: strcmp((string) $a['close_time'], (string) $b['close_time']);
                });

                foreach ($daySessions as $index => $session) {
                    DB::table('supplier_operational_hours')->updateOrInsert(
                        [
                            'supplier_id' => $supplierId,
                            'day_of_week' => $day,
                            'sequence' => $index + 1,
                        ],
                        [
                            'open_time' => $session['open_time'],
                            'close_time' => $session['close_time'],
                            'is_24_hours' => $session['is_24_hours'],
                            'notes' => $session['notes'],
                            'created_at' => $now,
                            'updated_at' => $now,
                            'deleted_at' => null,
                        ]
                    );
                }
            }
        }
    }

    private function parseFirstSheet(string $raw): array
    {
        $rows = [];

        foreach (preg_split('/\R/u', $raw) as $lineNumber => $line) {
            $columns = $this->splitTsvLine($line);

            if (empty($columns) || ($columns[0] ?? '') === 'No') {
                continue;
            }

            if (count($columns) < 7) {
                continue;
            }

            $materialName = $columns[1] ?? '';
            $supplierRaw = $columns[2] ?? '';

            if ($this->clean($materialName) === '' && $this->clean($supplierRaw) === '') {
                continue;
            }

            $rows[] = [
                'source' => 'sheet_1',
                'line' => $lineNumber + 1,
                'no' => $columns[0] ?? null,
                'material_name' => $materialName,
                'brand' => null,
                'supplier_raw' => $supplierRaw,
                'mode_raw' => $columns[3] ?? null,
                'schedule' => $columns[4] ?? null,
                'hours' => $columns[5] ?? null,
                'order_place' => $columns[6] ?? null,
                'cp' => $columns[7] ?? null,
                'contact_name' => $columns[8] ?? null,
            ];
        }

        return $rows;
    }

    private function parseSecondSheet(string $raw): array
    {
        $rows = [];

        foreach (preg_split('/\R/u', $raw) as $lineNumber => $line) {
            $columns = $this->splitTsvLine($line);

            if (empty($columns) || ($columns[0] ?? '') === 'No') {
                continue;
            }

            if (count($columns) < 2) {
                continue;
            }

            $materialName = $columns[1] ?? '';

            if ($this->clean($materialName) === '') {
                continue;
            }

            $brand = null;
            $supplierRaw = null;
            $modeRaw = null;
            $schedule = null;
            $hours = null;
            $orderPlace = null;
            $cp = null;
            $contactName = null;

            // Format bawah file kedua kadang: No, Bahan, Supplier, Online/Offline, Jam, Tempat Pesan, CP.
            if (isset($columns[3]) && $this->isModeValue($columns[3])) {
                $supplierRaw = $columns[2] ?? null;
                $modeRaw = $columns[3] ?? null;

                if (isset($columns[4]) && $this->looksLikeTimeRange($columns[4])) {
                    $schedule = null;
                    $hours = $columns[4] ?? null;
                    $orderPlace = $columns[5] ?? null;
                    $cp = $columns[6] ?? null;
                    $contactName = $columns[7] ?? null;
                } else {
                    $schedule = $columns[4] ?? null;
                    $hours = $columns[5] ?? null;
                    $orderPlace = $columns[6] ?? null;
                    $cp = $columns[7] ?? null;
                    $contactName = $columns[8] ?? null;
                }
            } else {
                // Format atas file kedua: No, Bahan, Brand/Merk, Supplier, Jadwal, Jam, Tempat Pesan, CP, Nama CP.
                $brand = $columns[2] ?? null;
                $supplierRaw = $columns[3] ?? null;
                $schedule = $columns[4] ?? null;
                $hours = $columns[5] ?? null;
                $orderPlace = $columns[6] ?? null;
                $cp = $columns[7] ?? null;
                $contactName = $columns[8] ?? null;
            }

            $rows[] = [
                'source' => 'sheet_2',
                'line' => $lineNumber + 1,
                'no' => $columns[0] ?? null,
                'material_name' => $materialName,
                'brand' => $brand,
                'supplier_raw' => $supplierRaw,
                'mode_raw' => $modeRaw,
                'schedule' => $schedule,
                'hours' => $hours,
                'order_place' => $orderPlace,
                'cp' => $cp,
                'contact_name' => $contactName,
            ];
        }

        return $rows;
    }

    private function splitTsvLine(string $line): array
    {
        $columns = explode("\t", $line);

        $columns = array_map(fn ($value) => $this->clean($value), $columns);

        while (!empty($columns) && end($columns) === '') {
            array_pop($columns);
        }

        return $columns;
    }

    private function splitSupplierNames(?string $raw): array
    {
        $raw = $this->clean($raw);

        if ($raw === '' || $raw === '-') {
            return [];
        }

        $names = preg_split('/\s*\/\s*/', $raw) ?: [];

        return collect($names)
            ->map(fn ($name) => $this->canonicalSupplierName($name))
            ->filter(fn ($name) => $name !== '' && $name !== '-')
            ->unique()
            ->values()
            ->all();
    }

    private function canonicalSupplierName(?string $name): string
    {
        $name = $this->clean($name);

        $aliases = [
            'Anuggrah Rempah' => 'Anugrah Rempah',
            'Lotte' => 'Lotte Mart',
            'Allfresh' => 'All Fresh',
            'Suplier Trashbag (Dimas)' => 'Supplier Trashbag (Dimas)',
        ];

        return $aliases[$name] ?? $name;
    }

    private function resolveSupplierMode(array $modes): string
    {
        $modes = array_values(array_unique(array_filter($modes)));

        if (in_array('both', $modes, true)) {
            return 'both';
        }

        if (in_array('online', $modes, true) && in_array('offline', $modes, true)) {
            return 'both';
        }

        if (in_array('online', $modes, true)) {
            return 'online';
        }

        if (in_array('offline', $modes, true)) {
            return 'offline';
        }

        return 'both';
    }

    private function normalizeMode(?string $rawMode, ?string $orderPlace = null): ?string
    {
        $rawMode = Str::of($this->clean($rawMode))->lower()->replace(' ', '')->replace('offiline', 'offline')->toString();

        if ($rawMode === 'online/offline' || $rawMode === 'online/offline/offline') {
            return 'both';
        }

        if ($rawMode === 'online') {
            return 'online';
        }

        if ($rawMode === 'offline') {
            return 'offline';
        }

        $orderPlace = Str::lower($this->clean($orderPlace));

        if (Str::contains($orderPlace, ['whatsapp', 'wa', 'aplikasi', 'tokopedia', 'shopee', 'request'])) {
            return 'online';
        }

        if ($orderPlace !== '' && $orderPlace !== '-') {
            return 'offline';
        }

        return null;
    }

    private function isModeValue(?string $value): bool
    {
        $value = Str::of($this->clean($value))->lower()->replace(' ', '')->replace('offiline', 'offline')->toString();

        return in_array($value, ['online', 'offline', 'online/offline', 'online/offline/offline'], true);
    }

    private function detectChannelType(?string $orderPlace, ?string $cp, ?string $mode): string
    {
        $orderPlace = Str::lower($this->clean($orderPlace));
        $cp = Str::lower($this->clean($cp));

        if (Str::contains($orderPlace, ['tokopedia', 'shopee']) || Str::contains($cp, ['tokopedia', 'shp.ee'])) {
            return 'Marketplace';
        }

        if (Str::contains($orderPlace, ['whatsapp', 'wa'])) {
            return 'WhatsApp';
        }

        if (Str::contains($orderPlace, ['aplikasi'])) {
            return 'Application';
        }

        if (Str::contains($orderPlace, ['request'])) {
            return 'By Request';
        }

        if ($orderPlace !== '' && $orderPlace !== '-') {
            return $mode === 'offline' ? 'Offline Store' : 'Other';
        }

        return $mode === 'offline' ? 'Offline Store' : 'Other';
    }

    private function normalizeChannelName(?string $orderPlace, string $channelTypeName, string $supplierName): ?string
    {
        $orderPlace = $this->clean($orderPlace);

        if ($orderPlace === '' || $orderPlace === '-') {
            return $channelTypeName === 'Offline Store' ? $supplierName : $channelTypeName;
        }

        if (Str::lower($orderPlace) === 'whatsapp') {
            return 'WhatsApp';
        }

        return $orderPlace;
    }

    private function splitPhones(?string $raw): array
    {
        $raw = $this->clean($raw);

        if ($raw === '' || $raw === '-' || Str::startsWith(Str::lower($raw), 'http')) {
            return [];
        }

        $chunks = preg_split('/[\/,;]|\bor\b|\bdan\b/i', $raw) ?: [];
        $phones = [];

        foreach ($chunks as $chunk) {
            $phone = preg_replace('/\D+/', '', $chunk);

            if (!$phone || strlen($phone) < 7) {
                continue;
            }

            if (Str::startsWith($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $phones[] = $phone;
        }

        return array_values(array_unique($phones));
    }

    private function extractUrl(?string $value): ?string
    {
        $value = $this->clean($value);

        if (preg_match('/https?:\/\/\S+/i', $value, $matches)) {
            return $matches[0];
        }

        return null;
    }

    private function daysFromSchedule(?string $schedule): array
    {
        $schedule = Str::of($this->clean($schedule))->lower()->replace(' ', '')->toString();

        if ($schedule === '') {
            return [];
        }

        if (Str::contains($schedule, 'setiaphari')) {
            return [1, 2, 3, 4, 5, 6, 7];
        }

        if (Str::contains($schedule, 'senin-sabtu')) {
            return [1, 2, 3, 4, 5, 6];
        }

        if (Str::contains($schedule, 'senin-jumat')) {
            return [1, 2, 3, 4, 5];
        }

        return [];
    }

    private function parseTimeRange(?string $rawHours): array
    {
        $rawHours = $this->clean($rawHours);

        if (Str::contains(Str::lower($rawHours), ['24 jam', '24hours', '24 hours'])) {
            return [null, null, true];
        }

        if (!preg_match('/(\d{1,2})[\.:](\d{2})\s*-\s*(\d{1,2})[\.:](\d{2})/', $rawHours, $matches)) {
            return [null, null, false];
        }

        return [
            sprintf('%02d:%02d:00', (int) $matches[1], (int) $matches[2]),
            sprintf('%02d:%02d:00', (int) $matches[3], (int) $matches[4]),
            false,
        ];
    }

    private function looksLikeTimeRange(?string $value): bool
    {
        return preg_match('/\d{1,2}[\.:]\d{2}\s*-\s*\d{1,2}[\.:]\d{2}/', $this->clean($value)) === 1;
    }

    private function clean(?string $value): string
    {
        $value = str_replace("\xC2\xA0", ' ', (string) $value);
        $value = trim($value);

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function rawSheetOne(): string
    {
        return <<<'RAW_SUPPLIER_SHEET_1'
No	Nama Bahan Baku	Tempat Pembelian/Supplier	Online/Offline	Jadwal Operasional	Jam Operasional	Tempat Pemesanan	CP
1	Beans Filter	Budiman Roastery	Online/Offline	Setiap Hari	-	By Request	-
2	Beans Filter Special	Budiman Roastery	Online/Offline	Setiap Hari	-	By Request	-
3	Beans Full Arabika Classic	Budiman Roastery	Online	Setiap Hari	08.00 - 18.00	Whatsapp	82111313514
4	Beans Full Arabila Modern	Budiman Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
5	Dandang Black Tea	Lotte Mart/Grand	Online	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
6	Blushing Berry Tea	Chemistea	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	85173146655
7	Choco Cookies Tea	Chemistea	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	85173146655
8	Fizzy Summer Tea	Chemistea	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	85173146655
9	Earl Grey CTC	Chemistea	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	85173146655
10	Creamer Rich Ice Hot 	Howki Gondang Perkasa	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	81280415126
11	Coconut Milk	Ricky Jaya Supplier	Online	Senin - Jumat	09.00 - 16.00	Whatsapp	81315989911
12	Fresh Milk Greenfields	Surya Anugrah Sentosa PT	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	83824567910
13	Fresh Milk Diamond	Sukanda Djaya	Online	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	81919291106
14	Brookfarm Whipping Cream	Sukanda Djaya	Online	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	81919291106
	Millac Gold	Sukanda Djaya	Online	Senin - Sabtu	08.00 - 15.01	Aplikasi Sukanda
15	UHT	Sukanda Djaya	Online	Senin - Sabtu	08.00 - 15.02	Aplikasi Sukanda	081919291106
16	Oatside	Sukanda Djaya	Online	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	85718916419
17	Ice Cream Diamond	Sukanda Djaya	Online	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	81919291106
18	Yogurt	Sukanda Djaya	Online	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	81919291106
19	Pristine	RUSH Supplier	Online	Setiap hari	08.00 - 17.00	Whatsapp	82299155585
20	Paper Filter	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
21	Denali Syrup Caramel	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
22	Denali Syrup Cranberry	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
23	Denali Syrup Hazelnut	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
24	Denali Syrup Lychee	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
25	Denali Syrup Mojito Mint	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
26	Denali Syrup Peach	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
27	Denali Syrup Roun	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
28	Denali Syrup Vanila	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
29	Denali Syrup Greenapple	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
30	Denali Syrup Butterscootch	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
31	Denali Red Velvet Powder	Kelana Roastery	Online	Setiap Hari	10.00 - 19.00	Whatsapp	82124414641
32	Giffard Bitter	Naraca Giffard Supplier	Online	Senin - Sabtu	09.00 - 16.00	Whatsapp	81381687783
33	Giffard Pineapple	Naraca Giffard Supplier	Online	Senin - Sabtu	09.00 - 16.00	Whatsapp	81381687783
34	Sunquick Orange	Grand/Lotte	Offline	Setiap Hari	09.00 - 17.00		81295900034/08881938602
35	Greentea Powder	Dillco 	Online	Senin - Jumat	09.00 - 16.00	WhatsApp	081120040777
36	Chocolate Powder	Dillco 	Online	Senin - Jumat	09.00 - 16.00	WhatsApp	081120040777
37	Gula Aren Pigo	Toko Bahan Kue Yoeks	Offline	Senin - Sabtu	09.00 - 16.00	Yoeks	-
38	Gula Pasir Rosebrand	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	WhatsApp	81295900034/08881938602
39	SKM Carnation	Surya Anugrah Sentosa PT	Online	Senin - Sabtu	09.00 - 17.00	WhatsApp
40	Schweppes Tonic Water	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	WhatsApp	81295900034/08881938602
41	Juice Apple 	Prima Squeeze	Online	Senin - Sabtu	09.00 - 17.00	WhatsApp	81295370993
42	Juice Lemon	Prima Squeeze	Online	Senin - Sabtu	09.00 - 17.00	WhatsApp	81295370993
43	Juice Lime	Prima Squeeze	Online	Senin - Sabtu	09.00 - 17.00	WhatsApp	81295370993
44	Juice Orange Jungle Juice	Supermarket	Offline	Setiap Hari	09.00 - 20.00	WhatsApp	-
45	Yakult	Supplier Yakult	Offline	Senin - Sabtu	09.00 - 16.00	WhatsApp	8118450695
46	Keju Meg Creamcheese	Silaris Indonesia	Online	Senin - Sabtu	09.00 - 17.00	WhatsApp	89652288287
47	Dried Sunkist	Budiman Roastery	Online	Senin - Sabtu	09.00 - 17.00	WhatsApp	81380367558
48	Dried Lemon	Budiman Roastery	Online	Senin - Sabtu	09.00 - 17.00	WhatsApp	81380367558
49	Edible Flower	Ijo Edible Flower	Online	Senin - Sabtu	08.00 - 17.00	WhatsApp	81222008030
50	Daun Mint	Sayurbox/Anugrah Rempah	Online	Setiap Hari	06.00 - 23.00	Aplikasi Sayurbox	-
51	Cinnamon Powder	Tokopedia	Online	Setiap Hari		Tokopedia	-
52	Cinnamon Stick	Sayurbox	Online	Setiap Hari	06.00 - 23.00	Aplikasi Sayurbox	-
53	Lychee Buah	Prambanan Kencana/Yoeks/Grand	Online/Offiline/Offline	Senin - Sabtu	09.00 - 16.00	Yoeks	-
54	Buah Lemon	Lotte Mart	Online/offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
55	Strawberry Jam 	Koerinci	Online	Setiap Hari		Whatsapp	62 853-7762-3513	Jeri
56	Brownies	Your Bake Friends	Online	Setiap Hari	07.00 - 20.00	Whatsapp	8561171284
57	Cookies	Your Bake Friends	Online	Setiap Hari	07.00 - 20.00	Whatsapp	8561171284
58	Butter Croissant	Imah Kopi Bandung	Online	Setiap Hari	08.00 - 21.00	Whatsapp	085222888987
59	Almond Chocolate	Imah Kopi Bandung	Online	Setiap Hari	08.00 - 21.00	Whatsapp	085222888987
60	Danish Creamcheese	Imah Kopi Bandung	Online	Setiap Hari	08.00 - 21.00	Whatsapp	085222888987
61	Choco Cinnamon	Imah Kopi Bandung	Online	Setiap Hari	08.00 - 21.00	Whatsapp	085222888987
62	Blueberry Apple	Imah Kopi Bandung	Online	Setiap Hari	08.00 - 21.00	Whatsapp	085222888987
63	Donat Reguler	Dandi Hagu	Online	Setiap Hari	08.00 - 21.00	Whatsapp	081287457004
64	Cheesecake Ori	Supplier Cheesecake	Online	Setiap Hari	08.00 - 21.00	Whatsapp	811887726
65	Cheesecake Matcha	Supplier Cheesecake	Online	Setiap Hari	08.00 - 21.00	Whatsapp	811887726
66	Cheesecake Strawberry	Funny Cake	Online	Setiap Hari	08.00 - 21.00	Whatsapp	89525536914
67	Makaroni Panggang	Lita Bakery	Online	Setiap Hari	08.00 - 21.00	Whatsapp	85778893219
68	Churros	Merayu Manis	Online	Setiap Hari	08.00 - 21.00	Whatsapp	81284583250
69	Telur	Nabara Jaya	Online	Setiap Hari	08.00 - 20.00	Whatsapp	812888853942
70	Beras	Silaris Indonesia/Nabara Jaya	Online	Setiap Hari	08.00 - 20.00	Whatsapp	812888853942
71	Bawang Merah	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
72	Bawang Putih	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
73	Bawang Bombay	Lotte Mart	Online/offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
74	Bawang Goreng	Joko Tole	Offline	Setiap Hari	08.00 - 20.00	Joko Tole	-
75	Cabe Rawit Merah	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
76	Cabe Keriting	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
77	Daun Basil	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
78	Daun Bawang	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
79	Daun Jeruk	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
80	Sereh	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
81	Selada 	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
82	Selada Iceberg	Sayurbox	Online	Setiap Hari	06.00 - 23.00	Aplikasi Sayurbox	-
83	Tomat	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
84	Tomat Ceri	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
85	Timun Jepang	Sayurbox	Online	Setiap Hari	06.00 - 23.00	Aplikasi Sayurbox	88212626436
86	Terasi	Lotte	Offline	Setiap Hari	09.00 - 17.00	Lotte	-
87	Kol Ungu	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
88	Jeruk Limo	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
89	Jagung Manis	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
90	Wortel	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
91	Jahe	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
92	Paprika Hijau	Anugrah Rempah	Online	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
93	Paprika Powder	Tokopedia	Online	Setiap Hari	08.00 - 17.00	Tokopedia	https://tokopedia.link/49QRWvjllQb
94	Parsley Powder	Tokopedia	Online	Setiap Hari	08.00 - 17.00	Tokopedia	https://tokopedia.link/yBwTtCsllQb
95	Lada Putih Bubuk	Tokopedia	Online	Setiap Hari	08.00 - 17.00	Tokopedia	https://tokopedia.link/prWJm2kllQb
96	Lada Hitam Bubuk	Tokopedia	Online	Setiap Hari	08.00 - 17.00	Tokopedia	https://tokopedia.link/Tr0ZQYpllQb
97	Nori 	Shopee	Online	Setiap Hari	08.00 - 17.00	Shopee	https://id.shp.ee/JRXDxJe
98	Sasa	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
99	Garam	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
100	Kaldu Jamur Totole	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
101	Knorr Ayam	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
102	Gula Halus	Yoeks	Offline	Senin - Sabtu	09.00 - 16.00	Yoeks	-
103	Kacang Tanah	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
104	Tepung Terigu 	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
105	Tepung Tapioka Rosebrand	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
106	Tepung Serbaguna	Silaris Indonesia	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	89652288287
107	Tepung Maizena	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
108	Tepung Roti	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
109	Mayonais MC Lwis	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
110	Saos Cabai McLwis	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
111	Saos Tomat McLwis	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
112	Saos Bangkok Indofood	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
113	Saos Tiram Lee Kum Kee	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
114	Saos Mentai Choice L	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
115	Saos Keju Choice L	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
116	Saos Bolognese La Fonte	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
117	Saos Nanban Kewpie	PT Indoguna Utama	Online	Senin - Jumat	09.00 - 17.00	Whatsapp	81219920992
118	Caramel Sauce Morin	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
119	Chocolate Sauce Morin	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
120	Kewpie Caesar Dressing	PT Indoguna Utama	Online	Senin - Jumat	09.00 - 17.00	Whatsapp	81219920992
121	Kecap Manis ABC	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
122	Kecap Asin Lee Kum Kee	Sukanda Djaya	Online	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	81919291106
123	Cuka	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
124	Madu Enak Pure Honey	Lotte Mart	Online/Offline	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
125	Minyak Wijen	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
126	Minyak Goreng Kuncimas 18 liter	PT Bogor Jaya Abadi	Online	Senin - Sabtu	09.00 - 15.00	Whatsapp	87770649200
127	Olive Oil Pietro Coricelli	Yoeks	Offline	Senin - Sabtu	09.00 - 16.00	Yoeks	-
128	Mentega Forvita	Yoeks	Offline	Senin - Sabtu	09.00 - 16.00	Yoeks	-
129	Vanila Essence	Yoeks	Offline	Senin - Sabtu	09.00 - 16.00	Yoeks	-
130	Baking Powder	Yoeks	Offline	Senin - Sabtu	09.00 - 16.00	Yoeks	-
131	Biji Wijen	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
132	Keju Melt Prochiz	Yoeks	Offline	Senin - Sabtu	09.00 - 16.00	Yoeks	-
133	Kulit Pangsit 	Ameen Frozen	Online	Setiap Hari	09.00 - 17.00	Ameen Frozen	-
134	Kulit Tortila	Tokopedia	Online	Senin - Sabtu	09.00 - 16.00	Tokopedia	https://tokopedia.link/KuZUFrznlQb
135	Ikan Dori	Sibeku	Online	Senin - Sabtu	09.00 - 16.00	Whatsapp	81903070506
136	Paha Ayam Fillet	Sibeku/Anuggrah Rempah	Online	Senin - Sabtu	09.00 - 16.00	Whatsapp	81903070506
137	Beef Sliced	Sibeku	Online	Senin - Sabtu	09.00 - 16.00	Whatsapp	81903070506
138	Beef Saikoro	Hijrah Food	Online	Setiap Hari	08.00 - 20.00	Shopee	https://id.shp.ee/ReFLqW3
139	Daging Giling	Hijrah Food	Online	Setiap Hari	08.00 - 20.00	Shopee	https://id.shp.ee/5GN7nSL
140	Udang	Sibeku	Online	Senin - Sabtu	09.00 - 16.00	Whatsapp	81903070506
141	French Fries Straight Cut Marquise	Prambanan Kencana	Online	Senin - Sabtu	09.00 - 15.00	Whatsapp	85718402221
142	Potato Wedges	Sibeku	Online	Senin - Sabtu	09.00 - 16.00	Whatsapp	81903070506
143	Risoles	Risol Cap Panda	Online	Setiap Hari	09.00 - 16.00	Whatsapp	81213757059
144	Roti Tawar	Woodenpin Bakers	Online	Setiap Hari	09.00 - 20.00	Whatsapp	62 813-8305-9590
145	Gas 3 Kg	Bravo Delivery	Online	Setiap Hari	08.00 - 16.00	Whatsapp	816866966
146	Gas 12 Kg	Aksimart	Online	Setiap Hari	09.00 - 17.00	Whatsapp	8551156000
147	Gas Torch	Jaya Makmur	Offline	Setiap Hari	09.00 - 17.00	Jaya Makmur	-
148	Aqua Galon	Aksimart	Online	Setiap Hari	09.00 - 17.00	Whatsapp	8551156000
149	Box Makan Takeaway	Plaskita	Offline	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
150	Botolan Series	Tokopedia	Online	Setiap Hari	09.00 - 17.00	Tokopedia	https://tokopedia.link/VvCGNBp5kQb
151	Cup Takeaway Ice	Yaudacup Indonesia	Online	Setiap Hari	09.00 - 17.00	Whatsapp	82261828063
152	Cup Takeaway Hot	Yaudacup Indonesia	Online	Setiap Hari	09.00 - 17.00	Whatsapp	82261828063
153	Handglove	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
154	Paperbag Pastry	Plaskita	Offline	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
155	Paperbag Takeaway UD Djaya	Shopee	Online	Setiap Hari	09.00 - 17.00	Shopee	https://id.shp.ee/meo3FJy
156	Plastik Takeaway Kitchen 	Plaskita	Offline	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
157	Plastik Takeaway Single Cup	Plaskita	Offline	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
158	Plastik Takeaway Double Cup	Plaskita	Offline	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
159	Plastik Prapatan	Plaskita	Offline	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
160	Kertas Kentang	Yaudacup Indonesia	Online	Setiap Hari	09.00 - 17.00	Whatsapp	82261828063
161	Kertas Pastry	Yaudacup Indonesia	Online	Setiap Hari	09.00 - 17.00	Whatsapp	82261828063
162	Ketas Thermall Bar	Yaudacup Indonesia	Online	Setiap Hari	09.00 - 17.00	Whatsapp	82261828063
163	Kertas Thermall Pastry	Yaudacup Indonesia	Online	Setiap Hari	09.00 - 17.00	Whatsapp	82261828063
164	Sendok Takeaway	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
165	Sedotan Merah	Tokopedia	Online	Setiap Hari	09.00 - 17.00	Tokopedia	https://tokopedia.link/0ddngifKkQb
166	Sedotan Hot	Plaskita	Offline	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
167	Tray Cup 	Shopee	Online	Setiap Hari	09.00 - 17.00	Shopee	https://id.shp.ee/gxgmr74
168	Tissue Wajah	Errytha Supplier Bogor	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
169	Tissue Roll Toilet	Errytha Supplier Bogor	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
170	Tissue Roll Kitchen	Errytha Supplier Bogor	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
171	Tissue Napkin	Errytha Supplier Bogor	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
172	TIssue Multifold	Errytha Supplier Bogor	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
173	Trashbag uk 60x90	Suplier Trashbag (Dimas)	Online	Setiap Hari	09.00 - 17.00	Whatsapp	83877428220
174	Trashbag uk 90x120	Suplier Trashbag (Dimas)	Online	Setiap Hari	09.00 - 17.00	Whatsapp	83877428220
175	Trashbag Toilet	Shopee	Online	Setiap Hari	09.00 - 17.00	Shopee	https://id.shp.ee/HWJNZTn
176	Wrapping Bar	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
177	Wrapping Kitchen	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
178	Amplop	Toko Buku AA	Offline	Senin - Sabtu	09.00 - 17.00	Toko Buku AA	-
179	Solatip	Toko Buku AA	Offline	Senin - Sabtu	09.00 - 17.00	Toko Buku AA	-
180	Spoons Busa	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
181	Spoons Kawat	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Setiap Hari	-
182	Sabun Cuci Piring	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Setiap Hari
183	Sabun Cuci Tangan	SheMax	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	62 821-12164325
184	Sabun Lantai	SheMax	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	62 821-12164325
185	Sabun Detergent	SheMax	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	62 821-12164325
186	Clink Pembersih Kaca	SheMax	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	62 821-12164325
187	Karbol	SheMax	Online	Senin - Sabtu	09.00 - 17.00	Whatsapp	62 821-12164325
188	Kamper	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
189	Harpic	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
190	Pengharum Gantung Ruangan	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
191	Pengharum Gantung Toilet	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-
192	Pengharum Ruangan Semprot	Grand	Offline	Senin - Sabtu	09.00 - 17.00	Grand	-













































































































































































































































































































































































































































































































































































































































































































































































































































































RAW_SUPPLIER_SHEET_1;
    }

    private function rawSheetTwo(): string
    {
        return <<<'RAW_SUPPLIER_SHEET_2'
No	Nama Bahan Baku	Tempat Pembelian/Supplier	Online/Offline	Jadwal Operasional	Jam Operasional	Tempat Pemesanan	CP
1	Fresh Milk	Greenfields	Surya Anugrah Sentosa PT	Senin - Sabtu	09.00 - 17.00	Whatsapp	83824567910	Feny
2	Salted Caramel Sauce	Davinci	Toffin	Senin - Sabtu	09.00 - 17.00	Whatsapp	85217169123
3	Powder Matcha Chemisty	Chemisty	Chemisty	Senin - Sabtu	09.00 - 17.00	Whatsapp	85173146655
4	Oat Milk	Oatside	Sukanda Djaya	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	81919291106
5	Cashew Milk	Arumi	Sukanda Djaya	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	81919291106
6	Creamer	Rich	Howki Gondang Perkasa	Senin - Sabtu	09.00 - 17.00	Whatsapp	81280415126
7	Millac Whipping Cream	Millac	Sukanda Djaya	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	81919291106
8	Vanilla Syrup	FO	Toffin	Senin - Sabtu	09.00 - 17.00	Whatsapp	85217169123	Gusto
9	Pistachio Syrup	Dripp	Dandy Monin Laska	Senin - Sabtu	09.00 - 17.00	Whatsapp	81399859010
10	Buah Sunkist		Production	Senin - Sabtu
11	Nutmeg (Pala)		Yogya	Setiap Hari
12	Orange Syrup	Dripp	Dandy Monin Laska	Senin - Sabtu	09.00 - 17.00	Whatsapp	81399859010
13	Brookfarm		Sukanda Djaya	Senin - Sabtu	09.00 - 17.00	Aplikasi Sukanda	81919291106
14	SKM	Carnation	Surya Anugrah Sentosa PT	Setiap Hari	09.00 - 17.00	Whatsapp	81212857886
15	UHT		Sukanda Djaya	Senin - Sabtu	08.00 - 15.00	Aplikasi Sukanda	81919291106
16	Creame Bubuk	Multibev	Kelana	Senin - Sabtu	09.00 - 17.00	Whatsapp	82124414641
17	Powder Coklat	Dilco	Dillco Sales Service	Senin - Sabtu	09.00 - 17.00	Whatsapp	81120040777
18	Yuzu Syrup	Dripp	Dandy Monin Laska	Senin - Sabtu	09.00 - 17.00	Whatsapp	81399859010	Dandy
19	Strawberry Jam	Goldenfill	Koerinci	Setiap Hari		Whatsapp	085377623513	Jeri
20	Strawberry Syrup	Monin	Yenny Sales Monin	Senin - Sabtu	09.00 - 17.00	Whatsapp	81339411458
21	Schweppes	Schweppes	Lotte Mart	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
22	Pastry 	Imah kopi	Imah Kopi Croissanterie	Setiap Hari	06.00 - 18.00	Whatsapp	85222888987
23	Cheesecake 	Flour	Tobing	Setiap Hari	09.00 - 17.00	Whatsapp
24	Bacang	Tukang bacang		Setiap Hari		Whatsapp
25	Romaine lettuce	Yogya/Allfresh		Setiap Hari	09.00 - 17.00
26	Curry Sauce	Kewpie	PT Indoguna Utama	Senin - Jumat	09.00 - 17.00	Whatsapp
27	Smoked Beef	Yona/Bernadi	Grand	Setiap Hari	09.00 - 17.00
28	Telur		Nabara Jaya	Setiap Hari	08.00 - 20.00	Whatsapp	812888853942
29	Butter	Anchor	Grand	Setiap Hari	09.00 - 17.00
30	Mayonaise	Kewpie	PT Indoguna Utama	Senin - Jumat	09.00 - 17.00	Whatsapp
31	Sauce Cabai	Leuwis	Lotte Mart	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
32	Tomat		Anugrah Rempah
33	Lada Hitam		Tokopedia	Setiap Hari	08.00 - 17.00	Tokopedia	https://tokopedia.link/prWJm2kllQb
34	Lada Putih		Tokopedia	Setiap Hari	08.00 - 17.00	Tokopedia	https://tokopedia.link/Tr0ZQYpllQb
35	Garam		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
36	Gula Pasir		Grand	Senin - Sabtu	09.00 - 17.00
37	BBQ Sauce		Grand	Setiap Hari	09.00 - 17.00
38	Bawang Bombay		Lotte Mart	Setiap Hari	07.00 - 20.00	Whatsapp	81295900034/08881938602
39	Bawang Putih		Anugrah Rempah	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
40	Paha Ayam 		Sibeku	Senin - Sabtu	09.00 - 16.00	Whatsapp	81903070506
41	Daging Slice		RnB Frozen	Setiap Hari	07.00 - 22.00	Whatsapp	81281167878
42	Red Cheddar		Grand	Senin - Sabtu	09.00 - 17.00	Grand
43	Jagung Manis		Anugrah Rempah	Setiap Hari	09.00 - 16.00	Whatsapp	88212626436
44	FrenchFries		Prambanan Kencana	Senin - Sabtu	09.00 - 15.00	Whatsapp	85718402221
45	Risol Rogut Ayam		Risol Cap Panda	Setiap Hari	09.00 - 16.00	Whatsapp	81213757059
46	Paprika Powder		Tokopedia	Setiap Hari	08.00 - 17.00	Tokopedia	https://tokopedia.link/49QRWvjllQb
47	Totole (kaldu jamur)		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
48	Roti Sourdough	Butterfields	Butterfields	Setiap Hari	09.00 - 16.00	Whatsapp	85157591700
49	Richotta Cheese	Greenfields	All Fresh			Whatsapp	83824567910	Fenny
50	Strawberry Buah		Yogya	Setiap Hari	07.00 - 20.00
51	Blueberry Frozen		Yogya	Setiap Hari	07.00 - 20.00
52	Kecap Manis		Lotte Mart	Setiap Hari	07.00 - 20.00
53	Sauce Tiram	LeeKumXi	Grand	Setiap Hari	07.00 - 20.00
54	Ubi Cilembu
55	Vanilla Essence	Butterfly	Grand	Setiap Hari	09.00 - 17.00
56	Tepung Maizena		Grand	Setiap Hari	09.00 - 17.00
57	Roti Tawar	Hagu	Hagu	Setiap Hari	09.00 - 20.00
58	Mentega	Blueband	Grand	Setiap Hari	09.00 - 17.00
59	Selai Choco Peanut 	Nuttela	Grand	Setiap Hari	09.00 - 17.00
60	Madu Enak Pure Honey		Lotte Mart	Setiap Hari	07.00 - 20.00
61	Buah Lemon	Roastery	Roastery	Setiap Hari	07.00 - 20.00
62	Gas 3 Kg	Bravo Delivery	Online	08.00 - 16.00	Whatsapp	816866966
63	Gas 12 Kg	Aksimart	Online	09.00 - 17.00	Whatsapp	8551156000
64	Gas Torch	Jaya Makmur	Offline	09.00 - 17.00	Jaya Makmur	-
65	Aqua Galon	Aksimart	Online	09.00 - 17.00	Whatsapp	8551156000
66	Box Makan Takeaway	Plaskita	Offline	09.00 - 17.00	Plaskita	81803060622
67	Handglove		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
68	Paperbag Pastry		Plaskita	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
69	Paperbag Takeaway UD Djaya		Shopee	Setiap Hari	09.00 - 17.00	Shopee	https://id.shp.ee/meo3FJy
70	Plastik Takeaway Kitchen Polos		Plaskita	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
71	Plastik Takeaway Single Cup		Plaskita	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
72	Plastik Takeaway Double Cup		Plaskita	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
73	Plastik Prapatan		Plaskita	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
74	Kertas Kentang Budiman		Shopee	Setiap Hari	08.00 - 16.00	Shopee	https://id.shp.ee/BdNea1G
75	Kertas Pastry Budiman		Shopee	Setiap Hari	08.00 - 16.00	Shopee	https://id.shp.ee/jsaogkE
76	Ketas Thermall Bar		Roll Paper Mandiri	Setiap Hari	08.00 - 16.00	Whatsapp	81211561483
77	Kertas Thermall Pastry		Roll Paper Mandiri	Setiap Hari	08.00 - 16.00	Whatsapp	81211561483
78	Sendok Takeaway		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
79	Sedotan Hot		Plaskita	Setiap Hari	09.00 - 17.00	Plaskita	81803060622
80	Tray Cup 		Shopee	Setiap Hari	09.00 - 17.00	Shopee	https://id.shp.ee/gxgmr74
81	Tissue Wajah		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
82	Tissue Roll Toilet		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
83	Tissue Roll Kitchen		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
84	Tissue Napkin		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
85	TIssue Multifold		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
86	Trashbag uk 60x90		Suplier Trashbag (Dimas)	Setiap Hari	09.00 - 17.00	Whatsapp	83877428220
87	Trashbag uk 90x120		Suplier Trashbag (Dimas)	Setiap Hari	09.00 - 17.00	Whatsapp	83877428220
88	Trashbag Toilet		Shopee	Setiap Hari	09.00 - 17.00	Shopee	https://id.shp.ee/HWJNZTn
89	Wrapping Bar		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
90	Wrapping Kitchen		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
91	Solatip		Toko Buku AA	Senin - Sabtu	09.00 - 17.00	Toko Buku AA	-
92	Spoons Busa		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
93	Spoons Kawat		Grand	Senin - Sabtu	09.00 - 17.00	Setiap Hari	-
94	Sabun Cuci Piring		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
95	Sabun Cuci Tangan		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
96	Sabun Lantai		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
97	Sabun Detergent		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
98	Clink Pembersih Kaca		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
99	Karbol		Errytha Supplier Bogor	Senin - Sabtu	09.00 - 17.00	Whatsapp	85697773152
100	Kamper		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
101	Harpic		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
102	Pengharum Gantung Ruangan		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
103	Pengharum Gantung Toilet		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
104	Pengharum Ruangan Semprot		Grand	Senin - Sabtu	09.00 - 17.00	Grand	-
105	Bacang		Empal Gentong H.Arif	Setiap Hari	09.00 - 17.00	Whatsapp	82125922074	Acun
106	Ubi Cilembu	Yogya	Yogya	Setiap Hari
































































































































































































































































































































































































































































































































































































































































































































































































RAW_SUPPLIER_SHEET_2;
    }
}
