<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewAdjustment;

class AdjustmentsReport extends AbstractReport
{
    public function key(): string { return 'adjustments'; }
    public function label(): string { return 'Excess / Shortage Adjustments'; }
    protected function modelClass(): string { return PdirectnewAdjustment::class; }
}
