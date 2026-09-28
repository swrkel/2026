<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement;

use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\SettlementController;

class SettlementSaveController extends SettlementController
{
    public function store(Request $request)
    {
        return parent::store($request);
    }

    public function update(Request $request, $id)
    {
        return parent::update($request, $id);
    }
}
