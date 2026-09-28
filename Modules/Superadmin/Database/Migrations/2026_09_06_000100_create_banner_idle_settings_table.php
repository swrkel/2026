<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Superadmin\Services\CentralContext;

class CreateBannerIdleSettingsTable extends Migration
{
    public function up()
    {
        $connection = CentralContext::trueCentralConnectionName();
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('banner_idle_settings')) {
            $schema->create('banner_idle_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();
                $table->unsignedInteger('idle_minutes')->default(0);
                $table->boolean('all_tenants')->default(true);
                $table->text('tenant_ids')->nullable();
                $table->boolean('all_businesses')->default(true);
                $table->longText('business_targets')->nullable();
                $table->timestamps();
            });
        }

        $table = DB::connection($connection)->table('banner_idle_settings');
        if (! $table->where('id', 1)->exists()) {
            $table->insert([
                'id' => 1,
                'idle_minutes' => 0,
                'all_tenants' => 1,
                'tenant_ids' => null,
                'all_businesses' => 1,
                'business_targets' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    public function down()
    {
        $connection = CentralContext::trueCentralConnectionName();
        Schema::connection($connection)->dropIfExists('banner_idle_settings');
    }
}
