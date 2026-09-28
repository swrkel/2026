<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define the new permissions
        $permissions = [
            'add_dividends',
            'edit_dividends'
        ];

        foreach ($permissions as $permission_name) {
            // Create the permission if it doesn't exist
            Permission::firstOrCreate(
                ['name' => $permission_name],
                ['guard_name' => 'web']
            );

            // Assign permission to all Admin roles (Admin#...)
            $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
            foreach ($adminRoles as $role) {
                if (!$role->hasPermissionTo($permission_name)) {
                    $role->givePermissionTo($permission_name);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'add_dividends',
            'edit_dividends'
        ];

        foreach ($permissions as $permission_name) {
            $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
            foreach ($adminRoles as $role) {
                $role->revokePermissionTo($permission_name);
            }
            
            // Optional: Delete permission from table
            // Permission::where('name', $permission_name)->delete();
        }
    }
};
