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
        if (Schema::hasTable('account_types')) {
            return;
        }

        Schema::create('account_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('parent_account_type_id')->nullable()->index('parent_account_type_id');
            $table->integer('business_id')->index('business_id');
            $table->unsignedBigInteger('default_account_type_id')->nullable()->index('default_account_type_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_types');
    }
};
