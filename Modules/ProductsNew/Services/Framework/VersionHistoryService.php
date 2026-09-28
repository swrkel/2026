<?php
namespace Modules\ProductsNew\Services\Framework;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class VersionHistoryService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}
    public function list(array $filters){ $q=DB::table('products_new_version_history as v')->leftJoin('products as p','v.product_id','=','p.id')->select('v.*','p.name as product_name','p.sku'); $this->guard->applyBusiness($q,'v.business_id'); return $q->orderByDesc('v.id')->paginate(50); }
    public function find(int $id){ return (array) DB::table('products_new_version_history')->where('id',$id)->first(); }
}
