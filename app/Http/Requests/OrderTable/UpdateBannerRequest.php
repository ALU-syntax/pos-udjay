<?php

namespace App\Http\Requests\OrderTable;

class UpdateBannerRequest extends BannerRequest
{
    protected function ability(): string
    {
        return 'update order-table/banners';
    }

    // A text card saved without a background has no image to keep when switching to image mode.
    protected function imageRequired(): bool
    {
        return blank($this->routeBanner()?->image_url);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['prohibited'],
        ]);
    }
}
