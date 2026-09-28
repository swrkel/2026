<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class DealerContext
{
    private bool $userLoaded = false;
    private ?object $cachedUser = null;
    private ?array $cachedOutletIds = null;
    private ?array $cachedPermissions = null;

    public function user(): ?object
    {
        if ($this->userLoaded) return $this->cachedUser;
        $this->userLoaded = true;
        $id = session(config('dealermanagement.session_key', 'dealer_management_user_id'));
        if (!$id) return $this->cachedUser = null;
        return $this->cachedUser = DB::table('dlr_users')
            ->where('id', $id)->where('is_active', 1)->first();
    }

    public function businessId(): ?int { return $this->user() ? (int)$this->user()->business_id : null; }
    public function dealerId(): ?int { return $this->user() ? (int)$this->user()->dealer_id : null; }

    public function outletIds(): array
    {
        if ($this->cachedOutletIds !== null) return $this->cachedOutletIds;
        $user = $this->user();
        if (!$user) return $this->cachedOutletIds = [];
        if ((int)$user->is_dealer_admin === 1) {
            return $this->cachedOutletIds = DB::table('dlr_outlets')
                ->where('dealer_id', $user->dealer_id)->where('is_active', 1)
                ->pluck('id')->map(fn($v)=>(int)$v)->all();
        }
        return $this->cachedOutletIds = DB::table('dlr_user_outlets')
            ->where('dealer_user_id', $user->id)->pluck('outlet_id')
            ->map(fn($v)=>(int)$v)->all();
    }

    private function permissions(): array
    {
        if ($this->cachedPermissions !== null) return $this->cachedPermissions;
        $user = $this->user();
        if (!$user || !$user->role_id) return $this->cachedPermissions = [];
        return $this->cachedPermissions = DB::table('dlr_role_permissions')
            ->where('role_id', $user->role_id)->pluck('permission_key')->flip()->all();
    }

    public function can(string $permission): bool
    {
        $user = $this->user();
        if (!$user) return false;
        if ((int)$user->is_dealer_admin === 1) return true;
        return array_key_exists($permission, $this->permissions());
    }
}
