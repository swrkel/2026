<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewAssignment;

class AssignmentsReport extends AbstractReport
{
    public function key(): string { return 'assignments'; }
    public function label(): string { return 'Pump Assignments'; }
    protected function modelClass(): string { return PdirectnewAssignment::class; }
}
