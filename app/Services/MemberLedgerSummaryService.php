<?php

namespace App\Services;

class MemberLedgerSummaryService
{
    public function getLedgerSummary($member_id, $business_id, $start_date, $end_date)
    {
        // Dummy implementation to prevent fatal errors
        return [
            'total_debit' => 0,
            'total_credit' => 0,
            'balance' => 0
        ];
    }
}
