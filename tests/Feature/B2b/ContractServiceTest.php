<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Contract;
use App\Models\B2b\ContractItem;
use App\Services\B2b\ContractService;
use Illuminate\Validation\ValidationException;

class ContractServiceTest extends B2bTestCase
{
    private function payload($customer, array $items, array $overrides = []): array
    {
        return array_merge([
            'b2b_customer_id' => $customer->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonths(6)->toDateString(),
            'notes' => null,
            'items' => array_map(fn (array $item) => array_merge(['id' => null, 'min_qty' => 1], $item), $items),
        ], $overrides);
    }

    private function assertRejected(callable $callback, string $field): void
    {
        try {
            $callback();
            $this->fail("Seharusnya ditolak ($field).");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }

    public function test_create_numbers_contract_as_active_with_item_prices(): void
    {
        $user = $this->userFor();
        $this->actingAs($user);
        $variant = $this->variant($this->outlet(), 40000);

        $contract = app(ContractService::class)->create($this->payload($this->customer(), [
            ['variant_id' => $variant->id, 'price' => 38000],
            ['variant_id' => $variant->id, 'min_qty' => 10, 'price' => 36000],
        ]));

        $this->assertMatchesRegularExpression('#^KTR/B2B/\d{4}/\d{4}$#', $contract->contract_number);
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->status);
        $this->assertSame('Berjalan', $contract->statusLabel());
        $this->assertSame($user->id, $contract->created_by);
        $this->assertSame([[1, 38000], [10, 36000]], $contract->items->map(fn ($item) => [$item->min_qty, $item->price])->all());
        $this->assertDatabaseHas('b2b_activity_logs', ['action' => 'contract.created', 'subject_id' => $contract->id]);
    }

    public function test_period_cannot_overlap_with_another_contract_of_the_same_customer(): void
    {
        $this->actingAs($this->userFor());
        $variant = $this->variant($this->outlet());
        $customer = $this->customer();
        $item = [['variant_id' => $variant->id, 'price' => 9000]];
        $service = app(ContractService::class);
        $scheduled = $this->contract($customer, $item, ['start_date' => today()->addMonths(2), 'end_date' => today()->addMonths(4)]);

        // Bentrok dengan kontrak terjadwal.
        $this->assertRejected(fn () => $service->create($this->payload($customer, $item, [
            'start_date' => today()->addMonths(3)->toDateString(), 'end_date' => today()->addMonths(5)->toDateString(),
        ])), 'start_date');

        // Periode yang tidak bersinggungan boleh.
        $running = $service->create($this->payload($customer, $item, ['end_date' => today()->addMonth()->toDateString()]));

        // Edit tanggal tidak dibandingkan dengan dirinya sendiri, tapi tetap dicek ke kontrak lain.
        $service->update($running, $this->payload($customer, $item, ['end_date' => today()->addMonths(2)->subDay()->toDateString()]));
        $this->assertRejected(fn () => $service->update($running, $this->payload($customer, $item, [
            'end_date' => today()->addMonths(2)->addDay()->toDateString(),
        ])), 'start_date');

        // Customer lain tidak bentrok.
        $service->create($this->payload($this->customer(), $item, ['end_date' => today()->addMonths(5)->toDateString()]));

        // Kontrak dihentikan atau dihapus tidak lagi memakan periode.
        $service->terminate($running, 'Ganti harga');
        $scheduled->delete();
        $service->create($this->payload($customer, $item, ['end_date' => today()->addMonths(5)->toDateString()]));
    }

