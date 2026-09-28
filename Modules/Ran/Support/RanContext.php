<?php

namespace Modules\Ran\Support;

use Illuminate\Support\Facades\Auth;
use RuntimeException;

final class RanContext
{
    public static function businessId(): int
    {
        $id = self::businessIdOrNull();
        if (! $id) {
            throw new RuntimeException('Ran requires an active business context.');
        }
        return $id;
    }

    public static function businessIdOrNull(): ?int
    {
        $id = session('user.business_id') ?: session('business.id') ?: optional(Auth::user())->business_id;
        return $id ? (int) $id : null;
    }

    public static function userId(): ?int
    {
        return Auth::id() ?: (session('user.id') ? (int) session('user.id') : null);
    }

    public static function locationId(): ?int
    {
        $id = request('location_id') ?: session('business_location_id') ?: session('location_id');
        return $id ? (int) $id : null;
    }

    public static function storeId(): ?int
    {
        $id = request('store_id') ?: session('store_id') ?: session('business.default_store');
        return $id ? (int) $id : null;
    }

    public static function scope(array $overrides = []): array
    {
        return array_merge([
            'business_id' => self::businessId(),
            'location_id' => self::locationId(),
            'store_id' => self::storeId(),
        ], array_filter($overrides, static fn ($value) => $value !== null));
    }
}
