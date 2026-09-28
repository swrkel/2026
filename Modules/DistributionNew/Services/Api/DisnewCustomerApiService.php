<?php

namespace Modules\DistributionNew\Services\Api;

use Illuminate\Http\Request;

class DisnewCustomerApiService
{
    public function orders(Request $request): array { return ['success'=>true,'data'=>[],'message'=>'Customer order list endpoint ready']; }
    public function storeOrder(Request $request): array { return ['success'=>true,'message'=>'Customer sales order accepted for validation']; }
    public function invoices(Request $request): array { return ['success'=>true,'data'=>[],'message'=>'Customer invoice endpoint ready']; }
    public function deliveries(Request $request): array { return ['success'=>true,'data'=>[],'message'=>'Customer delivery tracking endpoint ready']; }
}