    public function test_running_contract_can_change_dates_and_items_in_place(): void
    {
        $user = $this->userFor();
        $this->actingAs($user);
        $outlet = $this->outlet();
        $a = $this->variant($outlet, 40000, 100, null, 'A');
        $b = $this->variant($outlet, 30000, 100, null, 'B');
        $c = $this->variant($outlet, 35000, 100, null, 'C');
        $customer = $this->customer();
        $contract = $this->contract($customer, [
            ['variant_id' => $a->id, 'price' => 38000],
            ['variant_id' => $b->id, 'price' => 27000],
        ]);
        [$itemA, $itemB] = $contract->items->sortBy('id')->values()->all();

        app(ContractService::class)->update($contract, $this->payload($customer, [
            ['id' => $itemA->id, 'variant_id' => $a->id, 'price' => 37000],
            ['variant_id' => $c->id, 'price' => 30000],
        ], ['start_date' => today()->subDays(10)->toDateString(), 'end_date' => today()->addMonths(3)->toDateString()]));

        $contract->refresh()->load('items');
        $this->assertSame(today()->addMonths(3)->toDateString(), $contract->end_date->toDateString());
        $this->assertSame($user->id, $contract->modified_by);
        $this->assertSame(37000, $itemA->fresh()->price);
        $this->assertSoftDeleted('b2b_contract_items', ['id' => $itemB->id]);
        $this->assertSame([$a->id, $c->id], $contract->items->pluck('variant_id')->sort()->values()->all());
        $this->assertDatabaseHas('b2b_activity_logs', ['action' => 'contract.updated', 'subject_id' => $contract->id]);
    }

    public function test_terminated_contract_cannot_be_edited(): void
    {
        $this->actingAs($this->userFor());
        $variant = $this->variant($this->outlet());
        $customer = $this->customer();
        $contract = $this->contract($customer, [['variant_id' => $variant->id, 'price' => 9000]], ['status' => Contract::STATUS_TERMINATED]);

        $this->assertRejected(fn () => app(ContractService::class)->update($contract, $this->payload($customer, [
            ['variant_id' => $variant->id, 'price' => 8000],
        ])), 'status');
    }

    public function test_terminate_records_reason_and_rejects_terminated_or_ended_contracts(): void
    {
        $user = $this->userFor();
        $this->actingAs($user);
        $customer = $this->customer();
        $service = app(ContractService::class);
        $running = $this->contract($customer);

        $service->terminate($running, 'Kerja sama selesai');
        $running->refresh();
        $this->assertSame('Dihentikan', $running->statusLabel());
        $this->assertSame('Kerja sama selesai', $running->termination_reason);
        $this->assertSame($user->id, $running->terminated_by);

        $this->assertRejected(fn () => $service->terminate($running, 'Lagi'), 'status');
        $ended = $this->contract($customer, [], ['start_date' => today()->subMonths(3), 'end_date' => today()->subMonths(2)]);
        $this->assertSame('Berakhir', $ended->statusLabel());
        $this->assertRejected(fn () => $service->terminate($ended, 'Telat'), 'status');
    }

    public function test_running_contract_cannot_be_deleted_but_others_are_soft_deleted(): void
    {
        $this->actingAs($this->userFor());
        $customer = $this->customer();
        $service = app(ContractService::class);
        $running = $this->contract($customer);
        $scheduled = $this->contract($customer, [], ['start_date' => today()->addMonths(2), 'end_date' => today()->addMonths(3)]);
        $ended = $this->contract($customer, [], ['start_date' => today()->subMonths(3), 'end_date' => today()->subMonths(2)]);

        $this->assertSame('Terjadwal', $scheduled->statusLabel());
        $this->assertRejected(fn () => $service->delete($running), 'status');
        $this->assertNotSoftDeleted('b2b_contracts', ['id' => $running->id]);

        $service->delete($scheduled);
        $service->delete($ended);
        $service->terminate($running, 'Salah input');
        $service->delete($running->fresh());

        foreach ([$scheduled, $ended, $running] as $contract) {
            $this->assertSoftDeleted('b2b_contracts', ['id' => $contract->id]);
        }
        $this->assertDatabaseHas('b2b_activity_logs', ['action' => 'contract.deleted', 'subject_id' => $running->id]);
    }

    public function test_removed_item_stays_readable_for_old_orders(): void
    {
        $this->actingAs($this->userFor());
        $variant = $this->variant($this->outlet());
        $contract = $this->contract($this->customer(), [['variant_id' => $variant->id, 'price' => 9000]]);
        $item = $contract->items->first();

        $item->delete();

        $this->assertSame(9000, ContractItem::withTrashed()->find($item->id)->price);
        $this->assertCount(0, $contract->fresh()->items);
    }
}
