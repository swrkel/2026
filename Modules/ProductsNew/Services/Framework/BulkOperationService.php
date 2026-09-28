<?php
namespace Modules\ProductsNew\Services\Framework;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class BulkOperationService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}
    public function recent(){ return DB::table('products_new_bulk_operation_sessions')->where('business_id',$this->guard->businessId())->orderByDesc('id')->paginate(20); }
    public function preview(array $data): array { $count=DB::table('products')->where('business_id',$this->guard->businessId())->when(!empty($data['category_id']),fn($q)=>$q->where('category_id',$data['category_id']))->count(); return ['affected_count'=>$count,'operation'=>$data['operation']??'update','safe_to_commit'=>$count>0]; }
    public function commit(array $data): int { return (int) DB::table('products_new_bulk_operation_sessions')->insertGetId(['business_id'=>$this->guard->businessId(),'operation'=>$data['operation']??'update','criteria_json'=>json_encode($data['criteria']??[]),'changes_json'=>json_encode($data['changes']??[]),'status'=>'completed','affected_count'=>$data['affected_count']??0,'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]); }
}
