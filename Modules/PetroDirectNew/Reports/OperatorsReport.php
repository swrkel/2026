<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewOperator;

class OperatorsReport extends AbstractReport
{
    public function key(): string { return 'operators'; }
    public function label(): string { return 'Pump Operators'; }
    protected function modelClass(): string { return PdirectnewOperator::class; }
}
