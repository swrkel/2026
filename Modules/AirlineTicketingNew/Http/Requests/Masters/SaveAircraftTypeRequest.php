<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class SaveAircraftTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.aircraft-types.manage');
    }

    public function rules(): array
    {
        return ['name'=>['required','string','max:150'],'iata_code'=>['nullable','string','max:10'],'icao_code'=>['nullable','string','max:10'],'manufacturer'=>['nullable','string','max:100'],'model'=>['nullable','string','max:100'],'seat_capacity'=>['nullable','integer','min:1'],'is_active'=>['nullable','boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_base' => $this->has('is_base') ? $this->boolean('is_base') : null,
        ]);
    }
}
