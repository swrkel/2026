<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Facades\DB;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;

class PurchaseEntryDeleteService
{
    public function __construct(
        protected PurchaseEntryLifecycleService $lifecycle,
        protected PurchaseEntryTankService $tanks,
        protected PurchaseDateNumberUtil $numbers
    ) {
    }

    public function delete(int $id): void
    {
        $document = null;

        DB::transaction(function () use ($id, &$document): void {
            $purchase = $this->lifecycle->lockPurchase($id, $this->numbers->businessId());
            $lines = $this->lifecycle->linesForUpdate($id);
            $this->lifecycle->assertCanModify($purchase, $lines);
            $this->lifecycle->reverseReceivedStock($purchase, $lines);
            $this->tanks->reverseAndDelete($id);
            $this->lifecycle->clearRelatedRecords($id);
            $this->lifecycle->deleteTransaction($id);
            $document = $purchase->document ?? null;
        }, 3);

        $this->lifecycle->removeDocument($document);
    }
}
