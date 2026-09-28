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
        Schema::table('subscription_settings', function (Blueprint $table) {
               $table->string('subscription_code', 50)->nullable()->after('id');

        });
        $settings = DB::table('subscription_settings')->get();
    foreach ($settings as $setting) {
        DB::table('subscription_settings')
            ->where('id', $setting->id)
            ->update([
                'subscription_code' => 'Subs-' . str_pad($setting->id, 4, '0', STR_PAD_LEFT)
            ]);
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_settings', function (Blueprint $table) {
            //
            $table->dropColumn('subscription_code');
        });
    }
};
