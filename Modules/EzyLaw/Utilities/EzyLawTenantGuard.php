<?php
namespace Modules\EzyLaw\Utilities;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use RuntimeException;

class EzyLawTenantGuard
{
    public static function businessId(?int $requested = null): int
    {
        $sessionId = session('business.id') ?: session('user.business_id') ?: session('business_id');
        $userId = optional(auth()->user())->business_id;
        // The active session business wins in a multi-business login; user.business_id is only a fallback.
        $businessId = (int) ($sessionId ?: $userId ?: 0);
        if ($businessId <= 0) {
            throw new RuntimeException('EzyLaw could not resolve the active business.');
        }
        if ($requested !== null && (int)$requested !== $businessId) {
            throw new AccessDeniedHttpException('The requested business is not available in this session.');
        }
        return $businessId;
    }

    public static function ensureBusiness(Request $request): int
    {
        $requested = $request->input('business_id');
        $id = self::businessId($requested !== null ? (int)$requested : null);
        $request->merge(['business_id'=>$id]);
        return $id;
    }

    public static function userId(): ?int
    {
        return auth()->check() ? (int) auth()->id() : null;
    }

    public static function permittedLocationIds(): array
    {
        $user = auth()->user();
        if (!$user) return [];
        if (method_exists($user, 'permitted_locations')) {
            $ids = $user->permitted_locations();
            return $ids === 'all' ? ['all'] : array_values(array_filter((array)$ids, fn($v)=>$v!==null && $v!==''));
        }
        return ['all'];
    }
}
