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
        if (Schema::hasTable('accounts')) {
            return;
        }

        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index('business_id');
            $table->string('location_id', 20)->default('all')->index('location_id');
            $table->string('name');
            $table->string('account_number');
            $table->integer('account_type_id')->nullable()->index('account_type_id');
            $table->text('note')->nullable();
            $table->integer('asset_type')->nullable();
            $table->enum('is_need_cheque', ['Y', 'N'])->default('N');
            $table->integer('created_by');
            $table->boolean('is_main_account')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->softDeletes();
            $table->unsignedBigInteger('default_account_id')->nullable()->index('default_account_id');
            $table->timestamps();
            $table->unsignedInteger('parent_account_id')->nullable()->index('parent_account_id');
            $table->boolean('visible')->default(true);
            $table->boolean('disabled')->default(false);
            $table->boolean('show_in_balance_sheet')->nullable()->default(true);
            $table->integer('is_property')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
};
