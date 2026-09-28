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
        Schema::create('package_variables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('variable_options');
            $table->string('variable_code');
            $table->decimal('option_value', 15, 0);
            $table->enum('increase_decrease', ['0', '1']);
            $table->enum('variable_type', ['0', '1']);
            $table->double('price_value');
            $table->boolean('is_company_variable')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('package_variables');
    }
};
