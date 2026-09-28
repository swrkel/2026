<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

         // Default records
        DB::table('system')->insert([
            ['key' => 'enable_landing_page', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_logo', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'favicon', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system');
    }
};
