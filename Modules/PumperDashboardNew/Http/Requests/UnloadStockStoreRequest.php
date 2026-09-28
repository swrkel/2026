<?php

namespace Modules\PumperDashboardNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnloadStockStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'store_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'bill_number' => ['nullable', 'string', 'max:191'],
            'supplier_reference' => ['nullable', 'string', 'max:191'],
            'unloaded_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'edit_reason' => [$this->isMethod('PUT') || $this->isMethod('PATCH') ? 'required' : 'nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => ['required', 'integer', 'min:1'],
            'lines.*.tank_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.dip_reading' => ['nullable', 'numeric', 'min:0'],
            'lines.*.current_stock' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
