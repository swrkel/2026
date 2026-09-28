<?php

namespace Modules\MyHealthMembers\Services\Admin;

use Illuminate\Support\Facades\DB;

class MyHealthPermissionMatrixService
{
    public function sections(): array
    {
        return [
            'member' => ['View Medical History', 'Create Member', 'Edit Member', 'Upload Documents'],
            'consultation' => ['Create Consultation', 'Edit Consultation', 'View Doctor Notes'],
            'lab' => ['Create Lab Request', 'View Lab Results', 'Upload Lab Result'],
            'pharmacy' => ['View Prescription', 'Dispense Medicines', 'View Stock'],
            'insurance' => ['View Policy', 'Create Claim', 'Approve Claim'],
            'billing' => ['Create Invoice', 'Receive Payment', 'View Revenue'],
            'reports' => ['View Reports', 'Export Reports', 'Print Reports'],
            'audit' => ['View Audit Trail', 'View Access Logs'],
        ];
    }

    public function businesses()
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('business')) {
                return DB::table('business')->select('id', 'name')->orderBy('name')->get();
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return collect();
    }

    public function save(array $payload): void
    {
        if (! DB::getSchemaBuilder()->hasTable('myhealth_business_permissions')) {
            return;
        }

        $businessId = $payload['business_id'] ?? null;
        $permissions = $payload['permissions'] ?? [];

        if (! $businessId) {
            return;
        }

        DB::table('myhealth_business_permissions')->where('business_id', $businessId)->delete();

        foreach ($permissions as $section => $items) {
            foreach ((array) $items as $permission) {
                DB::table('myhealth_business_permissions')->insert([
                    'business_id' => $businessId,
                    'section' => $section,
                    'permission_key' => $permission,
                    'can_view' => 1,
                    'can_create' => str_contains($permission, 'Create') || str_contains($permission, 'Dispense'),
                    'can_edit' => str_contains($permission, 'Edit') || str_contains($permission, 'Approve'),
                    'can_delete' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
