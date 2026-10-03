<?php

namespace App\Http\Requests\OrderTable;

class StoreVoucherRequest extends VoucherRequest
{
    protected function ability(): string
    {
        return 'create order-table/vouchers';
    }
}
