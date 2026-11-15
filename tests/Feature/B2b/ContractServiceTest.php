<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Contract;
use App\Services\B2b\ContractService;
use Illuminate\Validation\ValidationException;

class ContractServiceTest extends B2bTestCase
{
    private function payload($customer, array $overrides = []): array
    {
        return array_merge([
            'b2b_customer_id' => $customer->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonths(6)->toDateString(),
            'notes' => null,
            'rules' => [['scope' => 'all', 'outlet_id' => null, 'category_id' => null, 'product_id' => null,
                'variant_id' => null, 'discount_type' => 'percent', 'discount_value' => 10, 'min_qty' => 1]],
        ], $overrides);
    }

    public function test_create_numbers_contract_as_draft_and_can_activate_immediately(): void
    {
        $this->actingAs($this->userFor());
        $customer = $this->customer();

        $draft = app(ContractService::class)->create($this->payload($customer, [
            'end_date' => today()->addMonth()->toDateString(),
        ]));
        $active = app(ContractService::class)->create($this->payload($customer, [
            'start_date' => today()->addMonths(2)->toDateString(),
            'end_date' => today()->addMonths(3)->toDateString(),
        ]), activate: true);

        $this->assertSame(Contract::STATUS_DRAFT, $draft->status);
        $this->assertMatchesRegularExpression('#^KTR/B2B/\d{4}/\d{4}$#', $draft->contract_number);
        $this->assertCount(1, $draft->rules);
        $this->assertSame(Contract::STATUS_ACTIVE, $active->fresh()->status);
        $this->assertNotNull($active->fresh()->activated_at);
    }

    public function test_overlapping_period_with_draft_or_active_contract_is_rejected(): void
    {
        $this->actingAs($this->userFor());
        $customer = $this->customer();
        $this->contract($customer, [], ['start_date' => today(), 'end_date' => today()->addMonth()]);

        $this->expectException(ValidationException::class);
        app(ContractService::class)->create($this->payload($customer, [
            'start_date' => today()->addDays(15)->toDateString(),
            'end_date' => today()->addMonths(2)->toDateString(),
        ]));
    }

    public function test_terminated_or_expired_contract_does_not_block_new_period(): void
    {
        $this->actingAs($this->userFor());
        $customer = $this->customer();
        $this->contract($customer, [], ['status' => Contract::STATUS_TERMINATED]);

        $contract = app(ContractService::class)->create($this->payload($customer));

        $this->assertSame(Contract::STATUS_DRAFT, $contract->status);
    }

    public function test_other_customers_contracts_do_not_conflict(): void
    {
        $this->actingAs($this->userFor());
        $this->contract($this->customer());

        $contract = app(ContractService::class)->create($this->payload($this->customer()));

        $this->assertNotNull($contract->id);
    }

    public function test_active_contract_cannot_be_edited_or_deleted(): void
    {
        $this->actingAs($this->userFor());
        $customer = $this->customer();
        $contract = $this->contract($customer);

        foreach ([
            fn () => app(ContractService::class)->update($contract, $this->payload($customer)),
            fn () => app(ContractService::class)->delete($contract),
        ] as $action) {
            try {
                $action();
                $this->fail('Kontrak aktif seharusnya tidak bisa diubah/dihapus.');
            } catch (ValidationException) {
                $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
            }
        }
    }

    public function test_draft_update_replaces_rules(): void
    {
        $this->actingAs($this->userFor());
        $customer = $this->customer();
        $contract = $this->contract($customer, [['discount_value' => 5], ['discount_value' => 6, 'min_qty' => 10]], ['status' => Contract::STATUS_DRAFT]);

        app(ContractService::class)->update($contract, $this->payload($customer));

        $this->assertSame([10.0], $contract->rules()->pluck('discount_value')->map(fn ($v) => (float) $v)->all());
    }

    public function test_terminate_requires_active_and_records_reason(): void
    {
        $user = $this->userFor();
        $this->actingAs($user);
        $contract = $this->contract($this->customer());

        app(ContractService::class)->terminate($contract, 'Customer tutup');

        $contract->refresh();
        $this->assertSame(Contract::STATUS_TERMINATED, $contract->status);
        $this->assertSame('Customer tutup', $contract->termination_reason);
        $this->assertSame($user->id, $contract->terminated_by);
    }

    public function test_cannot_activate_contract_that_already_ended(): void
    {
        $this->actingAs($this->userFor());
        $contract = $this->contract($this->customer(), [], [
            'status' => Contract::STATUS_DRAFT, 'start_date' => today()->subMonths(2), 'end_date' => today()->subDay(),
        ]);

        $this->expectException(ValidationException::class);
        app(ContractService::class)->activate($contract);
    }

    public function test_expire_command_marks_ended_active_contracts(): void
    {
        $customer = $this->customer();
        $ended = $this->contract($customer, [], ['start_date' => today()->subMonths(2), 'end_date' => today()->subDay()]);
        $running = $this->contract($customer, [], ['start_date' => today(), 'end_date' => today()->addMonth()]);

        $this->artisan('b2b:sync-contract-status')->assertSuccessful();

        $this->assertSame(Contract::STATUS_EXPIRED, $ended->fresh()->status);
        $this->assertSame(Contract::STATUS_ACTIVE, $running->fresh()->status);
    }
}
