<?php

namespace App\Services\B2b;

use App\Models\B2b\Customer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    private const CUSTOMER_FIELDS = [
        'type', 'name', 'npwp', 'phone', 'email', 'contact_name',
        'address', 'city', 'province', 'postal_code', 'is_active', 'notes',
        'reminder_enabled', 'reminder_channels', 'reminder_days', 'reminder_overdue_daily',
    ];

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

            $this->logger->log('customer.created', $customer);

            return $customer->fresh();
        });
    }

    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $before = $customer->only(self::CUSTOMER_FIELDS);
            $customer->fill(Arr::only($data, self::CUSTOMER_FIELDS));
            $customer->modified_by = auth()->id();
            $customer->save();

            $this->logger->log('customer.updated', $customer, [
                'before' => $before,
                'after' => $customer->only(self::CUSTOMER_FIELDS),
            ]);

            return $customer->fresh();
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
}
