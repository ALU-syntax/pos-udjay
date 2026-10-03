<?php

namespace Tests\Feature\OrderTable;

use App\Models\OrderTable\AuditLog;
use App\Models\OrderTable\DiningTable;
use App\Services\OrderTable\DiningTableQrService;

class DiningTableTest extends OrderTableTestCase
{
    public function test_store_prohibits_client_qr_token(): void
    {
        $outlet = $this->outlet('Token Prohibited');
        $user = $this->userFor($outlet, ['create order-table/dining-tables']);

        $this->actingAs($user)->postJson(route('order-table/dining-tables/store'), [
            'outlet_id' => $outlet->id,
            'code' => 'T01',
            'name' => 'Table 1',
            'qr_token' => str_repeat('a', 64),
        ])->assertUnprocessable()->assertJsonValidationErrors('qr_token');

        $this->assertDatabaseMissing('ot_dining_tables', ['outlet_id' => $outlet->id]);
    }

    public function test_store_normalizes_code_and_generates_a_64_character_hex_token(): void
    {
        $outlet = $this->outlet('Dining Create');
        $user = $this->userFor($outlet, ['create order-table/dining-tables']);

        $response = $this->actingAs($user)->postJson(route('order-table/dining-tables/store'), [
            'outlet_id' => $outlet->id,
            'code' => '  a-01  ',
            'name' => '  Terrace  ',
            'capacity' => 6,
            'qr_active' => true,
        ])->assertOk();

        $table = DiningTable::findOrFail($response->json('data.id'));
        $this->assertSame('A-01', $table->code);
        $this->assertSame('Terrace', $table->name);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{64}\z/', $table->qr_token);
        $this->assertDatabaseHas('ot_audit_logs', [
            'action' => 'dining-table.created',
            'subject_id' => $table->id,
        ]);
    }

    public function test_duplicate_code_is_rejected_within_the_same_outlet(): void
    {
        $outlet = $this->outlet('Duplicate Table');
        $user = $this->userFor($outlet, ['create order-table/dining-tables']);
        $this->diningTable($outlet, ['code' => 'A01']);

        $this->actingAs($user)->postJson(route('order-table/dining-tables/store'), [
            'outlet_id' => $outlet->id,
            'code' => ' a01 ',
            'name' => 'Duplicate',
        ])->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_qr_status_cannot_be_changed_through_generic_update(): void
    {
        $outlet = $this->outlet('QR Update Guard');
        $user = $this->userFor($outlet, ['update order-table/dining-tables']);
        $table = $this->diningTable($outlet);

        $this->actingAs($user)->putJson(route('order-table/dining-tables/update', $table->id), [
            'outlet_id' => $outlet->id,
            'code' => $table->code,
            'name' => $table->name,
            'capacity' => $table->capacity,
            'qr_active' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('qr_active');

        $this->assertTrue($table->fresh()->qr_active);
    }

    public function test_rotate_changes_token_and_redacts_both_audit_snapshots(): void
    {
        $outlet = $this->outlet('Rotate');
        $user = $this->userFor($outlet, ['rotate order-table/dining-tables']);
        $table = $this->diningTable($outlet);
        $oldToken = $table->qr_token;

        $this->actingAs($user)
            ->postJson(route('order-table/dining-tables/rotate-token', $table->id))
            ->assertOk();

        $table->refresh();
        $this->assertNotSame($oldToken, $table->qr_token);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{64}\z/', $table->qr_token);

        $audit = AuditLog::where('action', 'dining-table.qr-rotated')->where('subject_id', $table->id)->firstOrFail();
        $this->assertArrayNotHasKey('qr_token', $audit->before);
        $this->assertArrayNotHasKey('qr_token', $audit->after);
        $this->assertStringNotContainsString($oldToken, json_encode($audit->toArray(), JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($table->qr_token, json_encode($audit->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_destroy_soft_deletes_the_table_and_writes_an_audit_log(): void
    {
        $outlet = $this->outlet('Delete');
        $user = $this->userFor($outlet, ['delete order-table/dining-tables']);
        $table = $this->diningTable($outlet);

        $this->actingAs($user)
            ->deleteJson(route('order-table/dining-tables/destroy', $table->id))
            ->assertOk();

        $this->assertSoftDeleted('ot_dining_tables', ['id' => $table->id]);
        $this->assertDatabaseHas('ot_audit_logs', [
            'action' => 'dining-table.deleted',
            'subject_id' => $table->id,
        ]);
    }

    public function test_qr_download_is_svg_and_contains_the_public_table_url(): void
    {
        config(['order-table.public_url' => 'https://orders.example.test']);
        $outlet = $this->outlet('QR Download');
        $user = $this->userFor($outlet, ['download order-table/dining-tables']);
        $table = $this->diningTable($outlet, ['code' => 'QR 01']);

        $response = $this->actingAs($user)->get(route('order-table/dining-tables/qr/download', $table->id));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertHeader('Content-Disposition', 'attachment; filename="table-QR-01.svg"');
        $this->assertStringContainsString('<svg', $response->getContent());
        $this->assertStringContainsString(
            '/o/'.$outlet->id.'/t/'.$table->qr_token,
            app(DiningTableQrService::class)->url($table),
        );
    }
}
