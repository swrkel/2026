<?php

namespace Modules\PetroDirect\Services;

class PetroDirectDashboardService
{
    public function summary(): array
    {
        return [
            'module' => 'Petro Direct',
            'status' => 'foundation_ready',
            'message' => 'PetroDirect standalone foundation is installed. Functional migrations will be added in the next phases.',
        ];
    }
}
