<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\BannerDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderTable\StoreBannerRequest;
use App\Http\Requests\OrderTable\UpdateBannerRequest;
use App\Models\OrderTable\Banner;
use App\Models\OrderTable\Voucher;
use App\Models\Outlets;
use App\Services\OrderTable\AuditLogger;
use App\Services\OrderTable\CatalogSelectorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BannerController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly CatalogSelectorService $catalogSelector,
    ) {
        $this->abilities = [];
    }

    public function index(BannerDataTable $dataTable)
    {
        $this->authorize('read order-table/banners');

        return $dataTable->render('layouts.order-table.banners.index', [
            'outlets' => $this->outlets(),
            'canManageGlobal' => $this->catalogSelector->canUseGlobal(request()->user()),
        ]);
    }

    public function create()
    {
        $this->authorize('create order-table/banners');

        return view('layouts.order-table.banners.create', [
            'banner' => new Banner([
                'position' => 'home_top',
                'sort_order' => 0,
                'status' => true,
            ]),
            'outlets' => $this->outlets(),
            'canUseGlobal' => $this->catalogSelector->canUseGlobal(request()->user()),
        ]);
    }

    public function store(StoreBannerRequest $request)
    {
        $uploadedPath = $request->hasFile('image_upload')
            ? $request->file('image_upload')->store('order-table/banners', 'public')
            : null;

        try {
            $banner = DB::transaction(function () use ($request, $uploadedPath) {
                $payload = Arr::except($request->validated(), ['image_upload']);
                if ($uploadedPath !== null) {
                    $payload['image_url'] = Storage::disk('public')->url($uploadedPath);
                }

                $banner = Banner::create($payload);
                $this->auditLogger->log('banner.created', $banner, null, $banner->attributesToArray());

                return $banner;
            });
        } catch (Throwable $exception) {
            if ($uploadedPath !== null) {
                Storage::disk('public')->delete($uploadedPath);
            }

            throw $exception;
        }

        return responseSuccess(false, false, ['id' => $banner->id]);
    }

    public function edit(int $banner)
    {
        $this->authorize('update order-table/banners');
        $banner = $this->banner($banner);

        return view('layouts.order-table.banners.edit', [
            'banner' => $banner,
            'outlets' => $this->outlets(),
            'canUseGlobal' => $this->catalogSelector->canUseGlobal(request()->user()),
            'targetOption' => $this->catalogSelector->optionById(
                $banner->action_type,
                request()->user(),
                $banner->outlet_id,
                $banner->action_value,
            ),
        ]);
    }

    public function update(UpdateBannerRequest $request, int $banner)
    {
        $banner = $this->banner($banner);
        $oldManagedPath = $this->managedPath($banner->image_url);
        $uploadedPath = $request->hasFile('image_upload')
            ? $request->file('image_upload')->store('order-table/banners', 'public')
            : null;

        try {
            DB::transaction(function () use ($request, $banner, $uploadedPath) {
                $before = $banner->attributesToArray();
                $payload = Arr::except($request->validated(), ['image_upload']);
                if ($uploadedPath !== null) {
                    $payload['image_url'] = Storage::disk('public')->url($uploadedPath);
                } elseif ($payload['image_url'] === null) {
                    unset($payload['image_url']);
                }

                $banner->fill($payload);
                $banner->save();
                $this->auditLogger->log('banner.updated', $banner, $before, $banner->fresh()->attributesToArray());
            });
        } catch (Throwable $exception) {
            if ($uploadedPath !== null) {
                Storage::disk('public')->delete($uploadedPath);
            }

            throw $exception;
        }

        if (($uploadedPath !== null || $request->filled('image_url'))
            && $oldManagedPath !== null
            && $oldManagedPath !== $uploadedPath) {
            Storage::disk('public')->delete($oldManagedPath);
        }

        return responseSuccess(true);
    }

    public function toggle(Request $request, int $banner)
    {
        $this->authorize('update order-table/banners');
        $banner = $this->banner($banner);

        DB::transaction(function () use ($banner) {
            $banner = Banner::query()->whereKey($banner->id)->lockForUpdate()->firstOrFail();
            if (! $banner->status) {
                abort_unless($this->bannerTargetIsValid($banner), 422, 'Target banner tidak lagi tersedia. Perbarui banner sebelum mengaktifkannya.');
            }
            $before = $banner->attributesToArray();
            $banner->status = ! $banner->status;
            $banner->save();
            $this->auditLogger->log(
                $banner->status ? 'banner.enabled' : 'banner.disabled',
                $banner,
                $before,
                $banner->fresh()->attributesToArray(),
            );
        });
        $banner->refresh();

        return responseSuccess(true, $banner->status ? 'Banner diaktifkan' : 'Banner dinonaktifkan');
    }

    private function banner(int $id): Banner
    {
        $user = request()->user();

        return Banner::query()
            ->whereKey($id)
            ->where(function (Builder $query) use ($user) {
                $query->whereIn('outlet_id', $user->outletIds());
                if ($this->catalogSelector->canUseGlobal($user)) {
                    $query->orWhereNull('outlet_id');
                }
            })
            ->firstOrFail();
    }

    private function outlets()
    {
        return Outlets::query()
            ->whereIn('id', request()->user()->outletIds())
            ->orderBy('name')
            ->get();
    }

    private function managedPath(?string $url): ?string
    {
        $url = (string) $url;
        $managedBase = rtrim(Storage::disk('public')->url('order-table/banners'), '/').'/';
        if (! str_starts_with($url, $managedBase)) {
            return null;
        }

        $path = ltrim(substr($url, strlen($managedBase)), '/');
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return 'order-table/banners/'.$path;
    }

    private function bannerTargetIsValid(Banner $banner): bool
    {
        if ($banner->action_type === 'url') {
            return filter_var($banner->action_value, FILTER_VALIDATE_URL)
                && in_array(strtolower((string) parse_url($banner->action_value, PHP_URL_SCHEME)), ['http', 'https'], true);
        }
        if ($banner->action_type === 'internal') {
            return in_array($banner->action_value, CatalogSelectorService::INTERNAL_ROUTES, true);
        }
        if ($banner->outlet_id === null) {
            return $banner->action_type === 'voucher'
                && Voucher::query()->whereKey($banner->action_value)->whereNull('outlet_id')->where('status', true)->exists();
        }

        return $this->catalogSelector->validIds(
            $banner->action_type,
            request()->user(),
            $banner->outlet_id,
            [$banner->action_value],
        );
    }
}
