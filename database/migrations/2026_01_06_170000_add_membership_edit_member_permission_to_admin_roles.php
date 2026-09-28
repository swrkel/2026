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
        // Create the permission if it doesn't exist
        Permission::firstOrCreate(
            ['name' => 'membership.edit_member'],
            ['guard_name' => 'web']
        );

        // Assign permission to all Admin roles
        $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($adminRoles as $role) {
            if (!$role->hasPermissionTo('membership.edit_member')) {
                $role->givePermissionTo('membership.edit_member');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permission from Admin roles (optional - you may want to keep it)
        $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($adminRoles as $role) {
            $role->revokePermissionTo('membership.edit_member');
        }
    }
};

