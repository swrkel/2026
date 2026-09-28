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
        Schema::create('asset_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('asset_transactions_business_id_foreign');
            $table->unsignedInteger('asset_id')->nullable()->index('asset_id');
            $table->string('transaction_type');
            $table->string('ref_no');
            $table->unsignedInteger('receiver')->nullable()->index('asset_transactions_receiver_foreign')->comment('id from users table, who receives asset');
            $table->decimal('quantity', 22, 4);
            $table->dateTime('transaction_datetime');
            $table->date('allocated_upto')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedInteger('parent_id')->nullable()->index('asset_transactions_parent_id_foreign')->comment('id from asset_transactions table');
            $table->unsignedInteger('created_by')->index('asset_transactions_created_by_foreign')->comment('id from users table, who allocated asset');
            $table->timestamps();

            $table->index(['asset_id'], 'asset_transactions_asset_id_foreign');
            $table->index(['business_id'], 'business_id');
            $table->index(['parent_id'], 'parent_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_transactions');
    }
};
