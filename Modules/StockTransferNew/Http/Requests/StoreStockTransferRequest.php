<?php

namespace Modules\StockTransferNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'from_location_id' => ['required','integer'],
            'to_location_id' => ['required','integer','different:from_location_id'],
            'from_store_id' => ['nullable','integer'],
            'to_store_id' => ['nullable','integer'],
            'transfer_date' => ['required','date'],
            'expected_date' => ['nullable','date'],
            'lines' => ['required','array','min:1'],
            'lines.*.product_id' => ['required','integer'],
            'lines.*.variation_id' => ['nullable','integer'],
            'lines.*.qty_requested' => ['required','numeric','gt:0'],
            'lines.*.unit_cost' => ['nullable','numeric','min:0'],
        ];
    }
}
