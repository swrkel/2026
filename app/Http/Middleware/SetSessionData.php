<?php

namespace App\Http\Middleware;

use App\Business;
use App\Utils\BusinessUtil;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SetSessionData
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('customer')->check() && optional(Auth::user())->is_company_customer == 0) {
            return $next($request);
        }

        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        /*
         |----------------------------------------------------------------------
         | LA-1152: the header showed a different user's name.
         |----------------------------------------------------------------------
         |
         | This decided whether to rebuild session('user') from three tests:
         | session has no 'user', the BUSINESS id differs, or currency is missing.
         | The one thing it never compared was the USER id.
         |
         | So a session still carrying another user of the SAME business passed
         | every test and was left untouched. auth()->user() was the new user
         | while session('user') was the previous one - and the header prefers
         | the session (see layouts/partials/header.blade.php, which reads
         | $erpHeaderSessionUser['first_name'] before falling back to auth()).
         | That is why logging in as Test3-12 showed "sonali1312" at the top.
         |
         | It is not only cosmetic. Everything reading session('user.id') -
         | including the location and permission lookups the video shows
         | misbehaving - was resolving against the wrong user.
         |
         | Comparing the user id closes it. The business and currency tests are
         | kept so nothing that already worked changes.
         */
        $sessionUserId = (int) $request->session()->get('user.id');
        $sessionBusinessId = (int) $request->session()->get('user.business_id');

        $mustInitialize = ! $request->session()->has('user')
            || $sessionUserId !== (int) $user->id
            || $sessionBusinessId !== (int) $user->business_id
            || ! $request->session()->has('currency');

        if ($mustInitialize) {
            $business = Business::query()->with('currency')->findOrFail($user->business_id);
            $currency = $business->currency;

            $sessionData = [
                'id' => $user->id,
                'username' => $user->username,
                'surname' => $user->surname,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'business_id' => $user->business_id,
                'language' => $user->language,
                'pump_operator_id' => $user->pump_operator_id,
                'is_pump_operator' => $user->is_pump_operator,
                'is_property_user' => $user->is_property_user,
            ];

            $currencySymbol = $currency->symbol;
            if ((empty($currencySymbol) || trim((string) $currencySymbol) === '$')
                && strtoupper((string) $currency->code) !== 'USD') {
                $currencySymbol = $currency->code;
            }

            $currencyData = [
                'id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currencySymbol,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
                'currency_symbol_placement' => $business->currency_symbol_placement ?? 'before',
            ];

            $request->session()->put('user', $sessionData);
            $request->session()->put('business', $business);
            $request->session()->put('currency', $currencyData);
            $request->session()->put('business_currency_symbol', $currencyData['symbol']);
            $request->session()->put('business_currency_code', $currencyData['code']);
            $request->session()->put('business_currency_symbol_placement', $currencyData['currency_symbol_placement']);

            $businessUtil = app(BusinessUtil::class);
            $request->session()->put('financial_year', $businessUtil->getCurrentFinancialYear($business->id));
        }

        $this->setDiskCapacityWarning($request, (int) $user->business_id);

        return $next($request);
    }

    private function setDiskCapacityWarning($request, int $businessId): void
    {
        // Database-size calculation is expensive. Check at most once every five minutes.
        $cacheKey = 'erp_perf:disk_capacity:' . sha1(request()->getHost() . '|' . $businessId);
        $warning = Cache::remember($cacheKey, now()->addMinutes(5), static function () use ($businessId) {
            $maxDiskSize = business_disk_limit_mb($businessId);
            if ($maxDiskSize <= 0) {
                return null;
            }

            $usedDiskSize = tenant_database_size_mb();
            $usagePercentage = round(($usedDiskSize / $maxDiskSize) * 100, 2);
            if ($usagePercentage < 80) {
                return null;
            }

            $threshold = $usagePercentage >= 90 ? 90 : 80;

            return [
                'level' => $usagePercentage >= 90 ? 'critical' : 'warning',
                'message' => "{$threshold}% of your allowed disk capacity is reached. Please get the disk capacity increased.",
                'usage_percentage' => round($usagePercentage, 1),
                'used_disk_size' => round($usedDiskSize, 2),
                'allowed_disk_size' => round($maxDiskSize, 2),
                'dismiss_key' => 'disk_capacity_warning_' . $businessId . '_' . now()->format('YmdHi'),
            ];
        });

        if ($warning) {
            $request->session()->put('disk_capacity_warning', $warning);
        } else {
            $request->session()->forget('disk_capacity_warning');
        }
    }
}
