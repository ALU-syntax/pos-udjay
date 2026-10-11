<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\OutletSettingDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderTable\StoreOutletSettingRequest;
use App\Http\Requests\OrderTable\UpdateOrderTablePublicUrlRequest;
use App\Http\Requests\OrderTable\UpdateOutletSettingRequest;
use App\Models\Config;
use App\Models\OrderTable\OutletSetting;
use App\Models\Outlets;
use App\Services\OrderTable\AuditLogger;
use App\Services\OrderTable\DiningTableQrService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OutletSettingController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
        $this->abilities = [];
    }

    public function index(OutletSettingDataTable $dataTable)
    {
        $this->authorize('read order-table/outlet-settings');

        return $dataTable->render('layouts.order-table.outlet-settings.index', [
            'outlets' => $this->outlets(),
            'publicUrl' => $this->publicUrlData(),
        ]);
    }

    public function updatePublicUrl(UpdateOrderTablePublicUrlRequest $request)
    {
        $before = $this->publicUrlData();
        $value = trim((string) $request->input('public_url'));

        Config::setValue(
            DiningTableQrService::PUBLIC_URL_CONFIG_KEY,
            $value === '' ? null : $value,
            'Base URL halaman order customer (QR meja). Kosong = pakai domain backoffice',
            'string',
        );

        $after = $this->publicUrlData();
        $this->auditLogger->log('outlet-setting.public-url-updated', new OutletSetting, $before, $after);

        return responseSuccess(true, 'Base URL QR berhasil disimpan');
    }

    private function publicUrlData(): array
    {
        $config = Config::query()
            ->where('name', DiningTableQrService::PUBLIC_URL_CONFIG_KEY)
            ->first();

        return [
            'value' => (string) ($config?->value ?? ''),
            'resolved' => DiningTableQrService::resolveBaseUrl(),
            'fallback' => config('app.url'),
        ];
    }

    public function create()
    {
        $this->authorize('create order-table/outlet-settings');

        return view('layouts.order-table.outlet-settings.create', [
            'setting' => new OutletSetting([
                'order_enabled' => false,
                'stock_mode' => 'status_only',
                'forced_close' => false,
                'auto_preparing_delay_seconds' => 30,
                'session_close_time' => '23:59:00',
            ]),
            'outlets' => $this->outlets()
                ->whereNotIn('id', OutletSetting::query()->pluck('outlet_id')),
        ]);
    }

    public function store(StoreOutletSettingRequest $request)
    {
        $validated = $request->validated();
        $setting = DB::transaction(function () use ($validated) {
            $setting = OutletSetting::create(Arr::except($validated, [
                'latitude',
                'longitude',
                'geofence_radius_m',
            ]));

            $outlet = Outlets::query()->findOrFail($validated['outlet_id']);
            $outlet->fill(Arr::only($validated, ['latitude', 'longitude', 'geofence_radius_m']))->save();
            $setting->load('outlet');
            $this->auditLogger->log('outlet-setting.created', $setting, null, $this->snapshot($setting));

            return $setting;
        });

        return responseSuccess(false, false, ['id' => $setting->id]);
    }

    public function edit(int $outletSetting)
    {
        $this->authorize('update order-table/outlet-settings');
        $setting = $this->setting($outletSetting);

        return view('layouts.order-table.outlet-settings.edit', [
            'data' => $setting,
            'outlet' => $setting->outlet,
        ]);
    }

    public function update(UpdateOutletSettingRequest $request, int $outletSetting)
    {
        $setting = $this->setting($outletSetting);
        $validated = $request->validated();

        DB::transaction(function () use ($setting, $validated) {
            $setting->load('outlet');
            $before = $this->snapshot($setting);

            $setting->fill(Arr::except($validated, [
                'outlet_id',
                'forced_close',
                'latitude',
                'longitude',
                'geofence_radius_m',
            ]));
            $setting->save();

            $setting->outlet->fill(Arr::only($validated, [
                'latitude',
                'longitude',
                'geofence_radius_m',
            ]));
            $setting->outlet->save();

            $setting->refresh()->load('outlet');
            $this->auditLogger->log('outlet-setting.updated', $setting, $before, $this->snapshot($setting));
        });

        return responseSuccess(true);
    }

    public function toggleForcedClose(Request $request, int $outletSetting)
    {
        $this->authorize('update order-table/outlet-settings');
        $setting = $this->setting($outletSetting);

        DB::transaction(function () use ($setting) {
            $before = $setting->attributesToArray();
            $setting->forced_close = ! $setting->forced_close;
            $setting->save();

            $this->auditLogger->log(
                $setting->forced_close ? 'outlet-setting.forced-close' : 'outlet-setting.forced-open',
                $setting,
                $before,
                $setting->fresh()->attributesToArray(),
            );
        });

        return responseSuccess(true, $setting->forced_close ? 'Order outlet dijeda' : 'Order outlet dibuka');
    }

    private function setting(int $id): OutletSetting
    {
        return OutletSetting::query()
            ->whereKey($id)
            ->whereIn('outlet_id', request()->user()->outletIds())
            ->firstOrFail();
    }

    private function snapshot(OutletSetting $setting): array
    {
        return array_merge($setting->attributesToArray(), [
            'latitude' => $setting->outlet->latitude,
            'longitude' => $setting->outlet->longitude,
            'geofence_radius_m' => $setting->outlet->geofence_radius_m,
        ]);
    }

    private function outlets()
    {
        return Outlets::query()
            ->whereIn('id', request()->user()->outletIds())
            ->orderBy('name')
            ->get();
    }
}
