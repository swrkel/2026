<?php

namespace Modules\ProductsNew\Http\Requests;

class StoreProductRequest extends ProductFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'sku' => 'nullable|string|max:191',
        ]);
    }
}
