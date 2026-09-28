<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = \Illuminate\Support\Facades\DB::table('users')->get();
        foreach ($users as $user) {
            $userRoles = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', 'App\User')
                ->get();

            foreach ($userRoles as $userRole) {
                $role = \Illuminate\Support\Facades\DB::table('roles')
                    ->where('id', $userRole->role_id)
                    ->first();

                if ($role && $user->business_id != $role->business_id) {
                    $baseName = explode('#', $role->name)[0];
                    $correctRoleName = $baseName . '#' . $user->business_id;

                    $correctRole = \Illuminate\Support\Facades\DB::table('roles')
                        ->where('business_id', $user->business_id)
                        ->where('name', $correctRoleName)
                        ->first();

                    if ($correctRole) {
                        \Illuminate\Support\Facades\DB::table('model_has_roles')
                            ->where('model_id', $user->id)
                            ->where('role_id', $role->id)
                            ->update(['role_id' => $correctRole->id]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback action needed for data repair
    }
};
