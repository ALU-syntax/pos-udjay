<?php

namespace Tests\Feature\OrderTable;

use App\Models\Config;
use App\Models\OrderTable\DiningTable;
use App\Services\OrderTable\DiningTableQrService;
use ZipArchive;

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

    public function test_download_all_zips_active_qr_codes_grouped_by_outlet_folder(): void
    {
        $outletA = $this->outlet('Zip A');
        $outletB = $this->outlet('Zip B');
        $other = $this->outlet('Zip Other');
        $user = $this->userFor($outletA, ['download order-table/dining-tables']);
        $user->forceFill(['outlet_id' => [$outletA->id, $outletB->id]])->save();

        $this->diningTable($outletA, ['code' => 'A01']);
        $this->diningTable($outletB, ['code' => 'B01']);
        $this->diningTable($outletB, ['code' => 'B02', 'qr_active' => false]);
        $this->diningTable($other, ['code' => 'X01']);

        $response = $this->actingAs($user)->get(route('order-table/dining-tables/qr/download-all'));

        $response->assertOk()->assertDownload();
        $entries = $this->zipEntries($response->baseResponse->getFile()->getPathname());
        $this->assertEqualsCanonicalizing([
            'Order-Table-Outlet-Zip-A/table-A01.svg',
            'Order-Table-Outlet-Zip-B/table-B01.svg',
        ], $entries);
    }

    public function test_download_all_respects_the_outlet_filter_and_warns_when_empty(): void
    {
        $outlet = $this->outlet('Zip Filter');
        $user = $this->userFor($outlet, ['download order-table/dining-tables']);
        $this->diningTable($outlet, ['code' => 'F01']);

        $response = $this->actingAs($user)->get(route('order-table/dining-tables/qr/download-all', ['outlet_id' => $outlet->id]));
        $response->assertOk()->assertDownload();
        $this->assertSame(['Order-Table-Outlet-Zip-Filter/table-F01.svg'], $this->zipEntries($response->baseResponse->getFile()->getPathname()));

        $empty = $this->outlet('Zip Empty');
        $emptyUser = $this->userFor($empty, ['download order-table/dining-tables']);
        $this->diningTable($empty, ['qr_active' => false]);

        $this->actingAs($emptyUser)->getJson(route('order-table/dining-tables/qr/download-all'))
            ->assertOk()
            ->assertJson(['status' => 'warning']);
    }

    public function test_download_all_requires_download_permission(): void
    {
        $outlet = $this->outlet('Zip Forbidden');
        $user = $this->userFor($outlet, ['read order-table/dining-tables']);

        $this->actingAs($user)->get(route('order-table/dining-tables/qr/download-all'))->assertForbidden();
    }

    public function test_qr_download_is_svg_and_contains_the_public_table_url(): void
    {
        config(['app.url' => 'https://orders.example.test']);
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

    public function test_qr_url_prefers_the_configs_table_over_the_backoffice_fallback(): void
    {
        config(['app.url' => 'https://from-app.example.test']);
        Config::setValue(DiningTableQrService::PUBLIC_URL_CONFIG_KEY, 'https://from-db.example.test', null, 'string');

        $outlet = $this->outlet('QR Precedence');
        $table = $this->diningTable($outlet);

        $this->assertSame(
            'https://from-db.example.test/o/'.$outlet->id.'/t/'.$table->qr_token,
            app(DiningTableQrService::class)->url($table),
        );
    }

    public function test_qr_url_falls_back_to_the_backoffice_domain_when_config_is_missing(): void
    {
        config(['app.url' => 'https://from-app.example.test']);
        Config::query()->where('name', DiningTableQrService::PUBLIC_URL_CONFIG_KEY)->delete();

        $outlet = $this->outlet('QR Fallback');
        $table = $this->diningTable($outlet);

        $this->assertSame(
            'https://from-app.example.test/o/'.$outlet->id.'/t/'.$table->qr_token,
            app(DiningTableQrService::class)->url($table),
        );
    }

    private function zipEntries(string $path): array
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        $zip->close();

        return $entries;
    }
}
