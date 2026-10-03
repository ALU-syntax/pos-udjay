<?php

namespace Tests\Feature\OrderTable;

use App\Models\OrderTable\AuditLog;
use App\Models\OrderTable\OutletSetting;

class OutletSettingTest extends OrderTableTestCase
{
    public function test_setting_and_outlet_geolocation_are_created_atomically_with_an_audit_log(): void
    {
        $outlet = $this->outlet('Setting Create');
        $user = $this->userFor($outlet, ['create order-table/outlet-settings']);

        $response = $this->actingAs($user)->postJson(route('order-table/outlet-settings/store'), [
            'outlet_id' => $outlet->id,
            'order_enabled' => true,
            'stock_mode' => 'strict',
            'open_time' => '08:00',
            'close_time' => '22:00',
            'forced_close' => false,
            'service_fee_pct' => 7.5,
            'auto_preparing_delay_seconds' => 45,
            'session_close_time' => '23:30',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'geofence_radius_m' => 250,
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $setting = OutletSetting::findOrFail($response->json('data.id'));
        $outlet->refresh();

        $this->assertSame('strict', $setting->stock_mode);
        $this->assertSame('-6.2000000', $outlet->latitude);
        $this->assertSame('106.8166667', $outlet->longitude);
        $this->assertSame(250, $outlet->geofence_radius_m);
        $this->assertDatabaseHas('ot_audit_logs', [
            'user_id' => $user->id,
            'action' => 'outlet-setting.created',
            'subject_type' => OutletSetting::class,
            'subject_id' => $setting->id,
        ]);

        $audit = AuditLog::where('action', 'outlet-setting.created')->where('subject_id', $setting->id)->firstOrFail();
        $this->assertEquals(-6.2, $audit->after['latitude']);
        $this->assertEquals(106.8166667, $audit->after['longitude']);
    }

    public function test_setting_update_and_forced_close_toggle_are_audited(): void
    {
        $outlet = $this->outlet('Setting Update');
        $user = $this->userFor($outlet, ['update order-table/outlet-settings']);
        $setting = $this->setting($outlet);

        $this->actingAs($user)->putJson(route('order-table/outlet-settings/update', $setting->id), [
            'outlet_id' => $outlet->id,
            'order_enabled' => false,
            'stock_mode' => 'off',
            'open_time' => null,
            'close_time' => null,
            'service_fee_pct' => 3,
            'auto_preparing_delay_seconds' => 10,
            'session_close_time' => '22:30',
            'latitude' => -7.25,
            'longitude' => 112.75,
            'geofence_radius_m' => 300,
        ])->assertOk();

        $this->assertDatabaseHas('ot_outlet_settings', ['id' => $setting->id, 'stock_mode' => 'off']);
        $this->assertDatabaseHas('ot_audit_logs', [
            'action' => 'outlet-setting.updated',
            'subject_id' => $setting->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('order-table/outlet-settings/toggle', $setting->id))
            ->assertOk();

        $this->assertTrue($setting->fresh()->forced_close);
        $this->assertDatabaseHas('ot_audit_logs', [
            'action' => 'outlet-setting.forced-close',
            'subject_id' => $setting->id,
        ]);
    }

    public function test_hours_must_be_paired_and_service_fee_cannot_exceed_one_hundred_percent(): void
    {
        $outlet = $this->outlet('Setting Validation');
        $user = $this->userFor($outlet, ['update order-table/outlet-settings']);
        $setting = $this->setting($outlet);

        $this->actingAs($user)->putJson(route('order-table/outlet-settings/update', $setting->id), [
            'outlet_id' => $outlet->id,
            'order_enabled' => true,
            'stock_mode' => 'status_only',
            'open_time' => '08:00',
            'close_time' => null,
            'forced_close' => true,
            'service_fee_pct' => 100.01,
            'auto_preparing_delay_seconds' => 30,
            'session_close_time' => '23:59',
            'latitude' => null,
            'longitude' => null,
            'geofence_radius_m' => 150,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'close_time',
            'forced_close',
            'service_fee_pct',
        ]);
    }
}
