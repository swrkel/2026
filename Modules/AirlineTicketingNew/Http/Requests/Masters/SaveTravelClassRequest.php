<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class SaveTravelClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.travel-classes.manage');
    }

    public function rules(): array
    {
        return ['name'=>['required','string','max:100'],'code'=>['required','string','max:20'],'cabin_code'=>['nullable','string','max:5'],'display_order'=>['nullable','integer','min:0'],'is_active'=>['nullable','boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_base' => $this->has('is_base') ? $this->boolean('is_base') : null,
        ]);
    }
}
