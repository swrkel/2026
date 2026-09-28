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
        Schema::create('vat_contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->enum('type', ['supplier', 'customer', 'both', 'lead']);
            $table->string('name');
            $table->string('contact_id')->nullable()->index('contact_id');
            $table->string('mobile')->nullable();
            $table->string('alternate_number')->nullable();
            $table->unsignedInteger('created_by')->index('contacts_created_by_foreign');
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->integer('should_notify')->default(0);
            $table->string('credit_notification', 60)->nullable();
            $table->string('vat_no', 30)->nullable();

            $table->index(['business_id'], 'contacts_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_contacts');
    }
};
