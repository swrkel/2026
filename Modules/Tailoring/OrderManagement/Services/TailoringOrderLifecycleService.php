<?php
namespace Modules\Tailoring\OrderManagement\Services;

class TailoringOrderLifecycleService
{
    public function stages(): array
    {
        return [
            'quotation',
            'order_confirmed',
            'advance_paid',
            'job_card_generated',
            'material_reserved',
            'production',
            'trial',
            'alteration',
            'quality_check',
            'final_payment',
            'delivery',
        ];
    }
}
