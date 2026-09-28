<?php

namespace Modules\PetroGeneral\Http\Controllers\PumperDayEntry;

use Modules\PetroGeneral\Http\Controllers\PumperDayEntryController;

class PumperDayEntrySummaryController extends PumperDayEntryController
{
    public function dayEntrySummary()
    {
        return parent::getPumperDayEntrySummary();
    }

    public function closingShiftSummary()
    {
        return parent::getClosingShiftSummary();
    }
}
