<?php
namespace Modules\EggManagement\Http\Controllers\Reports;
use Illuminate\Routing\Controller;use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Schema;use Modules\EggManagement\Utilities\DateRange;use Modules\EggManagement\Services\EggContext;use Modules\EggManagement\Integrations\LocationStoreGateway;
abstract class BaseReportController extends Controller
{
    protected function reportData($request,$table,$dateColumn,EggContext $context,LocationStoreGateway $directory,array $columns)
    {
        [$from,$to]=DateRange::resolve($request);$location=$request->input('location_id',$context->locationId() ?: 'all');$store=$request->input('store_id',$context->storeId() ?: 'all');
        $q=DB::connection(config('egg.connection'))->table($table)->where('business_id',$context->businessId())->whereNull('deleted_at')->whereBetween($dateColumn,[$from,$to]);
        $schema=Schema::connection(config('egg.connection') ?: config('database.default'));
        if($location!=='all' && $location!==null && $location!=='' && $schema->hasColumn($table,'location_id'))$q->where('location_id',$location);
        if($store!=='all' && $store!==null && $store!=='' && $schema->hasColumn($table,'store_id'))$q->where('store_id',$store);
        return ['rows'=>$q->orderBy($dateColumn,'desc')->limit(5000)->get($columns),'from'=>$from,'to'=>$to,'locations'=>$directory->locations(),'stores'=>$directory->stores(),'selectedLocation'=>$location,'selectedStore'=>$store];
    }
}
