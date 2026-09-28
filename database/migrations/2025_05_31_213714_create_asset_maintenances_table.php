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
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('business_id')->index();
            $table->integer('asset_id')->index('asset_id');
            $table->string('maitenance_id')->nullable()->index('maitenance_id');
            $table->string('status')->nullable()->index();
            $table->string('priority')->nullable()->index();
            $table->integer('created_by')->index();
            $table->integer('assigned_to')->nullable()->index();
            $table->text('details')->nullable();
            $table->text('maintenance_note')->nullable();
            $table->timestamps();

            $table->index(['asset_id']);
            $table->index(['business_id'], 'business_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_maintenances');
    }
};
