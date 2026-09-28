<?php
namespace Modules\EggManagement\Integrations;

class LocationStoreGateway extends DirectoryGateway
{
    public function locations()
    {
        $table = config('egg.common.locations_table', 'business_locations');
        if (!$this->hasTable($table)) return collect();
        return $this->db()->table($table)->where('business_id',$this->context->businessId())->orderBy('name')->get(['id','name']);
    }

    public function stores($locationId = null)
    {
        $table = config('egg.common.stores_table', 'stores');
        if (!$this->hasTable($table)) return collect();
        $q = $this->db()->table($table)->where('business_id',$this->context->businessId());
        if ($locationId && $this->hasColumn($table,'location_id')) $q->where('location_id',$locationId);
        return $q->orderBy('name')->get(['id','name']);
    }

    public function assertScope($locationId, $storeId)
    {
        $b=$this->context->businessId();
        if ($locationId) {
            $table=config('egg.common.locations_table','business_locations');
            if ($this->hasTable($table) && !$this->db()->table($table)->where('business_id',$b)->where('id',$locationId)->exists()) throw new \RuntimeException('Selected location does not belong to the active business.');
        }
        if ($storeId) {
            $table=config('egg.common.stores_table','stores');
            if ($this->hasTable($table) && !$this->db()->table($table)->where('business_id',$b)->where('id',$storeId)->exists()) throw new \RuntimeException('Selected store does not belong to the active business.');
        }
        return true;
    }

    protected function hasColumn($table,$column)
    {
        try { return \Illuminate\Support\Facades\Schema::connection(config('egg.connection') ?: config('database.default'))->hasColumn($table,$column); } catch (\Throwable $e) { return false; }
    }
}
