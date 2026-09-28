<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class SaveRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.routes.manage');
    }

    public function rules(): array
    {
        return ['code'=>['required','string','max:40'],'origin_airport_id'=>['required','integer'],'destination_airport_id'=>['required','integer','different:origin_airport_id'],'default_airline_id'=>['nullable','integer'],'distance_km'=>['nullable','numeric','min:0'],'duration_minutes'=>['nullable','integer','min:0'],'is_active'=>['nullable','boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_base' => $this->has('is_base') ? $this->boolean('is_base') : null,
        ]);
    }
}
