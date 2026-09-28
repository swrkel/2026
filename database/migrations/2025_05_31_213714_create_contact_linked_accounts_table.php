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
        Schema::create('contact_linked_accounts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->integer('customer_advance');
            $table->integer('supplier_advance');
            $table->integer('customer_deposit_refund_liability_account')->nullable();
            $table->integer('customer_deposit_refund_asset_account')->nullable();
            $table->integer('created_by');
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->text('location')->nullable();
            $table->integer('status')->nullable()->comment('0 = disable, 1 = active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_linked_accounts');
    }
};
