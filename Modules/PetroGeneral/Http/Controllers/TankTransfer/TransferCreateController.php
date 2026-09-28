<?php

namespace Modules\PetroGeneral\Http\Controllers\TankTransfer;

use Illuminate\Routing\Controller;

class TransferCreateController extends Controller
{
    public function create()
    {
        return app('Modules\PetroGeneral\Http\Controllers\TankTransferController')->create();
    }
}
