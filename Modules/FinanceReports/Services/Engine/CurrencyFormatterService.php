<?php

namespace Modules\FinanceReports\Services\Engine;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CurrencyFormatterService
{
    public function precision(int $business_id): int
    {
        if (!Schema::hasTable('business')) {
            return 4;
        }

        $settings = DB::table('business')->where('id', $business_id)->value('accounting_method');
        return 4; // Finance Reports standard: preserve 4 decimals unless business settings are extended later.
    }

    public function amount($value, int $business_id = 0): string
    {
        return number_format((float) $value, $this->precision($business_id));
    }
}
