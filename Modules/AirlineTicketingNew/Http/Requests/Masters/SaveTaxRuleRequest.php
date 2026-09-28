<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class SaveTaxRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.tax-rules.manage');
    }

    public function rules(): array
    {
        return ['name'=>['required','string','max:150'],'code'=>['required','string','max:40'],'calculation_type'=>['required','in:percentage,fixed'],'rate'=>['nullable','numeric','min:0'],'fixed_amount'=>['nullable','numeric','min:0'],'applies_to'=>['required','string','max:40'],'country_code'=>['nullable','string','size:2'],'is_active'=>['nullable','boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_base' => $this->has('is_base') ? $this->boolean('is_base') : null,
        ]);
    }
}
