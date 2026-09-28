<?php

namespace Modules\PetroDirectNew\Reports;

use Modules\PetroDirectNew\Entities\PdirectnewTankTransfer;

class TankTransfersReport extends AbstractReport
{
    public function key(): string { return 'tank_transfers'; }
    public function label(): string { return 'Tank Transfers'; }
    protected function modelClass(): string { return PdirectnewTankTransfer::class; }
}
