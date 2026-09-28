<?php
namespace Modules\DistributionNew\Services\LiveOperations;
class CommandCentreService
{
 public function dashboard($request): array
 {
  return [
   'vehicles_on_road'=>0,'drivers_active'=>0,'deliveries_pending'=>0,'deliveries_delayed'=>0,
   'route_completion_percent'=>0,'alerts'=>[],'timeline'=>[]
  ];
 }
}
