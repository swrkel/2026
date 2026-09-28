<?php
namespace Modules\TeaEstateManagement\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocationAccessService
{
    public function __construct(private TenantContextService $context) {}

    public function options(): array
    {
        $businessId = $this->context->businessId();
        if (!Schema::hasTable('business_locations')) return [];
        $user = Auth::user();
        $query = DB::table('business_locations')->where('business_id', $businessId);
        if (Schema::hasColumn('business_locations','is_active')) $query->where('is_active',1);
        $all = $query->orderBy('name')->pluck('name','id')->mapWithKeys(fn($v,$k)=>[(int)$k=>$v])->all();
        if (!$user) return [];
        if ($this->isBusinessAdmin($user, $businessId) || $user->can('access_all_locations')) return $all;
        if (count($all) === 1) return $all;
        $allowed=[];
        foreach (array_keys($all) as $id) {
            if ($user->can('location.'.$id) || $user->can('location_'.$id)) $allowed[$id]=$all[$id];
        }
        return $allowed;
    }

    public function resolveRequired($value): int
    {
        $options=$this->options();
        abort_if(empty($options),403,'No business location is assigned to this user.');
        if (count($options)===1) return (int) array_key_first($options);
        $id=(int)$value;
        abort_if($id<=0 || !array_key_exists($id,$options),422,'Please select an authorised Location.');
        return $id;
    }

    public function defaultId(): ?int
    {
        $options=$this->options();
        return count($options)===1 ? (int)array_key_first($options) : null;
    }

    public function allowedIds(): array
    {
        return array_map('intval', array_keys($this->options()));
    }

    public function scope($query, string $column='location_id')
    {
        $ids=$this->allowedIds();
        abort_if(empty($ids),403,'No business location is assigned to this user.');
        return $query->whereIn($column,$ids);
    }

    private function isBusinessAdmin($user, int $businessId): bool
    {
        try {
            if (method_exists($user,'hasRole') && $user->hasRole('Admin#'.$businessId)) return true;
            if (Schema::hasTable('business')) {
                $ownerId=(int)DB::table('business')->where('id',$businessId)->value('owner_id');
                if ($ownerId>0 && (int)$user->id===$ownerId) return true;
            }
        } catch (\Throwable $e) {}
        return false;
    }
}
