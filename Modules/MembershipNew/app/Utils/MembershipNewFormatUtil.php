<?php

namespace Modules\MembershipNew\app\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Support\MembershipNewContext;

class MembershipNewFormatUtil
{
    private static array $userNameCache = [];
    private static array $precisionCache = [];

    public static function currencyPrecision(): int
    {
        $sessionPrecision = session('business.currency_precision');
        if ($sessionPrecision !== null && $sessionPrecision !== '' && is_numeric($sessionPrecision)) {
            return max(0, min(8, (int) $sessionPrecision));
        }

        $businessId = MembershipNewContext::businessIdOrNull();
        $connection = MembershipNewContext::connectionName();
        $cacheKey = $connection . ':' . (string) ($businessId ?: 0);

        if (array_key_exists($cacheKey, self::$precisionCache)) {
            return self::$precisionCache[$cacheKey];
        }

        if ($businessId) {
            try {
                $precision = DB::connection($connection)
                    ->table('business')
                    ->where('id', $businessId)
                    ->value('currency_precision');

                if ($precision !== null && $precision !== '' && is_numeric($precision)) {
                    return self::$precisionCache[$cacheKey] = max(0, min(8, (int) $precision));
                }
            } catch (\Throwable $e) {
                // The current tenancy connection may not expose the business table.
                // Fall back to the ERP default below rather than breaking the page.
            }
        }

        return self::$precisionCache[$cacheKey] = max(0, min(8, (int) config('constants.currency_precision', 2)));
    }

    public static function money($value): string
    {
        return number_format((float) $value, self::currencyPrecision(), '.', ',');
    }

    public static function moneyStep(): string
    {
        $precision = self::currencyPrecision();
        return $precision <= 0 ? '1' : '0.' . str_repeat('0', $precision - 1) . '1';
    }

    public static function points($value): string
    {
        return number_format((float) $value, (int) config('membershipnew.point_decimal_places', 4), '.', ',');
    }

    /**
     * Display Membership New date values with time. Date-only business fields
     * use the row creation time as their added-time component when available.
     */
    public static function dateTime($value, $createdAt = null): string
    {
        if (empty($value) && empty($createdAt)) {
            return '-';
        }

        try {
            $date = $value instanceof Carbon ? $value->copy() : Carbon::parse($value ?: $createdAt);
            if (!empty($createdAt)) {
                $created = $createdAt instanceof Carbon ? $createdAt : Carbon::parse($createdAt);
                $raw = is_string($value) ? trim($value) : '';
                $dateLooksDateOnly = $raw !== '' && strlen($raw) <= 10;
                $dateHasNoTime = $date->format('H:i:s') === '00:00:00';

                if ($dateLooksDateOnly || $dateHasNoTime) {
                    $date->setTime($created->hour, $created->minute, $created->second);
                }
            }

            return $date->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) ($value ?: $createdAt ?: '-');
        }
    }

    /**
     * Return the actual name of the user who added the record. No email,
     * username, numeric ID, or generic fallback is exposed.
     */
    public static function addedBy($record): string
    {
        if (!$record) {
            return '';
        }

        $creatorFields = [
            'created_by',
            'requested_by',
            'user_id',
        ];

        foreach ($creatorFields as $field) {
            try {
                $value = is_array($record) ? ($record[$field] ?? null) : ($record->{$field} ?? null);
            } catch (\Throwable $e) {
                $value = null;
            }

            if (!empty($value)) {
                return self::userNameById((int) $value);
            }
        }

        return '';
    }

    public static function userNameById(?int $userId): string
    {
        if (empty($userId)) {
            return '';
        }

        if (array_key_exists($userId, self::$userNameCache)) {
            return self::$userNameCache[$userId];
        }

        $authUser = Auth::user();
        if (!$authUser) {
            return self::$userNameCache[$userId] = '';
        }

        try {
            $userClass = get_class($authUser);
            $user = $userClass::query()->find($userId);
            if (!$user) {
                return self::$userNameCache[$userId] = '';
            }

            $parts = array_filter([
                $user->surname ?? null,
                $user->first_name ?? null,
                $user->last_name ?? null,
            ], static fn ($value) => $value !== null && trim((string) $value) !== '');

            $name = trim(implode(' ', $parts));
            if ($name === '' && isset($user->name) && trim((string) $user->name) !== '') {
                $name = trim((string) $user->name);
            }

            return self::$userNameCache[$userId] = $name;
        } catch (\Throwable $e) {
            return self::$userNameCache[$userId] = '';
        }
    }
}
