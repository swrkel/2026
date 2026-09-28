<?php
namespace Modules\EggManagement\Integrations;
class ProductGateway extends DirectoryGateway
{
    public function options($search = null, $limit = 100)
    {
        $table = config('egg.common.products_table', 'products');
        if (!$this->hasTable($table)) return collect();
        $q = $this->db()->table($table)->where('business_id',$this->context->businessId());
        if ($search) $q->where('name','like','%'.$search.'%');
        return $q->orderBy('name')->limit($limit)->get(['id','name']);
    }
}
