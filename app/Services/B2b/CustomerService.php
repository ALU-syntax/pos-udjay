<?php

namespace App\Services\B2b;

use App\Models\B2b\Customer;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    private const CUSTOMER_FIELDS = [
        'type', 'name', 'npwp', 'phone', 'email', 'is_active', 'notes',
        'reminder_enabled', 'reminder_channels', 'reminder_days', 'reminder_overdue_daily',
    ];

    private const CONTACT_FIELDS = ['name', 'phone', 'email', 'position', 'is_primary', 'receive_reminder'];

    private const ADDRESS_FIELDS = ['label', 'recipient_name', 'phone', 'address', 'city', 'province', 'postal_code', 'is_default_billing', 'is_default_shipping'];

    public function __construct(
        private readonly SequenceService $sequences,
        private readonly ActivityLogger $logger,
    ) {}

    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $customer = new Customer(Arr::only($data, self::CUSTOMER_FIELDS));
            $customer->code = $this->sequences->customerCode();
            $customer->created_by = auth()->id();
            $customer->save();

            $this->syncChildren($customer, $data);
            $this->logger->log('customer.created', $customer);

            return $customer->fresh(['contacts', 'addresses']);
        });
    }

    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $before = $customer->only(self::CUSTOMER_FIELDS);
            $customer->fill(Arr::only($data, self::CUSTOMER_FIELDS));
            $customer->modified_by = auth()->id();
            $customer->save();

            $this->syncChildren($customer, $data);
            $this->logger->log('customer.updated', $customer, [
                'before' => $before,
                'after' => $customer->only(self::CUSTOMER_FIELDS),
            ]);

            return $customer->fresh(['contacts', 'addresses']);
        });
    }

    public function toggle(Customer $customer): Customer
    {
        $customer->forceFill(['is_active' => ! $customer->is_active, 'modified_by' => auth()->id()])->save();
        $this->logger->log($customer->is_active ? 'customer.activated' : 'customer.deactivated', $customer);

        return $customer;
    }

    /** Customer yang sudah punya pesanan tidak dihapus (riwayat & piutang); nonaktifkan saja. */
    public function delete(Customer $customer): void
    {
        if ($customer->orders()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'customer' => 'Customer sudah memiliki pesanan, jadi tidak bisa dihapus. Nonaktifkan customer ini sebagai gantinya.',
            ]);
        }

        DB::transaction(function () use ($customer) {
            $customer->contracts()->whereIn('status', ['draft', 'active'])->update(['status' => 'terminated', 'terminated_at' => now(), 'termination_reason' => 'Customer dihapus']);
            $customer->forceFill(['modified_by' => auth()->id()])->save();
            $customer->delete();
            $this->logger->log('customer.deleted', $customer);
        });
    }

    private function syncChildren(Customer $customer, array $data): void
    {
        $contacts = $this->onlyOneFlag(
            array_map(fn ($row) => $this->withBooleans($row, ['is_primary', 'receive_reminder']), $data['contacts'] ?? []),
            'is_primary',
        );
        $addresses = $this->onlyOneFlag(
            $this->onlyOneFlag(
                array_map(fn ($row) => $this->withBooleans($row, ['is_default_billing', 'is_default_shipping']), $data['addresses'] ?? []),
                'is_default_billing',
            ),
            'is_default_shipping',
        );

        $this->syncRelation($customer->contacts(), $contacts, self::CONTACT_FIELDS);
        $this->syncRelation($customer->addresses(), $addresses, self::ADDRESS_FIELDS);
    }

    /** Update baris yang punya id milik customer ini, buat baris baru, dan soft delete yang dihapus dari form. */
    private function syncRelation(HasMany $relation, array $rows, array $fields): void
    {
        $existing = $relation->getQuery()->clone()->get()->keyBy('id');
        $keptIds = [];

        foreach ($rows as $row) {
            $attributes = Arr::only($row, $fields);
            $model = filled($row['id'] ?? null) ? $existing->get((int) $row['id']) : null;

            if ($model) {
                $model->fill($attributes)->save();
                $keptIds[] = $model->id;
            } else {
                $keptIds[] = $relation->create($attributes)->id;
            }
        }

        $existing->except($keptIds)->each->delete();
    }

    private function withBooleans(array $row, array $keys): array
    {
        foreach ($keys as $key) {
            $row[$key] = filter_var($row[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $row;
    }

    /** Paling banyak satu baris yang ditandai; kalau tidak ada yang ditandai, baris pertama dipakai. */
    private function onlyOneFlag(array $rows, string $flag): array
    {
        $found = false;
        foreach ($rows as $index => $row) {
            $rows[$index][$flag] = $row[$flag] && ! $found;
            $found = $found || $row[$flag];
        }

        if (! $found && $rows !== []) {
            $rows[array_key_first($rows)][$flag] = true;
        }

        return $rows;
    }
}
