<?php

namespace Modules\StockTakingNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:160',
            'count_date' => 'required|date',
            'location_id' => 'required|integer|min:1',
            'store_id' => 'nullable|integer|min:1',
            'count_method' => 'required|in:full,cycle,spot',
            'count_mode' => 'required|in:blind,open',
            'freeze_stock' => 'nullable|boolean',
            'require_recount' => 'nullable|boolean',
            'variance_qty_threshold' => 'nullable|numeric|min:0',
            'variance_value_threshold' => 'nullable|numeric|min:0',
            'template_id' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:3000',
            'scope.category_id' => 'nullable|integer|min:1',
            'scope.brand_id' => 'nullable|integer|min:1',
            'scope.product_ids' => 'nullable|array',
            'scope.product_ids.*' => 'integer|min:1|distinct',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'integer|min:1|distinct',
        ];
    }
}
