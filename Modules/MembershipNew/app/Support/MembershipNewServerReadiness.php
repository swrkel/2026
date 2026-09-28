<?php

namespace Modules\MembershipNew\app\Support;

use Modules\MembershipNew\app\Manifest\MembershipNewManifest;

class MembershipNewServerReadiness
{
    public function checklist(): array
    {
        return [
            ['item' => 'Module folder uploaded', 'status' => 'manual_check'],
            ['item' => 'Module provider registered', 'status' => 'manual_check'],
            ['item' => 'Route files configured', 'status' => count(MembershipNewManifest::routeFiles()) > 0 ? 'ready' : 'missing'],
            ['item' => 'SQL files run in order', 'status' => 'manual_check'],
            ['item' => 'Permissions assigned to role', 'status' => 'manual_check'],
            ['item' => 'Public assets published/copied', 'status' => 'manual_check'],
            ['item' => 'Health check page opened', 'status' => 'manual_check'],
            ['item' => 'Demo page opened', 'status' => 'manual_check'],
            ['item' => 'No POS/Sales/Contacts direct changes', 'status' => 'ready'],
        ];
    }
}
