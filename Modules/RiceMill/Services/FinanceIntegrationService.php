<?php
namespace Modules\RiceMill\Services;

use Modules\RiceMill\Models\FinanceOutbox;
use Modules\RiceMill\Events\RiceMillFinancialTransactionReady;

class FinanceIntegrationService
{
    public function queue(int $businessId, string $eventType, string $sourceType, int $sourceId, array $payload): FinanceOutbox
    {
        $row=FinanceOutbox::create([
            'business_id'=>$businessId,'event_type'=>$eventType,'source_type'=>$sourceType,'source_id'=>$sourceId,
            'payload'=>$payload,'status'=>'pending','attempts'=>0,
        ]);
        event(new RiceMillFinancialTransactionReady($row->id,$payload));
        $handler=config('ricemill.finance_handler');
        if ($handler && class_exists($handler)) {
            try {
                app($handler)->handleRiceMillTransaction($row->toArray());
                $row->update(['status'=>'processed','processed_at'=>now()]);
            } catch (\Throwable $e) {
                $row->update(['status'=>'failed','attempts'=>1,'last_error'=>mb_substr($e->getMessage(),0,1000)]);
            }
        }
        return $row;
    }
}
