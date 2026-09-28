<?php

namespace Modules\EnterpriseFramework\Services\Filter;

use Illuminate\Http\Request;

class FilterContextService
{
    public function fromRequest(Request $request): array
    {
        return [
            'business_id' => session('business.id'),
            'location_id' => $request->get('location_id'),
            'consolidated' => (bool) $request->get('consolidated', false),
            'financial_year' => $request->get('financial_year'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'user_id' => auth()->id(),
        ];
    }
}
