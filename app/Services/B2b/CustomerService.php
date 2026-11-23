<?php

namespace App\Services\B2b;

use App\Models\B2b\Contract;
use App\Models\B2b\Customer;
use App\Models\B2b\CustomerAccount;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    private const CUSTOMER_FIELDS = [
        'type', 'name', 'npwp', 'phone', 'email', 'contact_name',
        'address', 'province_code', 'regency_code', 'district_code', 'village_code', 'postal_code', 'is_active', 'notes',
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
            $this->syncAccounts($customer, $data['accounts'] ?? []);

            $this->logger->log('customer.created', $customer, ['accounts' => $this->accountSummaries($customer)]);

            return $customer->fresh();
        });
    }

    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $before = $customer->only(self::CUSTOMER_FIELDS) + ['accounts' => $this->accountSummaries($customer)];
            $customer->fill(Arr::only($data, self::CUSTOMER_FIELDS));
            $customer->modified_by = auth()->id();
            $customer->save();
            if (array_key_exists('accounts', $data)) {
                $this->syncAccounts($customer, $data['accounts']);
            }

            $this->logger->log('customer.updated', $customer, [
                'before' => $before,
                'after' => $customer->only(self::CUSTOMER_FIELDS) + ['accounts' => $this->accountSummaries($customer)],
            ]);

            return $customer->fresh();
        });
    }

    /**
     * Samakan rekening customer dengan isi form. Rekening dikenali dari provider + nomor:
     * yang sama diperbarui (nama, catatan), yang hilang dihapus, yang baru dibuat. Hapus dulu baru buat,
     * supaya mengganti nomor tidak bentrok dengan indeks unik.
     *
     * @param  list<array{provider: string, account_number: string, account_name: string, notes: ?string}>  $accounts
     */
    private function syncAccounts(Customer $customer, array $accounts): void
    {
        $key = fn ($account) => $account['provider'].'|'.$account['account_number'];
        $submitted = collect($accounts)->keyBy($key);
        $existing = $customer->accounts()->get()->keyBy(fn (CustomerAccount $account) => $key($account));

        $existing->diffKeys($submitted)->each(fn (CustomerAccount $account) => $account->delete());
        foreach ($submitted as $id => $account) {
            $values = ['account_name' => $account['account_name'], 'notes' => $account['notes'] ?? null];
            if ($existing->has($id)) {
                $existing[$id]->update($values);

                continue;
            }
            $customer->accounts()->create($values + [
                'type' => CustomerAccount::typeOf($account['provider']),
                'provider' => $account['provider'],
                'account_number' => $account['account_number'],
            ]);
        }
    }

    /** @return list<string> */
    private function accountSummaries(Customer $customer): array
    {
        return $customer->accounts()->get()->map(fn (CustomerAccount $account) => $account->summary())->all();
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
            $customer->contracts()->where('status', Contract::STATUS_ACTIVE)->update([
                'status' => Contract::STATUS_TERMINATED, 'terminated_at' => now(), 'terminated_by' => auth()->id(), 'termination_reason' => 'Customer dihapus',
            ]);
            $customer->forceFill(['modified_by' => auth()->id()])->save();
            // Rekening dilepas supaya bisa didaftarkan ke customer lain (satu rekening = satu customer).
            $customer->accounts()->delete();
            $customer->delete();
            $this->logger->log('customer.deleted', $customer);
        });
    }
}
