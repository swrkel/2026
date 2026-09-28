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
       Schema::table('distribution_sales_agents', function (Blueprint $table) {
            $table->date('joined_date')->nullable();
            $table->string('employment_grade')->nullable();
            $table->decimal('salary', 22, 4)->default(0);
            $table->decimal('commission', 22, 4)->default(0);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_sales_agents', function (Blueprint $table) {
            //
        });
    }
};
