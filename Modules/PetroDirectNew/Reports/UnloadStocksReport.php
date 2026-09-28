<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewUnloadStock;

class UnloadStocksReport extends AbstractReport
{
    public function key(): string { return 'unload_stocks'; }
    public function label(): string { return 'Unload Stock'; }
    protected function modelClass(): string { return PdirectnewUnloadStock::class; }
}
