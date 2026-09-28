<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewDailyCollection;

class CollectionsReport extends AbstractReport
{
    public function key(): string { return 'collections'; }
    public function label(): string { return 'Daily Collections'; }
    protected function modelClass(): string { return PdirectnewDailyCollection::class; }
}
