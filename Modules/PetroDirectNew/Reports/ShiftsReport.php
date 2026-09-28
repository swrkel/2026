<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewShift;

class ShiftsReport extends AbstractReport
{
    public function key(): string { return 'shifts'; }
    public function label(): string { return 'Shifts'; }
    protected function modelClass(): string { return PdirectnewShift::class; }
}
