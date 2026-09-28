<?php

namespace Modules\RestaurantNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestaurantNewBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [];
    }
}
