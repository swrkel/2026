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
     * Adds eight Membership section permissions and migrates membership.edit_member to edit_member.
     */
    public function up(): void
    {
        $permissions = [
            'add_membership_settings',
            'edit_membership_settings',
            'add_member',
            'edit_member',
            'add_dividends',
            'edit_dividends',
            'approved_by',
            'checked_by',
        ];

        foreach ($permissions as $permission_name) {
            Permission::firstOrCreate(
                ['name' => $permission_name],
                ['guard_name' => 'web']
            );
        }

        // Migrate roles that have membership.edit_member to also have edit_member
        $oldPerm = Permission::where('name', 'membership.edit_member')->where('guard_name', 'web')->first();
        if ($oldPerm) {
            $roleIds = \DB::table('role_has_permissions')->where('permission_id', $oldPerm->id)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                $role = Role::find($roleId);
                if ($role && !$role->hasPermissionTo('edit_member')) {
                    $role->givePermissionTo('edit_member');
                }
            }
        }

        // Assign all 8 permissions to Admin roles so existing admins keep access
        $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($adminRoles as $role) {
            foreach ($permissions as $permission_name) {
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
            'add_membership_settings',
            'edit_membership_settings',
            'add_member',
            'edit_member',
            'approved_by',
            'checked_by',
        ];

        $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($adminRoles as $role) {
            foreach ($permissions as $permission_name) {
                if ($role->hasPermissionTo($permission_name)) {
                    $role->revokePermissionTo($permission_name);
                }
            }
        }

        foreach ($permissions as $permission_name) {
            Permission::where('name', $permission_name)->where('guard_name', 'web')->delete();
        }
    }
};
