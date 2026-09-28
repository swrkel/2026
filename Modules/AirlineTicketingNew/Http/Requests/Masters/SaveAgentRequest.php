<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class SaveAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.agents.manage');
    }

    public function rules(): array
    {
        return ['code'=>['required','string','max:40'],'name'=>['required','string','max:150'],'agent_type'=>['required','string','max:40'],'contact_person'=>['nullable','string','max:150'],'phone'=>['nullable','string','max:40'],'email'=>['nullable','email','max:150'],'address'=>['nullable','string'],'commission_type'=>['nullable','string','max:20'],'commission_value'=>['nullable','numeric','min:0'],'credit_limit'=>['nullable','numeric','min:0'],'is_active'=>['nullable','boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_base' => $this->has('is_base') ? $this->boolean('is_base') : null,
        ]);
    }
}
