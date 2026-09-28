<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyhealthAccessRequestsTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('myhealth_access_requests')) {
            Schema::create('myhealth_access_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('member_code', 20)->index();
                $table->string('mobile', 50)->nullable()->index();
                $table->string('nic_no', 50)->nullable()->index();
                $table->string('purpose');
                $table->text('access_sections')->nullable();
                $table->string('otp', 10)->nullable();
                $table->string('status', 30)->default('pending_otp')->index();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('myhealth_access_requests');
    }
}
