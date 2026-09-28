<?php

namespace Modules\SW\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * A SW Shift: one location, one date, many operators.
 *
 * The lifecycle drives everything:
 *
 *   OPEN    accepts entries. Appears in the expense, customer payment and cash
 *           deposit dropdowns.
 *   CLOSED  accepts nothing further. Appears in the SW Settlement dropdown.
 *   SETTLED reconciled.
 *
 * Closing before settling is what makes Balance In Hand meaningful - it is a
 * figure at a moment, not a total that moves while someone reconciles it.
 */
class Shift extends SWModel
{
    use SoftDeletes;

    protected $table = 'sw_shifts';

    public const STATUS_OPEN    = 0;
    public const STATUS_CLOSED  = 1;
    public const STATUS_SETTLED = 2;
    public const STATUS_VOID    = 3;

    protected $casts = [
        'shift_date' => 'date',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function operators()
    {
        return $this->hasMany(ShiftOperator::class, 'sw_shift_id');
    }

    public function dailyCash()
    {
        return $this->hasMany(DailyCash::class, 'sw_shift_id');
    }

    public function dailyCreditSales()
    {
        return $this->hasMany(DailyCreditSale::class, 'sw_shift_id');
    }

    public function dailyCards()
    {
        return $this->hasMany(DailyCard::class, 'sw_shift_id');
    }

    public function settlement()
    {
        return $this->hasOne(Settlement::class, 'sw_shift_id');
    }

    /**
     * IS2245 status compatibility.
     *
     * Current SW databases use 0/1/2/3, but some existing tenant databases
     * pre-date that migration and still carry textual values such as
     * open/close/closed/settled. Casting "closed" to int returns 0, which made
     * a genuinely closed shift display as Open and leak into open-shift lists.
     */
    public static function normalizeStatusValue($status, $closedAt = null): int
    {
        $raw = strtolower(trim((string) $status));

        if (in_array($raw, ['2', 'settled', 'settle'], true)) {
            return self::STATUS_SETTLED;
        }

        if (in_array($raw, ['3', 'void', 'voided', 'cancelled', 'canceled'], true)) {
            return self::STATUS_VOID;
        }

        if (in_array($raw, ['1', 'closed', 'close'], true)) {
            return self::STATUS_CLOSED;
        }

        // closed_at is authoritative evidence that an apparent "open" row was
        // actually closed, including rows affected by legacy status coercion.
        if (! empty($closedAt)) {
            return self::STATUS_CLOSED;
        }

        return self::STATUS_OPEN;
    }

    /**
     * Store the semantic status in the format used by the live tenant schema.
     *
     * This matters for legacy ENUM columns: writing integer 1 to an ENUM can
     * select its first item ("open"), which is exactly the IS2245 symptom.
     */
    public static function storageStatusValue(string $semantic)
    {
        $semantic = strtolower(trim($semantic));
        $numeric = [
            'open' => self::STATUS_OPEN,
            'closed' => self::STATUS_CLOSED,
            'settled' => self::STATUS_SETTLED,
            'void' => self::STATUS_VOID,
        ][$semantic] ?? self::STATUS_OPEN;

        try {
            $column = DB::selectOne(
                "SELECT COLUMN_TYPE AS column_type
                   FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'sw_shifts'
                    AND COLUMN_NAME = 'status'
                  LIMIT 1"
            );

            $type = strtolower((string) ($column->column_type ?? ''));
            if (str_starts_with($type, 'enum(')) {
                preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);
                $allowed = array_map(
                    static fn ($value) => strtolower(stripcslashes($value)),
                    $matches[1] ?? []
                );

                $candidates = [
                    'open' => ['open', 'opened', '0'],
                    'closed' => ['closed', 'close', '1'],
                    'settled' => ['settled', 'settle', '2'],
                    'void' => ['void', 'voided', '3'],
                ][$semantic] ?? [];

                foreach ($candidates as $candidate) {
                    $index = array_search($candidate, $allowed, true);
                    if ($index !== false) {
                        return $matches[1][$index];
                    }
                }
            }

            if (str_contains($type, 'char') || str_contains($type, 'text')) {
                // Legacy text schemas should keep semantic text. All SW status
                // readers below accept both textual and numeric representations.
                return $semantic;
            }
        } catch (\Throwable $e) {
            // Fall through to the current tinyint representation.
        }

        return $numeric;
    }

    public function isOpen(): bool
    {
        return self::normalizeStatusValue($this->status, $this->closed_at) === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return self::normalizeStatusValue($this->status, $this->closed_at) === self::STATUS_CLOSED;
    }

    public function isSettled(): bool
    {
        return self::normalizeStatusValue($this->status, $this->closed_at) === self::STATUS_SETTLED;
    }

    public function statusLabel(): string
    {
        return [
            self::STATUS_OPEN    => 'Open',
            self::STATUS_CLOSED  => 'Closed',
            self::STATUS_SETTLED => 'Settled',
            self::STATUS_VOID    => 'Void',
        ][self::normalizeStatusValue($this->status, $this->closed_at)] ?? 'Unknown';
    }

    /** Shifts still accepting entries - expenses, payments, deposits. */
    public function scopeOpenAt($query, int $businessId, int $locationId)
    {
        return $query->where('business_id', $businessId)
                     ->where('location_id', $locationId)
                     ->whereRaw("LOWER(TRIM(CAST(status AS CHAR))) IN ('0', 'open', 'opened')")
                     ->whereNull('closed_at')
                     ->orderByDesc('shift_date');
    }

    /** Shifts ready to settle. Nothing can be attributed to these any more. */
    public function scopeClosedAt($query, int $businessId, int $locationId)
    {
        return $query->where('business_id', $businessId)
                     ->where('location_id', $locationId)
                     ->where(function ($q) {
                         $q->whereRaw("LOWER(TRIM(CAST(status AS CHAR))) IN ('1', 'closed', 'close')")
                           ->orWhere(function ($legacy) {
                               $legacy->whereRaw("LOWER(TRIM(CAST(status AS CHAR))) IN ('0', 'open', 'opened')")
                                      ->whereNotNull('closed_at');
                           });
                     })
                     ->orderByDesc('shift_date');
    }

    /**
     * Open shifts a particular operator is assigned to.
     * The daily entry screens ask for an operator first, then their shifts.
     */
    public function scopeOpenForOperator($query, int $businessId, int $locationId, int $operatorId)
    {
        return $query->openAt($businessId, $locationId)
                     ->whereHas('operators', function ($q) use ($operatorId) {
                         $q->where('pump_operator_id', $operatorId);
                     });
    }
}
