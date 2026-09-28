<?php

namespace Modules\PetroGeneral\Http\Controllers\PumperDayEntry;

use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumperDayEntryController;

class PumperDayEntrySettlementNoController extends PumperDayEntryController
{
    public function addForm($id)
    {
        return parent::getAddSettlementNo($id);
    }

    public function save($id, Request $request)
    {
        return parent::postAddSettlementNo($id, $request);
    }

    public function view($id)
    {
        return parent::viewAddSettlementNo($id);
    }
}
