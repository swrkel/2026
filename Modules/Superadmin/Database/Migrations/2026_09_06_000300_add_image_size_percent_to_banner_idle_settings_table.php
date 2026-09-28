<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Superadmin\Services\CentralContext;

class AddImageSizePercentToBannerIdleSettingsTable extends Migration
{
    public function up()
    {
        $connection = CentralContext::trueCentralConnectionName();
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('banner_idle_settings')) {
            return;
        }

        if (! $schema->hasColumn('banner_idle_settings', 'image_size_percent')) {
            $schema->table('banner_idle_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('image_size_percent')->default(90)->after('idle_seconds');
            });

            DB::connection($connection)
                ->table('banner_idle_settings')
                ->where(function ($query) {
                    $query->whereNull('image_size_percent')
                        ->orWhere('image_size_percent', '<', 25)
                        ->orWhere('image_size_percent', '>', 100);
                })
                ->update(['image_size_percent' => 90]);
        }
    }

    public function down()
    {
        $connection = CentralContext::trueCentralConnectionName();
        $schema = Schema::connection($connection);

        if ($schema->hasTable('banner_idle_settings')
            && $schema->hasColumn('banner_idle_settings', 'image_size_percent')) {
            $schema->table('banner_idle_settings', function (Blueprint $table) {
                $table->dropColumn('image_size_percent');
            });
        }
    }
}
