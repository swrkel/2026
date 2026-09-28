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
        Schema::create('default_group_sub_taxes', function (Blueprint $table) {
            $table->unsignedInteger('group_tax_id')->index('group_sub_taxes_group_tax_id_foreign');
            $table->unsignedInteger('tax_id')->index('group_sub_taxes_tax_id_foreign');

            $table->index(['group_tax_id'], 'group_tax_id');
            $table->index(['tax_id'], 'tax_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('default_group_sub_taxes');
    }
};
