<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('issue_customer_bill_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index('business_id');
            $table->boolean('show_pump')->default(true);
            $table->boolean('show_pump_operator')->default(true);
            $table->tinyInteger('print_option')->default(1)->comment('1=A4/A5, 2=POS Bill 80mm');
            $table->unsignedInteger('added_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_customer_bill_settings');
    }
};
