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
        if (Schema::hasTable('account_settings')) {
            return;
        }
        Schema::create('account_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('date');
            $table->unsignedInteger('account_id')->index('account_id');
            $table->unsignedInteger('group_id')->index('group_id');
            $table->decimal('amount', 15, 6);
            $table->integer('at_asset_id')->index('at_asset_id')->comment('Account Transaction Id for asset account');
            $table->integer('at_obe_id')->index('at_obe_id')->comment('Account Transaction Id for Opening Balance Equity Account');
            $table->integer('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_settings');
    }
};
