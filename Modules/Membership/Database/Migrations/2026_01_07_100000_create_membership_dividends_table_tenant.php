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
        if (!Schema::hasTable('membership_dividends')) {
            Schema::create('membership_dividends', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->unsignedBigInteger('member_id');
                $table->date('dividend_date');
                $table->decimal('dividend_amount', 15, 2)->default(0);
                $table->string('reference_number', 255)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                
                $table->index('business_id');
                $table->index('member_id');
                $table->index('dividend_date');
                // $table->foreign('member_id')->references('id')->on('membership_members')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_dividends');
    }
};

