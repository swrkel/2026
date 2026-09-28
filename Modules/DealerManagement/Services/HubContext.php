<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class HubContext
{
    private $user;
    private $dealer;
    private $outletIds;

    public function user()
    {
        if ($this->user !== null) return $this->user;
        $id = (int) session(config('dealermanagement.hub_session_key','dealer_hub_user_id'), 0);
        if ($id <= 0) return null;
        $this->user = app(HubDatabaseManager::class)->central(fn() => DB::table('dlr_hub_users')->where('id',$id)->where('is_active',1)->first());
        return $this->user;
    }

    public function dealer()
    {
        if ($this->dealer !== null) return $this->dealer;
        $u = $this->user();
        if (!$u) return null;
        $this->dealer = app(HubDatabaseManager::class)->central(fn() => DB::table('dlr_hub_dealers')->where('id',$u->hub_dealer_id)->where('status','active')->first());
        return $this->dealer;
    }

    public function outletIds(): array
    {
        if ($this->outletIds !== null) return $this->outletIds;
        $u = $this->user();
        if (!$u) return [];
        $this->outletIds = app(HubDatabaseManager::class)->central(function() use($u) {
            $ids = DB::table('dlr_hub_user_outlets')->where('hub_user_id',$u->id)->pluck('hub_outlet_id')->map(fn($x)=>(int)$x)->all();
            if (!$ids && $u->is_hub_admin) $ids = DB::table('dlr_hub_outlets')->where('hub_dealer_id',$u->hub_dealer_id)->where('is_active',1)->pluck('id')->map(fn($x)=>(int)$x)->all();
            return $ids;
        });
        return $this->outletIds;
    }

    public function can(string $permission): bool
    {
        $u=$this->user(); if(!$u) return false; if($u->is_hub_admin) return true;
        $p=json_decode($u->permissions_json ?: '[]',true) ?: [];
        return in_array('*',$p,true) || in_array($permission,$p,true);
    }
}
