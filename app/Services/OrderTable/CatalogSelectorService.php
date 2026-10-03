<?php

namespace App\Services\OrderTable;

use App\Models\Category;
use App\Models\OrderTable\Voucher;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CatalogSelectorService
{
    public const INTERNAL_ROUTES = ['menu', 'cart', 'orders', 'vouchers'];

    public function canUseGlobal(User $user): bool
    {
        $activeOutletIds = Outlets::query()
            ->when(Schema::hasColumn('outlets', 'is_active'), fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->where('is_active', '!=', false)->orWhereNull('is_active')))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        return $activeOutletIds->isNotEmpty()
            && $activeOutletIds->diff($user->outletIds())->isEmpty();
    }

    public function isAuthorizedOutlet(User $user, ?int $outletId): bool
    {
        return $outletId === null
            ? $this->canUseGlobal($user)
            : in_array($outletId, $user->outletIds(), true);
    }

    public function products(User $user, ?int $outletId, ?string $search = null): Collection
    {
        return $this->productQuery($user, $outletId)
            ->when($search, fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name'])
            ->map(fn (Product $product) => ['id' => $product->id, 'text' => $product->name]);
    }

    public function categories(User $user, ?int $outletId, ?string $search = null): Collection
    {
        return $this->categoryQuery($user, $outletId)
            ->when($search, fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name'])
            ->map(fn (Category $category) => ['id' => $category->id, 'text' => $category->name]);
    }

    public function vouchers(User $user, ?int $outletId, ?string $search = null): Collection
    {
        return $this->voucherTargetQuery($user, $outletId)
            ->when($search, fn (Builder $query) => $query->where('code', 'like', '%'.$search.'%'))
            ->orderBy('code')
            ->limit(50)
            ->get(['id', 'code'])
            ->map(fn (Voucher $voucher) => ['id' => $voucher->id, 'text' => $voucher->code]);
    }

    public function promos(User $user, ?int $outletId, ?string $search = null): Collection
    {
        return Promo::query()
            ->where('status', true)
            ->whereIn('outlet_id', $this->contextOutletIds($user, $outletId))
            ->when($search, fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name'])
            ->map(fn (Promo $promo) => ['id' => $promo->id, 'text' => $promo->name]);
    }

    public function internal(?string $search = null): Collection
    {
        return collect(self::INTERNAL_ROUTES)
            ->filter(fn (string $route) => ! $search || str_contains($route, $search))
            ->map(fn (string $route) => ['id' => $route, 'text' => ucfirst($route)])
            ->values();
    }

    public function validIds(string $type, User $user, ?int $outletId, array $ids): bool
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();
        $query = match ($type) {
            'product' => $this->productQuery($user, $outletId),
            'category' => $this->categoryQuery($user, $outletId),
            'voucher' => $this->voucherTargetQuery($user, $outletId),
            'promo' => Promo::query()->where('status', true)
                ->whereIn('outlet_id', $this->contextOutletIds($user, $outletId)),
            default => null,
        };

        return $query !== null && $query->whereKey($ids)->count() === $ids->count();
    }

    public function optionsByIds(string $type, User $user, ?int $outletId, array $ids): Collection
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty() || ! in_array($type, ['category', 'product'], true)) {
            return collect();
        }

        $query = $type === 'product'
            ? $this->productQuery($user, $outletId)
            : $this->categoryQuery($user, $outletId);

        return $query->whereKey($ids)->get(['id', 'name'])
            ->map(fn ($model) => ['id' => $model->id, 'text' => $model->name]);
    }

    public function optionById(string $type, User $user, ?int $outletId, mixed $id): ?array
    {
        if ($type === 'internal' && in_array($id, self::INTERNAL_ROUTES, true)) {
            return ['id' => $id, 'text' => ucfirst((string) $id)];
        }
        if ($type === 'url' || ! is_numeric($id)) {
            return null;
        }

        $model = match ($type) {
            'product' => $this->productQuery($user, $outletId)->whereKey((int) $id)->first(['id', 'name']),
            'category' => $this->categoryQuery($user, $outletId)->whereKey((int) $id)->first(['id', 'name']),
            'voucher' => $this->voucherTargetQuery($user, $outletId)->whereKey((int) $id)->first(['id', 'code']),
            'promo' => Promo::query()->where('status', true)
                ->whereIn('outlet_id', $this->contextOutletIds($user, $outletId))
                ->whereKey((int) $id)->first(['id', 'name']),
            default => null,
        };

        return $model ? [
            'id' => $model->id,
            'text' => $type === 'voucher' ? $model->code : $model->name,
        ] : null;
    }

    private function productQuery(User $user, ?int $outletId): Builder
    {
        return Product::query()
            ->where('status', true)
            ->whereIn('outlet_id', $this->contextOutletIds($user, $outletId));
    }

    private function categoryQuery(User $user, ?int $outletId): Builder
    {
        $outletIds = $this->contextOutletIds($user, $outletId);

        return Category::query()
            ->where('status', true)
            ->whereHas('products', fn (Builder $query) => $query
                ->where('status', true)
                ->whereIn('outlet_id', $outletIds));
    }

    private function voucherTargetQuery(User $user, ?int $outletId): Builder
    {
        $this->authorizeOutlet($user, $outletId);
        $query = Voucher::query()->where('status', true);

        if ($outletId === null) {
            return $query->whereNull('outlet_id');
        }

        return $query->where(fn (Builder $query) => $query
            ->whereNull('outlet_id')
            ->orWhere('outlet_id', $outletId));
    }

    private function contextOutletIds(User $user, ?int $outletId): array
    {
        $this->authorizeOutlet($user, $outletId);

        return $outletId === null ? $user->outletIds() : [$outletId];
    }

    private function authorizeOutlet(User $user, ?int $outletId): void
    {
        if (! $this->isAuthorizedOutlet($user, $outletId)) {
            throw new AuthorizationException;
        }
    }
}
