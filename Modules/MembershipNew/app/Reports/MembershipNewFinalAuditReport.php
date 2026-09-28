<?php

namespace Modules\MembershipNew\app\Reports;

class MembershipNewFinalAuditReport
{
    public function checklist(): array
    {
        return [
            'Standalone module folder exists',
            'Own route files exist',
            'Own controllers exist',
            'Own models exist',
            'Own services exist',
            'Own reports exist',
            'Own views exist',
            'Own JS/CSS exists',
            'Own language files exist',
            'Own permissions registry exists',
            'Own SQL files exist',
            'Central registry exists',
            'Business-specific history exists',
            'Points earn/redeem exists',
            'Shares/dividends exists',
            'Card print/scan/lifecycle exists',
            'Audit/approval exists',
            'No POS/Sales/Contacts files modified directly',
        ];
    }
}
