<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyhealthMobileApiTables extends Migration
{
    public function up(): void
    {
        $connection = config('myhealthmembers.central_connection');

        if (! Schema::connection($connection)->hasTable('myhealth_mobile_access_tokens')) {
            Schema::connection($connection)->create('myhealth_mobile_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('token_hash', 64)->unique();
                $table->string('device_name')->nullable();
                $table->string('device_id')->nullable()->index();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('revoked_at')->nullable()->index();
                $table->timestamps();

                $table->foreign('member_id')->references('id')->on('myhealth_members')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::connection(config('myhealthmembers.central_connection'))->dropIfExists('myhealth_mobile_access_tokens');
    }
}
