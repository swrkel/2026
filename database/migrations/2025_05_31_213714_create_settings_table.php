<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('google_key')->nullable();
            $table->string('google_analytics_id')->nullable()->index('google_analytics_id');
            $table->longText('site_name')->nullable();
            $table->string('site_logo')->nullable();
            $table->string('favicon')->nullable();
            $table->longText('seo_meta_description')->nullable();
            $table->longText('seo_keywords')->nullable();
            $table->string('seo_image')->nullable();
            $table->string('tawk_chat_bot_key')->nullable();
            $table->longText('name');
            $table->longText('address');
            $table->string('driver');
            $table->string('host');
            $table->integer('port');
            $table->string('encryption');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('1');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->decimal('quantity', 22)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
