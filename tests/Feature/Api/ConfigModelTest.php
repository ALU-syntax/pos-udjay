<?php

namespace Tests\Feature\Api;

use App\Models\Config;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit-ish test untuk model Config.
 *
 * Ditaruh di Feature/Api agar ikut konvensi DatabaseTransactions
 * (lihat catatan di tests/Pest.php: RefreshDatabase tidak boleh dipakai
 * untuk test yang bergantung pada data seed).
 */
class ConfigModelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Config::query()->delete();
    }

    public function test_get_value_returns_casted_integer()
    {
        Config::create(['name' => 'password_min_length', 'type' => 'integer', 'value' => '8']);

        $this->assertSame(8, Config::getValue('password_min_length'));
    }

    public function test_get_value_returns_casted_boolean()
    {
        Config::create(['name' => 'feature_flag', 'type' => 'boolean', 'value' => '1']);
        Config::create(['name' => 'feature_off', 'type' => 'boolean', 'value' => '0']);

        $this->assertTrue(Config::getValue('feature_flag'));
        $this->assertFalse(Config::getValue('feature_off'));
    }

    public function test_get_value_returns_casted_float()
    {
        Config::create(['name' => 'tax_rate', 'type' => 'float', 'value' => '11.5']);

        $this->assertSame(11.5, Config::getValue('tax_rate'));
    }

    public function test_get_value_returns_cast_json()
    {
        Config::create(['name' => 'rule_json', 'type' => 'json', 'value' => json_encode(['max' => 5])]);

        $this->assertSame(['max' => 5], Config::getValue('rule_json'));
    }

    public function test_get_value_returns_string_by_default()
    {
        Config::create(['name' => 'receipt_note', 'value' => '10']);

        $this->assertSame('10', Config::getValue('receipt_note'));
    }

    public function test_get_value_returns_default_when_not_found()
    {
        $this->assertSame(10, Config::getValue('tidak_ada_config_ini', 10));
    }

    public function test_get_value_null_default_when_not_found()
    {
        $this->assertNull(Config::getValue('tidak_ada_config_ini'));
    }

    public function test_set_value_creates_config_with_type()
    {
        Config::setValue('pin_length', 6, 'Panjang PIN', 'integer');

        $this->assertDatabaseHas('configs', [
            'name' => 'pin_length',
            'type' => 'integer',
            'value' => '6',
            'description' => 'Panjang PIN',
        ]);
        $this->assertSame(6, Config::getValue('pin_length'));
    }

    public function test_set_value_updates_existing_config_without_losing_type()
    {
        Config::create(['name' => 'pin_length', 'type' => 'integer', 'value' => '4']);

        Config::setValue('pin_length', 6);

        $this->assertSame(6, Config::getValue('pin_length'));
        $this->assertSame(1, Config::where('name', 'pin_length')->count());
        $this->assertSame('integer', Config::where('name', 'pin_length')->value('type'));
    }

    public function test_set_value_serializes_boolean_and_array()
    {
        Config::setValue('feature_flag', true, null, 'boolean');
        Config::setValue('feature_config', ['enabled' => true], null, 'json');

        $this->assertSame('1', Config::where('name', 'feature_flag')->value('value'));
        $this->assertTrue(Config::getValue('feature_flag'));
        $this->assertSame(['enabled' => true], Config::getValue('feature_config'));
    }

    public function test_cast_invalid_value_falls_back_to_raw_string()
    {
        Config::create(['name' => 'rusak', 'type' => 'integer', 'value' => 'bukan-angka']);

        $this->assertSame('bukan-angka', Config::getValue('rusak'));
    }

    public function test_cast_null_value_returns_null()
    {
        Config::create(['name' => 'kosong', 'type' => 'integer', 'value' => null]);

        $this->assertNull(Config::getValue('kosong'));
    }

    public function test_config_name_is_unique()
    {
        Config::create(['name' => 'duplicate_key', 'value' => '1']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Config::create(['name' => 'duplicate_key', 'value' => '2']);
    }
}
