<?php
namespace Modules\EggManagement\Integrations;
class SupplierGateway extends DirectoryGateway
{
    public function options($search = null, $limit = 100)
    {
        $table = config('egg.common.contacts_table', 'contacts');
        if (!$this->hasTable($table)) return collect();
        $q = $this->db()->table($table)->where('business_id',$this->context->businessId())->where('type',config('egg.common.supplier_type','supplier'));
        if ($search) $q->where(function($x) use ($search){ $x->where('name','like','%'.$search.'%')->orWhere('mobile','like','%'.$search.'%'); });
        return $q->orderBy('name')->limit($limit)->get(['id','name','mobile','email']);
    }
}
