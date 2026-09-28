<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembershipPointsTable extends Migration
{
    public function up()
    {
        Schema::create('membership_points', function (Blueprint $table) {
            $table->id();
            $table->integer('form_number');
            $table->date('date');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('business_type_id');
            $table->string('business_name')->nullable();
            $table->string('bill_number')->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('earned_points', 18, 2)->default(0);
            $table->decimal('redeemed_points', 18, 2)->default(0);
            $table->decimal('point_balance', 18, 2);
            $table->string('payment_details')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('membership_points');
    }
}
