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
        Schema::create('uploaded_orders', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('contact_id')->index('contact_id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('transaction_id')->nullable()->index('transaction_id');
            $table->text('image');
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
        Schema::dropIfExists('uploaded_orders');
    }
};
