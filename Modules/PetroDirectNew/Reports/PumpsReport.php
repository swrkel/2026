<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewPump;

class PumpsReport extends AbstractReport
{
    public function key(): string { return 'pumps'; }
    public function label(): string { return 'Pumps'; }
    protected function modelClass(): string { return PdirectnewPump::class; }
}
