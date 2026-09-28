<?php

namespace Modules\SettlementSW\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * SW_SEP_003
 * Shared base service for Settlement SW service separation.
 *
 * This class is intentionally dependency-light and safe. It centralizes common
 * helpers without changing any existing controller behavior yet.
 */
abstract class SettlementSwBaseService
{
    protected function businessId(): ?int
    {
        return (int) (session('user.business_id') ?? optional(auth()->user())->business_id);
    }

    protected function userId(): ?int
    {
        return (int) (session('user.id') ?? optional(auth()->user())->id);
    }

    protected function money($amount): float
    {
        return round((float) ($amount ?? 0), 4);
    }

    protected function sumAmount($items, string $field = 'amount'): float
    {
        if (!$items instanceof Collection) {
            $items = collect($items ?: []);
        }

        return $this->money($items->sum(function ($item) use ($field) {
            return is_array($item) ? ($item[$field] ?? 0) : ($item->{$field} ?? 0);
        }));
    }

    protected function success(string $message, array $extra = []): array
    {
        return array_merge([
            'success' => true,
            'msg' => $message,
        ], $extra);
    }

    protected function failure(string $message, array $extra = []): array
    {
        return array_merge([
            'success' => false,
            'msg' => $message,
        ], $extra);
    }

    protected function logError(string $context, \Throwable $e, array $data = []): void
    {
        Log::error('[SettlementSW] ' . $context, array_merge([
            'business_id' => $this->businessId(),
            'user_id' => $this->userId(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], $data));
    }
}
