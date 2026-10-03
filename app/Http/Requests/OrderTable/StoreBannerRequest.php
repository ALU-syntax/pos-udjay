<?php

namespace App\Http\Requests\OrderTable;

class StoreBannerRequest extends BannerRequest
{
    protected function ability(): string
    {
        return 'create order-table/banners';
    }

    protected function imageRequired(): bool
    {
        return true;
    }
}
