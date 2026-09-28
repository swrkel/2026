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
        if (Schema::hasTable('ads')) {
            return;
        }

        Schema::create('ads', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('ad_id', 255)->index('ad_id');
            $table->integer('ad_page_id')->index('ad_page_id');
            $table->integer('ad_page_slot_id')->index('ad_page_slot_id');
            $table->integer('status')->default(0);
            $table->string('code', 50);
            $table->string('client_name', 255);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount', 10, 0);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->string('content', 2000);
            $table->string('link', 2000)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ads');
    }
};
