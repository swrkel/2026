<?php

namespace Modules\LeadsNew\Reports;

class LeadsNewRegressionChecklistReport
{
    public function rows(): array
    {
        return [
            ['area' => 'Dashboard', 'test' => 'Open dashboard and verify KPI counts load.'],
            ['area' => 'Lead CRUD', 'test' => 'Create, edit, view, archive, restore and delete a lead.'],
            ['area' => 'Followups', 'test' => 'Create due, upcoming and completed followups.'],
            ['area' => 'Documents', 'test' => 'Upload, preview and download supported files.'],
            ['area' => 'Reports', 'test' => 'Run reports with date range and exports.'],
            ['area' => 'Permissions', 'test' => 'Verify route access with and without permissions.'],
            ['area' => 'Tenant', 'test' => 'Confirm tenant A cannot see tenant B data.'],
        ];
    }
}
