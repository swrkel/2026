<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembershipMemberTable extends Migration
{
    public function up()
    {
        Schema::create('membership_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('member_number')->unique();
            $table->string('member_name');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('membership_business_type_id');
            $table->string('default_mobile_number');
            $table->text('other_mobile_numbers')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('membership_members');
    }
}
