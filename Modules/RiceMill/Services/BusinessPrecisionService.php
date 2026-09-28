<?php

namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves display/input precision from the host ERP Business Settings.
 *
 * The active tenant database's `business` row is authoritative. Session values
 * are used only as a compatibility fallback when a host version does not expose
 * the precision columns, so every Rice Mill page follows Business Settings.
 */
class BusinessPrecisionService
{
    private array $memo = [];

    /** @return array{currency:int,quantity:int,currency_step:string,quantity_step:string} */
    public function forBusiness(int $businessId): array
    {
        if (isset($this->memo[$businessId])) {
            return $this->memo[$businessId];
        }

        // Business Settings in the active tenant DB is authoritative. Session
        // values are only a fallback for hosts that do not expose the columns.
        $currency = null;
        $quantity = null;
        if (Schema::hasTable('business')) {
            $columns = Schema::getColumnListing('business');
            $select = ['id'];
            if (in_array('currency_precision', $columns, true)) {
                $select[] = 'currency_precision';
            }
            if (in_array('quantity_precision', $columns, true)) {
                $select[] = 'quantity_precision';
            }

            if (count($select) > 1) {
                $row = DB::table('business')->where('id', $businessId)->first($select);
                if ($row) {
                    if (isset($row->currency_precision)) {
                        $currency = $this->normalise($row->currency_precision, null);
                    }
                    if (isset($row->quantity_precision)) {
                        $quantity = $this->normalise($row->quantity_precision, null);
                    }
                }
            }
        }

        $currency = $currency ?? $this->sessionPrecision('currency_precision');
        $quantity = $quantity ?? $this->sessionPrecision('quantity_precision');
        $currency = $currency ?? $this->normalise(config('ricemill.currency_decimals', 4), 4);
        $quantity = $quantity ?? $this->normalise(config('ricemill.quantity_decimals', 3), 3);

        return $this->memo[$businessId] = [
            'currency' => $currency,
            'quantity' => $quantity,
            'currency_step' => $this->step($currency),
            'quantity_step' => $this->step($quantity),
        ];
    }

    private function sessionPrecision(string $key): ?int
    {
        foreach (["business.{$key}", $key] as $sessionKey) {
            $value = session($sessionKey);
            $precision = $this->normalise($value, null);
            if ($precision !== null) {
                return $precision;
            }
        }

        return null;
    }

    private function normalise($value, ?int $fallback): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $fallback;
        }

        return max(0, min(8, (int) $value));
    }

    private function step(int $precision): string
    {
        return $precision <= 0 ? '1' : '0.' . str_repeat('0', $precision - 1) . '1';
    }
}
