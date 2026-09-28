<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class SaveCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.currencies.manage');
    }

    public function rules(): array
    {
        return ['code'=>['required','string','size:3'],'name'=>['required','string','max:100'],'symbol'=>['nullable','string','max:10'],'decimal_places'=>['required','integer','min:0','max:6'],'exchange_rate'=>['required','numeric','gt:0'],'is_base'=>['nullable','boolean'],'is_active'=>['nullable','boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_base' => $this->has('is_base') ? $this->boolean('is_base') : null,
        ]);
    }
}
