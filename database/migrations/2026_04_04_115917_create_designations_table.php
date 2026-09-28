<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDesignationsTable extends Migration
{
    public function up()
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('department_id');
            $table->string('designation');

            // user tracking
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Prevent duplicates per department + business
            $table->unique(['business_id', 'department_id', 'designation'], 'unique_designation_per_department');
        });
    }

    public function down()
    {
        Schema::dropIfExists('designations');
    }
}