<?php
return [
 'customers'=>['adapter'=>\Modules\EggManagement\Integrations\CustomerGateway::class,'required'=>false],
 'suppliers'=>['adapter'=>\Modules\EggManagement\Integrations\SupplierGateway::class,'required'=>false],
 'products_new'=>['adapter'=>\Modules\EggManagement\Integrations\ProductGateway::class,'required'=>false],
 'locations_stores'=>['adapter'=>\Modules\EggManagement\Integrations\LocationStoreGateway::class,'required'=>false],
 'finance'=>['adapter'=>\Modules\EggManagement\Integrations\FinanceGateway::class,'mode'=>'outbox','required'=>false],
 'messaging'=>['adapter'=>\Modules\EggManagement\Integrations\MessagingGateway::class,'required'=>false],
];
