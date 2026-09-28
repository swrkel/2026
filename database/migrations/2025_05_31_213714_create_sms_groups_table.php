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
        Schema::create('sms_groups', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->string('group_name', 255);
            $table->longText('members')->nullable();
            $table->date('date');
            $table->date('created_at');
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
        Schema::dropIfExists('sms_groups');
    }
};
