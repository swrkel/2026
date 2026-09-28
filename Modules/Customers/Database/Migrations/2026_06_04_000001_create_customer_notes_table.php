<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerNotesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('customer_notes')) {
            return;
        }

        Schema::create('customer_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->index();
            $table->string('note_type', 50)->default('general');
            $table->text('note');
            $table->boolean('is_private')->default(false);
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_notes');
    }
}
