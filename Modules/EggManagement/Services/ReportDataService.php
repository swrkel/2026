<?php
namespace Modules\EggManagement\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ReportDataService
{
    protected $map = [
        'production'=>['egg_collections','collection_date',['Collection'=>'collection_no','Date'=>'collection_date','Total'=>'total_pieces','Good'=>'good_pieces','Broken'=>'broken_pieces','Dirty'=>'dirty_pieces','Rejected'=>'rejected_pieces','Status'=>'status']],
        'stock'=>['egg_stock_lots','collection_date',['Lot'=>'lot_no','Collection Date'=>'collection_date','Grade'=>'grade_id','Received'=>'received_pieces','Available'=>'available_pieces','Unit Cost'=>'unit_cost','Status'=>'status']],
        'sales'=>['egg_sales','sale_date',['Sale No'=>'sale_no','Date'=>'sale_date','Customer'=>'customer_id','Subtotal'=>'subtotal','Discount'=>'discount','Total'=>'total','Payment'=>'payment_status']],
        'purchases'=>['egg_purchases','purchase_date',['Purchase No'=>'purchase_no','Date'=>'purchase_date','Supplier'=>'supplier_id','Subtotal'=>'subtotal','Discount'=>'discount','Total'=>'total','Payment'=>'payment_status']],
        'movements'=>['egg_stock_movements','movement_date',['Date'=>'movement_date','Direction'=>'direction','Grade'=>'grade_id','Pieces'=>'pieces','Unit Cost'=>'unit_cost','Source'=>'source_type','Source ID'=>'source_id']],
        'wastage'=>['egg_collections','collection_date',['Collection'=>'collection_no','Date'=>'collection_date','Broken'=>'broken_pieces','Dirty'=>'dirty_pieces','Rejected'=>'rejected_pieces']],
        'audit'=>['egg_audit_logs','created_at',['Date'=>'created_at','User'=>'user_id','Action'=>'action','Type'=>'auditable_type','ID'=>'auditable_id','IP'=>'ip_address']],
    ];

    public function forShare($businessId,$type,array $parameters=[])
    {
        if (!isset($this->map[$type])) throw new \InvalidArgumentException('Unsupported Egg report type.');
        [$table,$date,$columns]=$this->map[$type];
        $from=isset($parameters['from']) ? Carbon::parse($parameters['from'])->startOfDay() : now()->startOfMonth();
        $to=isset($parameters['to']) ? Carbon::parse($parameters['to'])->endOfDay() : now()->endOfDay();
        $q=DB::connection(config('egg.connection'))->table($table)->where('business_id',$businessId)->whereNull('deleted_at')->whereBetween($date,[$from,$to]); $schema=Schema::connection(config('egg.connection') ?: config('database.default')); $location=$parameters['location_id']??'all';$store=$parameters['store_id']??'all';if($location!=='all'&&$location!==null&&$location!==''&&$schema->hasColumn($table,'location_id'))$q->where('location_id',$location);if($store!=='all'&&$store!==null&&$store!==''&&$schema->hasColumn($table,'store_id'))$q->where('store_id',$store);
        $rows=$q->orderBy($date,'desc')->limit(5000)->get(array_values($columns));
        return ['headers'=>array_keys($columns),'columns'=>array_values($columns),'rows'=>$rows,'from'=>$from,'to'=>$to];
    }
}
