<?php

namespace Modules\ProductsNew\Http\Requests;

class UpdateProductRequest extends ProductFormRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // SKU is immutable after creation. Ignore a crafted replacement value.
        $this->request->remove('sku');
    }

    public function rules(): array
    {
        return $this->commonRules();
    }
}
