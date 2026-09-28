<?php

namespace Modules\StockTakingNew\Utils;

final class StockTakingStatus
{
    public const DRAFT = 'draft';
    public const PREPARED = 'prepared';
    public const COUNTING = 'counting';
    public const RECOUNT = 'recount';
    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const POSTED = 'posted';
    public const CANCELLED = 'cancelled';

    public static function all(): array
    {
        return [
            self::DRAFT, self::PREPARED, self::COUNTING, self::RECOUNT,
            self::SUBMITTED, self::APPROVED, self::REJECTED, self::POSTED, self::CANCELLED,
        ];
    }

    public static function label(?string $status): string
    {
        return match ($status) {
            self::RECOUNT => 'Recount Required',
            self::SUBMITTED => 'Awaiting Approval',
            default => ucwords(str_replace('_', ' ', (string) $status)),
        };
    }

    public static function cssClass(?string $status): string
    {
        return match ($status) {
            self::POSTED, self::APPROVED => 'success',
            self::COUNTING, self::PREPARED => 'info',
            self::RECOUNT, self::SUBMITTED => 'warning',
            self::REJECTED, self::CANCELLED => 'danger',
            default => 'secondary',
        };
    }
}
