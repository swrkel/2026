<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoginCodeService
{
    public function generate(int $businessId): string
    {
        for ($i=0; $i<200; $i++) {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            if (!DB::table('dlr_users')->where('business_id', $businessId)->where('login_code', $code)->exists()) return $code;
        }
        throw new RuntimeException('Unable to generate a unique 4 digit dealer login code for this business.');
    }

    public function validateAvailable(int $businessId, string $code, ?int $ignoreUserId = null): bool
    {
        if (!preg_match('/^\d{4}$/', $code)) return false;
        $q = DB::table('dlr_users')->where('business_id', $businessId)->where('login_code', $code);
        if ($ignoreUserId) $q->where('id', '<>', $ignoreUserId);
        return !$q->exists();
    }
}
