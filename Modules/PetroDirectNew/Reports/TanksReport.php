<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewTank;

class TanksReport extends AbstractReport
{
    public function key(): string { return 'tanks'; }
    public function label(): string { return 'Fuel Tanks'; }
    protected function modelClass(): string { return PdirectnewTank::class; }
}
