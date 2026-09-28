<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositAccountPartiesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('deposit_account_parties')) {
            Schema::create('deposit_account_parties', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('deposit_account_id')->index();
                $table->string('party_type')->default('nominee')->index();
                $table->string('name');
                $table->string('relationship')->nullable();
                $table->string('nic_no')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('share_percentage', 8, 4)->default(100);
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('deposit_account_parties');
    }
}
