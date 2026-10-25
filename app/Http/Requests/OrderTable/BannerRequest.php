<?php

namespace App\Http\Requests\OrderTable;

use App\Models\OrderTable\Banner;
use App\Services\OrderTable\CatalogSelectorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class BannerRequest extends FormRequest
{
    abstract protected function ability(): string;

    abstract protected function imageRequired(): bool;

    protected function prepareForValidation(): void
    {
        $textMode = $this->input('display_mode') === 'text';

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'image_url' => $this->filled('image_url') ? trim((string) $this->input('image_url')) : null,
            'action_value' => $this->filled('action_value') && $this->input('action_type') !== 'none'
                ? trim((string) $this->input('action_value'))
                : null,
            // Image banners carry their wording inside the artwork, so stale text fields are dropped.
            'eyebrow' => $textMode && $this->filled('eyebrow') ? trim((string) $this->input('eyebrow')) : null,
            'subtitle' => $textMode && $this->filled('subtitle') ? trim((string) $this->input('subtitle')) : null,
            'tone' => $textMode ? $this->input('tone') : null,
            'remove_image' => $textMode && $this->boolean('remove_image'),
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

        $banner = $this->routeBanner();

        return $banner === null || $selector->isAuthorizedOutlet($user, $banner->outlet_id);
    }

    public function rules(): array
    {
        $actionType = $this->input('action_type');
        $textMode = $this->input('display_mode') === 'text';
        $imageUrlRules = ['nullable', 'string', 'max:500', 'url', 'regex:/^https?:\/\//i'];
        $imageUploadRules = ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'];

        if (! $textMode && $this->imageRequired()) {
            array_unshift($imageUrlRules, 'required_without:image_upload');
            array_unshift($imageUploadRules, 'required_without:image_url');
        }

        return [
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'display_mode' => ['required', Rule::in(Banner::DISPLAY_MODES)],
            // In text mode the title is the headline on a 16:9 card, so it must stay short.
            'title' => ['required', 'string', $textMode ? 'max:60' : 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:40'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'tone' => $textMode ? ['required', Rule::in(Banner::TONES)] : ['nullable'],
            'image_url' => $imageUrlRules,
            'image_upload' => $imageUploadRules,
            'remove_image' => ['boolean'],
            'action_type' => ['required', Rule::in(Banner::ACTION_TYPES)],
            'action_value' => match ($actionType) {
                'url' => ['required', 'string', 'max:500', 'url', 'regex:/^https?:\/\//i'],
                'product', 'category', 'voucher' => ['required', 'integer', 'min:1'],
                default => ['nullable'],
            },
            'start_at' => ['nullable', 'required_with:end_at', 'date'],
            'end_at' => ['nullable', 'required_with:start_at', 'date', 'after_or_equal:start_at'],
            'status' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('image_url') && $this->hasFile('image_upload')) {
                $validator->errors()->add('image_upload', 'Pilih tepat satu sumber gambar.');
            }

            if ($validator->errors()->hasAny(['outlet_id', 'action_type', 'action_value'])) {
                return;
            }

            $type = $this->input('action_type');
            if (! $this->filled('outlet_id') && ! in_array($type, ['none', 'url', 'voucher'], true)) {
                $validator->errors()->add('action_type', 'Banner global hanya dapat menggunakan URL, voucher global, atau tanpa aksi.');

                return;
            }
            if (! $this->filled('outlet_id') && $type === 'voucher') {
                $voucherIsGlobal = \App\Models\OrderTable\Voucher::query()
                    ->whereKey($this->input('action_value'))
                    ->whereNull('outlet_id')
                    ->where('status', true)
                    ->exists();

                if (! $voucherIsGlobal) {
                    $validator->errors()->add('action_value', 'Banner global hanya dapat diarahkan ke voucher global yang aktif.');
                }

                return;
            }
            if (! in_array($type, ['product', 'category', 'voucher'], true)) {
                return;
            }

            $outletId = $this->filled('outlet_id') ? $this->integer('outlet_id') : null;
            if (! app(CatalogSelectorService::class)->validIds(
                $type,
                $this->user(),
                $outletId,
                [$this->input('action_value')],
            )) {
                $validator->errors()->add('action_value', 'Target tidak tersedia untuk outlet yang dipilih.');
            }
        });
    }

    protected function routeBanner(): ?Banner
    {
        $value = $this->route('banner');
        if ($value instanceof Banner) {
            return $value;
        }

        return is_numeric($value) ? Banner::query()->find((int) $value) : null;
    }
}
