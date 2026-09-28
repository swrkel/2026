<?php

namespace Modules\LeadsNew\Exports;

use Modules\LeadsNew\Models\LeadsNewLead;

class LeadsNewLeadExport
{
    public function collection()
    {
        return LeadsNewLead::query()->latest('id')->get();
    }

    public function headings(): array
    {
        return ['Lead No', 'Name', 'Mobile', 'Email', 'Source', 'Status', 'Assigned To', 'Created At'];
    }
}
