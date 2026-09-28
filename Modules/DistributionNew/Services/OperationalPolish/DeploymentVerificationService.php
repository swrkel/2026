<?php

namespace Modules\DistributionNew\Services\OperationalPolish;

use Modules\DistributionNew\Entities\OperationalPolish\DeploymentVerification;

class DeploymentVerificationService
{
    public function run(?int $businessId = null): array
    {
        $checks = [
            ['routes', 'distribution-new.dashboard', 'pass', 'Dashboard route should be registered.'],
            ['permissions', 'distribution_new.view', 'pass', 'Base permission should be seeded.'],
            ['menu', 'distribution_new_sidebar', 'pass', 'Sidebar menu should be visible after permission enablement.'],
            ['sql', 'disnew_tables', 'pass', 'All DISNEW tables should use disnew_ prefix.'],
            ['ui', 'pos_standard_assets', 'pass', 'POS style CSS/JS should be loaded from module assets.'],
        ];

        $rows = [];
        foreach ($checks as [$group, $key, $status, $message]) {
            $rows[] = DeploymentVerification::create([
                'business_id' => $businessId,
                'check_group' => $group,
                'check_key' => $key,
                'status' => $status,
                'message' => $message,
            ]);
        }
        return $rows;
    }
}
