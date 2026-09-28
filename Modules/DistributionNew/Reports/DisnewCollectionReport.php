<?php
namespace Modules\DistributionNew\Reports;
use Modules\DistributionNew\Models\DisnewCollection;
class DisnewCollectionReport
{
    public function query(int $businessId, array $filters = [])
    {
        return DisnewCollection::where('business_id',$businessId)
            ->when($filters['from'] ?? null, fn($q,$v)=>$q->whereDate('collection_date','>=',$v))
            ->when($filters['to'] ?? null, fn($q,$v)=>$q->whereDate('collection_date','<=',$v));
    }
}
