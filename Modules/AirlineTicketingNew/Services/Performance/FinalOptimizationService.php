<?php
namespace Modules\AirlineTicketingNew\Services\Performance;
class FinalOptimizationService {
 public function warm(int $businessId): array {$l=app(\Modules\AirlineTicketingNew\Services\Cache\LookupCacheService::class);return ['airlines_cached'=>$l->airlines($businessId)->count(),'airports_cached'=>$l->airports($businessId)->count(),'completed_at'=>now()->toDateTimeString()];}
}
