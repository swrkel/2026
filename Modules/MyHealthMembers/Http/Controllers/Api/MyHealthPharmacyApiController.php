<?php

namespace Modules\MyHealthMembers\Http\Controllers\Api;

use Modules\MyHealthMembers\Entities\MyHealthDispense;
use Modules\MyHealthMembers\Entities\MyHealthDispenseItem;

class MyHealthPharmacyApiController extends MyHealthApiBaseController
{
    public function dispenses()
    {
        $dispenses = MyHealthDispense::where('member_id', $this->member()->id)
            ->latest('dispense_date')
            ->latest('id')
            ->paginate(20);

        return $this->success(['dispenses' => $dispenses]);
    }

    public function dispenseItems($dispenseId)
    {
        $dispense = MyHealthDispense::where('member_id', $this->member()->id)->findOrFail($dispenseId);
        $items = MyHealthDispenseItem::where('dispense_id', $dispense->id)->get();

        return $this->success(['dispense' => $dispense, 'items' => $items]);
    }
}
