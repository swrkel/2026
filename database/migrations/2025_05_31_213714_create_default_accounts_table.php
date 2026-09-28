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
        Schema::create('default_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index('business_id');
            $table->string('name');
            $table->string('account_number');
            $table->integer('account_type_id')->nullable()->index('account_type_id');
            $table->text('note')->nullable();
            $table->integer('asset_type')->nullable();
            $table->integer('created_by');
            $table->boolean('is_closed')->default(false);
            $table->softDeletes();
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
        Schema::dropIfExists('default_accounts');
    }
};
