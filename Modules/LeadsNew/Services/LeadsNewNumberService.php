<?php

namespace Modules\LeadsNew\Services;

use Modules\LeadsNew\Models\LeadsNewLead;
use Modules\LeadsNew\Services\LeadsNewTableGuard;

class LeadsNewNumberService
{
    protected $tableGuard;

    public function __construct(LeadsNewTableGuard $tableGuard)
    {
        $this->tableGuard = $tableGuard;
    }
    public function next(): string
    {
        if (! $this->tableGuard->exists('leads_new_leads')) {
            return 'LN-000001';
        }

        $lastId = (int) LeadsNewLead::withTrashed()->max('id');
        return 'LN-' . str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }
}
