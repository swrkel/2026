<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class SaveAirportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.airports.manage');
    }

    public function rules(): array
    {
        return ['name'=>['required','string','max:150'],'iata_code'=>['required','string','size:3'],'icao_code'=>['nullable','string','size:4'],'country_code'=>['required','string','size:2'],'city'=>['required','string','max:100'],'timezone'=>['nullable','string','max:80'],'latitude'=>['nullable','numeric'],'longitude'=>['nullable','numeric'],'terminal_notes'=>['nullable','string'],'is_active'=>['nullable','boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_base' => $this->has('is_base') ? $this->boolean('is_base') : null,
        ]);
    }
}
