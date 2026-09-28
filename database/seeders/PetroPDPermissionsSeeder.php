<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PetroPDPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'petro_pd.access',
            'petro_pd.view_operators',
            'petro_pd.view_report',
            'petro_pd.list_settlement',
            'petro_pd.create_settlement',
            'petro_pd.edit_settlement',
            'petro_pd.delete_settlement',
            'petro_pd.manual_entry',
            'petro_pd.meter_sale_tab',
            'petro_pd.other_sale_tab',
            'petro_pd.other_income_tab',
            'petro_pd.customer_payment_tab',
            'petro_pd.payment_tab',
            'petro_pd_sms_notifications',
            'petro_pd_whatsapp',
            'petro_pd_daily_collection.edit',
            'petro_pd_daily_collection.delete'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $role_name = 'Admin#3';
        $role = Role::where('name', $role_name)->first();

        if ($role) {
            $role->givePermissionTo($permissions);
            $this->command->info("Permissions assigned to role: $role_name");
        } else {
            $this->command->error("Role $role_name not found.");
        }
    }
}
