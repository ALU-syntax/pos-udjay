<?php

namespace App\Http\Requests\OrderTable;

use App\Models\OrderTable\Voucher;
use App\Services\OrderTable\CatalogSelectorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class VoucherRequest extends FormRequest
{
    abstract protected function ability(): string;

    protected function prepareForValidation(): void
    {
        $scope = $this->input('scope');

        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'scope_ids' => $scope === 'all' ? null : $this->input('scope_ids'),
        ]);
    }

    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user?->can($this->ability())) {
            return false;
        }

        $selector = app(CatalogSelectorService::class);
        $outletId = $this->filled('outlet_id') ? $this->integer('outlet_id') : null;
        if (! $selector->isAuthorizedOutlet($user, $outletId)) {
            return false;
        }

        $voucher = $this->routeVoucher();

        return $voucher === null || $selector->isAuthorizedOutlet($user, $voucher->outlet_id);
    }

    public function rules(): array
    {
        $voucherId = $this->routeVoucher()?->getKey()
            ?? (int) ($this->route('voucher') ?? 0);
        $type = $this->input('type');
        $scope = $this->input('scope');

        return [
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('ot_vouchers', 'code')->ignore($voucherId)],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => $type === 'percent'
                ? ['required', 'integer', 'min:1', 'max:100']
                : ['required', 'integer', 'min:1'],
            'min_spend' => ['required', 'integer', 'min:0'],
            'max_discount' => $type === 'percent'
                ? ['nullable', 'integer', 'min:0']
                : ['prohibited'],
            'quota_total' => ['nullable', 'integer', 'min:1'],
            'quota_per_device' => ['nullable', 'integer', 'min:1', 'max:1'],
            'quota_per_session' => ['nullable', 'integer', 'min:1', 'max:1'],
            'quota_used' => ['prohibited'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => $this->filled('valid_from')
                ? ['nullable', 'date', 'after_or_equal:valid_from']
                : ['nullable', 'date'],
            'scope' => ['required', Rule::in(['all', 'category', 'product'])],
            'scope_ids' => $scope === 'all'
                ? ['nullable', Rule::in([null])]
                : ['required', 'array', 'min:1'],
            'scope_ids.*' => ['integer', 'distinct'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['outlet_id', 'scope', 'scope_ids', 'scope_ids.*'])) {
                return;
            }

            $scope = $this->input('scope');
            if (! $this->filled('outlet_id') && $scope !== 'all') {
                $validator->errors()->add('scope', 'Voucher global hanya dapat menggunakan cakupan semua produk.');

                return;
            }
            if (! in_array($scope, ['category', 'product'], true)) {
                return;
            }

            $outletId = $this->filled('outlet_id') ? $this->integer('outlet_id') : null;
            if (! app(CatalogSelectorService::class)->validIds(
                $scope,
                $this->user(),
                $outletId,
                $this->input('scope_ids', []),
            )) {
                $validator->errors()->add('scope_ids', 'Pilihan scope tidak tersedia untuk outlet yang dipilih.');
            }
        });
    }

    private function routeVoucher(): ?Voucher
    {
        $value = $this->route('voucher');
        if ($value instanceof Voucher) {
            return $value;
        }

        return is_numeric($value) ? Voucher::query()->find((int) $value) : null;
    }
}
