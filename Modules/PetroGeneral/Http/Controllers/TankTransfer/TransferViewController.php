<?php

namespace Modules\PetroGeneral\Http\Controllers\TankTransfer;

use Illuminate\Routing\Controller;

class TransferViewController extends Controller
{
    public function show($id)
    {
        return app('Modules\PetroGeneral\Http\Controllers\TankTransferController')->show($id);
    }
}
