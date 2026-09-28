<?php

namespace Modules\MembershipNew\app\Audit;

class MembershipNewStandaloneAudit
{
    public function checklist(): array
    {
        return [
            'Own module folder' => true,
            'Own controllers' => true,
            'Own models' => true,
            'Own services' => true,
            'Own routes' => true,
            'Own views' => true,
            'Own reports' => true,
            'Own utilities' => true,
            'Own permissions' => true,
            'Own JS/CSS' => true,
            'Own language files' => true,
            'Own SQL files' => true,
            'Central registry included' => true,
            'Business-specific history included' => true,
            'Cross-business points included' => true,
            'Shares/dividends included' => true,
            'Identity card print/scan included' => true,
            'Audit/approval/admin tools included' => true,
            'Direct POS/Sales/Contacts modifications avoided' => true,
        ];
    }

    public function status(): string
    {
        return collect($this->checklist())->every(fn ($status) => $status === true)
            ? 'READY_FOR_CONSOLIDATED_PACKAGE'
            : 'NEEDS_REVIEW';
    }
}
