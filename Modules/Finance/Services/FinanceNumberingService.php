<?php

namespace Modules\Finance\Services;

use App\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * 8030: auto-incrementing reference numbers for Finance operations.
 *
 *   Cash Deposit       CAD
 *   Cheque to Realize  CHR
 *   Cheque Deposit     CHD
 *   Card Deposit       CDD
 *   Transfers          TFR
 *
 * Numbers start at 1 (or the starting number configured in Business Settings)
 * and are stored in account_transactions.finance_ref_no.
 *
 * The prefix and starting number come from the same business settings every
 * other prefix uses - ref_no_prefixes and ref_no_starting_number - so they are
 * edited in Settings > Business Settings > Prefixes with no special handling.
 */
class FinanceNumberingService
{
    /**
     * Operation key => default prefix.
     *
     * The key is what is stored in ref_no_prefixes, and must match the field
     * names added to settings_prefixes.blade.php.
     */
    public const OPERATIONS = [
        'finance_cash_deposit'   => 'CAD',
        'finance_cheque_realize' => 'CHR',
        'finance_cheque_deposit' => 'CHD',
        'finance_card_deposit'   => 'CDD',
        'finance_transfer'       => 'TFR',
    ];

    /**
     * The next reference for an operation, e.g. "CAD0001".
     *
     * Returns null - rather than throwing - if the column is missing or anything
     * goes wrong. A reference number must never prevent a deposit being saved.
     */
    public function next(string $operation, int $businessId): ?string
    {
        try {
            if (! isset(self::OPERATIONS[$operation])) {
                return null;
            }

            if (! Schema::hasTable('account_transactions')
                || ! Schema::hasColumn('account_transactions', 'finance_ref_no')) {
                return null;
            }

            $prefix = $this->prefixFor($operation, $businessId);
            $start  = $this->startingNumberFor($operation, $businessId);

            /*
             | The highest number ALREADY USED for this prefix.
             |
             | Derived from the stored references rather than a counter, so the
             | series cannot drift out of step with the data - and a row deleted
             | by hand does not cause the next save to reuse its number.
             |
             | Matched with a LIKE on the prefix, then the numeric tail is taken.
             | CAST is used so "CAD10" sorts above "CAD9", which a string sort
             | would get wrong.
             */
            $highest = DB::table('account_transactions')
                ->where('business_id', $businessId)
                ->where('finance_ref_no', 'LIKE', $prefix . '%')
                ->selectRaw('MAX(CAST(SUBSTRING(finance_ref_no, ?) AS UNSIGNED)) AS highest', [strlen($prefix) + 1])
                ->value('highest');

            $next = max((int) $highest + 1, $start);

            return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            \Log::warning('8030 finance numbering failed', [
                'operation'   => $operation,
                'business_id' => $businessId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * The configured prefix, falling back to the default for the operation.
     */
    public function prefixFor(string $operation, int $businessId): string
    {
        $default = self::OPERATIONS[$operation] ?? '';
        $prefixes = $this->businessSetting($businessId, 'ref_no_prefixes');
        $configured = trim((string) ($prefixes[$operation] ?? ''));

        return $configured !== '' ? $configured : $default;
    }

    /**
     * The configured starting number, at least 1.
     */
    public function startingNumberFor(string $operation, int $businessId): int
    {
        $starts = $this->businessSetting($businessId, 'ref_no_starting_number');
        $configured = (int) ($starts[$operation] ?? 0);

        return max($configured, 1);
    }

    /**
     * Is this prefix already used by a DIFFERENT operation or module?
     *
     * Requirement 4 of 8030: a prefix already in use anywhere must be rejected.
     * Compared case-insensitively and trimmed, because "cad" and "CAD " would
     * collide in practice while differing as strings.
     */
    public function prefixIsTaken(string $prefix, string $operation, int $businessId): bool
    {
        $prefix = strtoupper(trim($prefix));

        if ($prefix === '') {
            return false;
        }

        $prefixes = $this->businessSetting($businessId, 'ref_no_prefixes');

        foreach ($prefixes as $key => $value) {
            if ($key === $operation) {
                continue;
            }

            if (strtoupper(trim((string) $value)) === $prefix) {
                return true;
            }
        }

        return false;
    }

    /**
     * A business setting decoded to an array.
     *
     * ref_no_prefixes is cast to an array by the Business model on most installs
     * but arrives as a JSON string on some, so both are handled.
     */
    private function businessSetting(int $businessId, string $column): array
    {
        try {
            $value = Business::where('id', $businessId)->value($column);

            if (is_array($value)) {
                return $value;
            }

            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : [];
            }

            return [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
