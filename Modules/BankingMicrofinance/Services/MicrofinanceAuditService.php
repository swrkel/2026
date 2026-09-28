<?php

namespace Modules\BankingMicrofinance\Services;

use Modules\BankingMicrofinance\Entities\MicrofinanceAuditEvent;

class MicrofinanceAuditService
{
    public function log(string $eventType, string $area, $recordId=null, array $payload=[], string $severity='info'): MicrofinanceAuditEvent
    {
        return MicrofinanceAuditEvent::create(['event_type'=>$eventType,'module_area'=>$area,'record_id'=>$recordId,'payload'=>$payload,'severity'=>$severity,'created_by'=>auth()->id()]);
    }
}
