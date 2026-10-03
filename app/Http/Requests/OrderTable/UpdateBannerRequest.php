<?php

namespace App\Http\Requests\OrderTable;

class UpdateBannerRequest extends BannerRequest
{
    protected function ability(): string
    {
        return 'update order-table/banners';
    }

    protected function imageRequired(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['prohibited'],
        ]);
    }
}
