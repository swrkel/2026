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
        Schema::create('business_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('location_id')->nullable()->index('location_id');
            $table->string('name', 256);
            $table->text('landmark')->nullable();
            $table->string('address_1', 255)->nullable();
            $table->string('address_2', 255)->nullable();
            $table->string('address_3', 255)->nullable();
            $table->string('country', 100);
            $table->string('state', 100);
            $table->string('city', 100);
            $table->char('zip_code', 7);
            $table->float('latitude', 10, 0)->nullable();
            $table->float('longitude', 10, 0)->nullable();
            $table->string('timezone', 100)->nullable();
            $table->string('location_access_type', 100)->nullable();
            $table->unsignedInteger('invoice_scheme_id')->index('business_locations_invoice_scheme_id_foreign');
            $table->unsignedInteger('invoice_layout_id')->index('business_locations_invoice_layout_id_foreign');
            $table->integer('selling_price_group_id')->nullable()->index('selling_price_group_id');
            $table->boolean('print_receipt_on_invoice')->nullable()->default(true);
            $table->enum('receipt_printer_type', ['browser', 'printer'])->default('browser');
            $table->integer('printer_id')->nullable()->index('printer_id');
            $table->string('mobile')->nullable();
            $table->string('alternate_number')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('default_payment_accounts')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('custom_field1')->nullable();
            $table->string('custom_field2')->nullable();
            $table->string('custom_field3')->nullable();
            $table->string('custom_field4')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->bigInteger('currency_id')->nullable()->default(111)->index('currency_id');
            $table->string('district', 100)->nullable();

            $table->index(['business_id']);
            $table->index(['invoice_layout_id'], 'invoice_layout_id');
            $table->index(['invoice_scheme_id'], 'invoice_scheme_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_locations');
    }
};
