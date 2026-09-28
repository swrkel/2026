<?php
namespace Modules\ProductsNew\Services\Framework;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class SavedFilterService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}
    public function list(){ return DB::table('products_new_saved_filters')->where('business_id',$this->guard->businessId())->where(function($q){$q->where('user_id',auth()->id())->orWhere('is_shared',1);})->orderBy('name')->paginate(25); }
    public function store(array $data): int { return (int) DB::table('products_new_saved_filters')->insertGetId(['business_id'=>$this->guard->businessId(),'user_id'=>auth()->id(),'name'=>$data['name']??'Saved filter','filter_json'=>json_encode($data['filters']??[]),'is_shared'=>!empty($data['is_shared']),'created_at'=>now(),'updated_at'=>now()]); }
}
