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
        Schema::create('distribution_prefix_settings', function (Blueprint $table) {
             $table->id();
            $table->unsignedBigInteger('business_id');

            $table->enum('numbering_type', [
                'sales_invoice',
                'daily_summary_sheet'
            ]);

            $table->string('prefix')->nullable();
            $table->integer('starting_no');
            $table->integer('current_no'); // auto increment reference

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'numbering_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_prefix_settings');
    }
};
