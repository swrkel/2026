<?php

namespace Modules\Pawning\Reports;

use Modules\Pawning\Models\Pledge;

class PawningRegisterReport
{
    public function query(array $filters = [])
    {
        $query = Pledge::query();
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->orderBy('pledged_on', 'desc');
    }
}
