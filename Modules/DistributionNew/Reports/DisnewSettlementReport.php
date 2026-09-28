<?php
namespace Modules\DistributionNew\Reports;
use Modules\DistributionNew\Models\DisnewSettlement;
class DisnewSettlementReport
{
    public function query(int $businessId, array $filters = [])
    {
        return DisnewSettlement::where('business_id',$businessId)
            ->when($filters['sales_rep_id'] ?? null, fn($q,$v)=>$q->where('sales_rep_id',$v))
            ->when($filters['vehicle_id'] ?? null, fn($q,$v)=>$q->where('vehicle_id',$v));
    }
}
