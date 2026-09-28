<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales Agent Module - Migration for sales_agents table
 * Created per requirement 7743-4: Create Sales Agent Database Table
 * 
 * Note: Using 'sales_agent_' prefix as specified in requirements
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_agents', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->integer('location_id')->unsigned()->nullable();
            $table->string('name');
            $table->date('joined_date');
            $table->string('employment_grade')->nullable(); // Simple text field per client requirement
            $table->decimal('salary', 22, 4)->default(0);
            $table->decimal('commission', 22, 4)->default(0);
            $table->integer('user_id')->unsigned()->nullable(); // Can be linked to existing user
            $table->integer('created_by')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('business_id');
            $table->index('location_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_agents');
    }
};
