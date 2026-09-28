<?php

namespace Modules\LeadsNew\Services\Branch;

class LeadsNewBranchFilterService
{
    public function filtersFromRequest($request): array
    {
        return [
            'business_id' => session('business.id'),
            'location_id' => $request->get('location_id'),
            'territory_id' => $request->get('territory_id'),
            'assigned_to' => $request->get('assigned_to'),
            'status_id' => $request->get('status_id'),
            'source_id' => $request->get('source_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];
    }
}
