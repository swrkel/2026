<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewDipReading;

class DipReadingsReport extends AbstractReport
{
    public function key(): string { return 'dip_readings'; }
    public function label(): string { return 'Dip Readings'; }
    protected function modelClass(): string { return PdirectnewDipReading::class; }
}
