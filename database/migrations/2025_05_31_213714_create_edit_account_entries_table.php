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
        Schema::create('edit_account_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('account_id')->index('account_id');
            $table->unsignedInteger('account_transaction_id')->index('account_transaction_id');
            $table->timestamp('date_and_time')->useCurrentOnUpdate()->useCurrent();
            $table->decimal('orignal_amount', 15, 6);
            $table->decimal('edited_amount', 15, 6);
            $table->enum('action_type', ['edited', 'deleted'])->nullable();
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
        Schema::dropIfExists('edit_account_entries');
    }
};
