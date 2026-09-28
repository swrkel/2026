<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class SaveAirlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.airlines.manage');
    }

    public function rules(): array
    {
        return ['name'=>['required','string','max:150'],'iata_code'=>['nullable','string','size:2'],'icao_code'=>['nullable','string','size:3'],'ticketing_code'=>['nullable','string','max:3'],'country_code'=>['nullable','string','size:2'],'phone'=>['nullable','string','max:40'],'email'=>['nullable','email','max:150'],'website'=>['nullable','url','max:190'],'notes'=>['nullable','string'],'is_active'=>['nullable','boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_base' => $this->has('is_base') ? $this->boolean('is_base') : null,
        ]);
    }
}
