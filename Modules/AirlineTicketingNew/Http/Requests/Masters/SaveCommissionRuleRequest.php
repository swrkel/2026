<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class SaveCommissionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.commission-rules.manage');
    }

    public function rules(): array
    {
        return ['name'=>['required','string','max:150'],'code'=>['required','string','max:40'],'party_type'=>['required','string','max:30'],'calculation_type'=>['required','in:percentage,fixed'],'value'=>['required','numeric','min:0'],'airline_id'=>['nullable','integer'],'route_id'=>['nullable','integer'],'travel_class_id'=>['nullable','integer'],'effective_from'=>['nullable','date'],'effective_to'=>['nullable','date','after_or_equal:effective_from'],'is_active'=>['nullable','boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_base' => $this->has('is_base') ? $this->boolean('is_base') : null,
        ]);
    }
}
