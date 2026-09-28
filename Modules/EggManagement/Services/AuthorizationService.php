<?php
namespace Modules\EggManagement\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthorizationService
{
    protected $context;
    public function __construct(EggContext $context) { $this->context = $context; }

    public function allows($permission)
    {
        $user = Auth::user();
        if (!$user) return false;

        if (config('egg.permissions.host_can_first', true) && method_exists($user, 'can')) {
            try { if ($user->can($permission)) return true; } catch (\Throwable $e) { }
        }

        if (!config('egg.permissions.module_grants_fallback', true)) return false;
        $conn = config('egg.connection');
        $schema = Schema::connection($conn ?: config('database.default'));
        if (!$schema->hasTable('egg_access_grants')) return false;

        return DB::connection($conn)->table('egg_access_grants')
            ->where('business_id', $this->context->businessId())
            ->where('user_id', $user->getAuthIdentifier())
            ->where('permission', $permission)
            ->where('allowed', 1)
            ->exists();
    }
}
