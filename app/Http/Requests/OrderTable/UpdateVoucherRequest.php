<?php

namespace App\Http\Requests\OrderTable;

class UpdateVoucherRequest extends VoucherRequest
{
    protected function ability(): string
    {
        return 'update order-table/vouchers';
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['prohibited'],
        ]);
    }
}
