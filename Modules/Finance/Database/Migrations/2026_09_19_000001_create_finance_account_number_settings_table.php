<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_account_number_settings')) {
            return;
        }

        Schema::create('finance_account_number_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->string('account_type_key', 32);
            $table->unsignedBigInteger('start_number');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'account_type_key'], 'fin_acc_num_business_type_unique');
            $table->index('business_id', 'fin_acc_num_business_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_number_settings');
    }
};
