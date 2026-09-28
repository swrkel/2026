<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Superadmin\Services\CentralContext;

class AddIdleSecondsToBannerIdleSettingsTable extends Migration
{
    public function up()
    {
        $connection = CentralContext::trueCentralConnectionName();
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('banner_idle_settings')) {
            return;
        }

        if (! $schema->hasColumn('banner_idle_settings', 'idle_seconds')) {
            $schema->table('banner_idle_settings', function (Blueprint $table) {
                $table->unsignedInteger('idle_seconds')->default(0)->after('idle_minutes');
            });

            // Preserve the previous setting: e.g. 1 minute becomes 60 seconds.
            DB::connection($connection)
                ->table('banner_idle_settings')
                ->where('idle_minutes', '>', 0)
                ->update([
                    'idle_seconds' => DB::raw('idle_minutes * 60'),
                ]);
        }
    }

    public function down()
    {
        $connection = CentralContext::trueCentralConnectionName();
        $schema = Schema::connection($connection);

        if ($schema->hasTable('banner_idle_settings') && $schema->hasColumn('banner_idle_settings', 'idle_seconds')) {
            $schema->table('banner_idle_settings', function (Blueprint $table) {
                $table->dropColumn('idle_seconds');
            });
        }
    }
}
