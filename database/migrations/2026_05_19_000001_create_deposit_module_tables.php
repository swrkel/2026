<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositModuleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. deposit_settings
        Schema::create('deposit_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('settings_key', 100);
            $table->text('settings_value')->nullable();
            $table->timestamps();
            
            $table->unique(['business_id', 'settings_key'], 'business_key_unique');
            $table->index('business_id');
        });

        // 2. deposit_types
        Schema::create('deposit_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('name', 150);
            $table->enum('period', ['Daily', 'Weekly', 'Monthly', 'Yearly']);
            $table->integer('period_value');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('last_edited_by')->nullable();
            $table->timestamps();

            $table->index('business_id');
        });

        // 3. deposit_type_activities
        Schema::create('deposit_type_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('deposit_type_id');
            $table->string('original_added_by', 150);
            $table->string('changed_by_user', 150);
            $table->text('details');
            $table->timestamps();

            $table->foreign('deposit_type_id')->references('id')->on('deposit_types')->onDelete('cascade');
        });

        // 4. deposit_records
        Schema::create('deposit_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->string('deposit_number', 100);
            $table->unsignedInteger('contact_id'); // Bank Customer reference
            $table->string('current_loan_id', 100)->nullable();
            $table->unsignedBigInteger('deposit_type_id');
            $table->enum('deposit_period', ['Daily', 'Weekly', 'Monthly', 'Yearly']);
            $table->integer('deposit_period_value');
            $table->string('interest_per', 50)->nullable();
            $table->decimal('total_interest', 15, 2)->default(0.00);
            $table->string('currency', 10);
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['Cash', 'Card', 'Cheque', 'Online Transfer']);
            
            // Payment method details
            $table->string('card_number', 50)->nullable();
            $table->string('slip_number', 100)->nullable();
            $table->string('bank', 150)->nullable();
            $table->string('cheque_no', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->unsignedInteger('deposited_bank_id')->nullable(); // Account reference
            $table->string('transaction_reference', 150)->nullable(); // Transfer ID
            
            $table->string('attachment', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->foreign('deposit_type_id')->references('id')->on('deposit_types');
            
            $table->index('business_id');
            $table->index('location_id');
            $table->index('contact_id');
            $table->index('deposit_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deposit_records');
        Schema::dropIfExists('deposit_type_activities');
        Schema::dropIfExists('deposit_types');
        Schema::dropIfExists('deposit_settings');
    }
}
