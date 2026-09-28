<?php

namespace Modules\LeadsNew\Imports;

use Modules\LeadsNew\Models\LeadsNewLead;

class LeadsNewLeadImport
{
    public function row(array $row): ?LeadsNewLead
    {
        if (empty($row['name']) && empty($row['mobile'])) {
            return null;
        }

        return LeadsNewLead::create([
            'name' => $row['name'] ?? null,
            'mobile' => $row['mobile'] ?? null,
            'email' => $row['email'] ?? null,
            'source' => $row['source'] ?? null,
            'status' => $row['status'] ?? 'new',
        ]);
    }
}
