<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function connectionName(): string
    {
        return config('myhealthmembers.central_connection', config('database.default'));
    }

    public function up(): void
    {
        $connection = $this->connectionName();

        if (! Schema::connection($connection)->hasTable('myhealth_business_permissions')) {
            return;
        }

        Schema::connection($connection)->table('myhealth_business_permissions', function (Blueprint $table) use ($connection) {
            if (! Schema::connection($connection)->hasColumn('myhealth_business_permissions', 'portal_footer_text')) {
                $table->text('portal_footer_text')->nullable()->after('access_expiry_date');
            }
        });
    }

    public function down(): void
    {
        $connection = $this->connectionName();

        if (Schema::connection($connection)->hasTable('myhealth_business_permissions')
            && Schema::connection($connection)->hasColumn('myhealth_business_permissions', 'portal_footer_text')) {
            Schema::connection($connection)->table('myhealth_business_permissions', function (Blueprint $table) {
                $table->dropColumn('portal_footer_text');
            });
        }
    }
};
