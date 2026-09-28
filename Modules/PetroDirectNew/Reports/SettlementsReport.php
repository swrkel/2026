<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewSettlement;

class SettlementsReport extends AbstractReport
{
    public function key(): string { return 'settlements'; }
    public function label(): string { return 'Direct Settlements'; }
    protected function modelClass(): string { return PdirectnewSettlement::class; }
}
