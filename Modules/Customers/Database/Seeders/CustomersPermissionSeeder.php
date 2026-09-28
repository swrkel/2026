<?php

namespace Modules\Customers\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CustomersPermissionSeeder extends Seeder
{
    /**
     * Create standalone Customers module permissions.
     *
     * These permissions do not remove or change existing Contacts permissions.
     * They allow the Customers module to progressively separate from Contacts.
     */
    public function run()
    {
        $permissions = [
            'customers.dashboard',
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
            'customers.import',
            'customers.export',
            'customers.notes.view',
            'customers.notes.create',
            'customers.notes.delete',
            'customers.attachments.view',
            'customers.attachments.create',
            'customers.attachments.delete',
            'customers.documents.view',
            'customers.documents.create',
            'customers.documents.delete',
            'customers.document_categories.view',
            'customers.document_categories.create',
            'customers.document_categories.delete',
            'customers.timeline.view',
            'customers.audit.view',
            'customers.reports.view',
            'customers.settings.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
