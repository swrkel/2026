<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ExpensesNew\Entities\Setting;

/**
 * MA-002 (S-612): the next expense category code.
 *
 * The Settings page holds two values:
 *
 *     category_code_prefix    e.g. "EXP-"
 *     category_code_start     e.g. 1000
 *
 * and this works out what the next category's code should be. The Add
 * Category form calls it to prefill the field.
 *
 * WHY IT READS THE EXISTING CODES RATHER THAN KEEPING A COUNTER
 *
 * A stored "next number" drifts. Someone deletes a category, someone types a
 * code by hand, someone imports a batch - and the counter no longer matches
 * reality, so it starts handing out numbers that are already taken. Reading
 * the highest code actually in use cannot drift, because it IS reality.
 *
 * The starting number is used only when no category carries the prefix yet.
 */
class CategoryCodeGenerator
{
    public const PREFIX_KEY = 'category_code_prefix';
    public const START_KEY = 'category_code_start';

    /**
     * The prefix configured for this business, or an empty string.
     */
    public function prefix(int $businessId): string
    {
        return (string) $this->setting($businessId, self::PREFIX_KEY, '');
    }

    /**
     * The configured starting number, never below 1.
     */
    public function startNumber(int $businessId): int
    {
        $value = $this->setting($businessId, self::START_KEY, 1);

        return max(1, (int) $value);
    }

    /**
     * The code the next category should be given.
     *
     * Returns the prefix followed by a number, padded to the same width as the
     * starting number so 1000 gives EXP-1000 and 0001 gives EXP-0001.
     */
    public function next(int $businessId): string
    {
        $prefix = $this->prefix($businessId);
        $start = $this->startNumber($businessId);

        $highest = $this->highestUsedNumber($businessId, $prefix);

        $number = $highest === null ? $start : max($start, $highest + 1);

        $rawStart = (string) $this->setting($businessId, self::START_KEY, '');
        $width = strlen(preg_replace('/\D/', '', $rawStart) ?: '');
        $width = $width > 0 ? $width : strlen((string) $start);

        return $prefix . str_pad((string) $number, $width, '0', STR_PAD_LEFT);
    }

    /**
     * The largest number already used with this prefix, or null if none.
     */
    protected function highestUsedNumber(int $businessId, string $prefix): ?int
    {
        if (! Schema::hasTable('expnew_categories')
            || ! Schema::hasColumn('expnew_categories', 'code')) {
            return null;
        }

        $query = DB::table('expnew_categories')->where('business_id', $businessId);

        if ($prefix !== '') {
            // Escape the prefix so a code containing % or _ cannot widen the match.
            $escaped = addcslashes($prefix, '%_\\');
            $query->where('code', 'like', $escaped . '%');
        }

        $codes = $query->pluck('code');

        $highest = null;

        foreach ($codes as $code) {
            $code = (string) $code;

            if ($prefix !== '') {
                if (! str_starts_with($code, $prefix)) {
                    continue;
                }
                $tail = substr($code, strlen($prefix));
            } else {
                $tail = $code;
            }

            // Only a purely numeric tail counts. A hand-typed code like
            // "EXP-FUEL" must not be read as a number and must not block the
            // sequence.
            if ($tail === '' || ! ctype_digit($tail)) {
                continue;
            }

            $value = (int) $tail;

            if ($highest === null || $value > $highest) {
                $highest = $value;
            }
        }

        return $highest;
    }

    /**
     * Read one setting for a business.
     */
    protected function setting(int $businessId, string $key, $default)
    {
        if (! Schema::hasTable('expnew_settings')) {
            return $default;
        }

        $value = Setting::query()
            ->where('business_id', $businessId)
            ->where('key', $key)
            ->value('value');

        return ($value === null || $value === '') ? $default : $value;
    }
}
