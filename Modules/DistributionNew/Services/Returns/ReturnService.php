<?php

namespace Modules\DistributionNew\Services\Returns;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewReturn;
use Modules\DistributionNew\Models\DisnewReturnLine;
use Modules\DistributionNew\Services\Stock\StockMovementService;
use Modules\DistributionNew\Services\Sms\DistributionNewSmsEventService;

class ReturnService
{
    public function create(array $payload, array $lines): DisnewReturn
    {
        return DB::transaction(function () use ($payload, $lines) {
            $return = DisnewReturn::create($payload);
            foreach ($lines as $line) {
                $line['business_id'] = $payload['business_id'];
                $line['return_id'] = $return->id;
                DisnewReturnLine::create($line);
            }
            app(DistributionNewSmsEventService::class)->queue('return_created', $return);
            return $return;
        });
    }

    public function approve(DisnewReturn $return): DisnewReturn
    {
        return DB::transaction(function () use ($return) {
            $return->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
            app(StockMovementService::class)->recordReturnMovement($return);
            app(DistributionNewSmsEventService::class)->queue('return_approved', $return);
            return $return->refresh();
        });
    }
}
