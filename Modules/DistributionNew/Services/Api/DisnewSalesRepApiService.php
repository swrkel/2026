<?php

namespace Modules\DistributionNew\Services\Api;

use Illuminate\Http\Request;

class DisnewSalesRepApiService
{
    public function dashboard(Request $request): array { return ['success'=>true,'data'=>['orders_today'=>0,'collections_today'=>0,'pending_deliveries'=>0]]; }
    public function storeOrder(Request $request): array { return ['success'=>true,'message'=>'Sales rep order accepted for processing']; }
    public function storeCollection(Request $request): array { return ['success'=>true,'message'=>'Sales rep collection accepted for processing']; }
}
