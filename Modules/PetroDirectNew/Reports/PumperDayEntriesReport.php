<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewPumperDayEntry;

class PumperDayEntriesReport extends AbstractReport
{
    public function key(): string { return 'pumper_day_entries'; }
    public function label(): string { return 'Pumper Day Entries'; }
    protected function modelClass(): string { return PdirectnewPumperDayEntry::class; }
}
