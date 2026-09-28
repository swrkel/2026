<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPortalBrandingToMyhealthBusinessPermissions extends Migration
{
    public function up(): void
    {
        $connection = config('myhealthmembers.central_connection');

        if (!Schema::connection($connection)->hasTable('myhealth_business_permissions')) {
            return;
        }

        Schema::connection($connection)->table('myhealth_business_permissions', function (Blueprint $table) use ($connection) {
            if (!Schema::connection($connection)->hasColumn('myhealth_business_permissions', 'portal_branding')) {
                $table->longText('portal_branding')->nullable()->after('access_expiry_date');
            }
        });
    }

    public function down(): void
    {
        $connection = config('myhealthmembers.central_connection');

        if (!Schema::connection($connection)->hasTable('myhealth_business_permissions')) {
            return;
        }

        Schema::connection($connection)->table('myhealth_business_permissions', function (Blueprint $table) use ($connection) {
            if (Schema::connection($connection)->hasColumn('myhealth_business_permissions', 'portal_branding')) {
                $table->dropColumn('portal_branding');
            }
        });
    }
}
