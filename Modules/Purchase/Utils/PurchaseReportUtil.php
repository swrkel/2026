<?php
namespace Modules\Purchase\Utils;

class PurchaseReportUtil
{
    public function defaultColumns(): array
    {
        return ['date','ref_no','supplier','status','total'];
    }
}
