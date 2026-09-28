<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function connectionName(): ?string
    {
        return config('myhealthmembers.central_connection');
    }

    public function up(): void
    {
        $connection = $this->connectionName();

        if (! Schema::connection($connection)->hasTable('myhealth_ai_reminders')) {
            Schema::connection($connection)->create('myhealth_ai_reminders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('reminder_type', 80)->index();
                $table->string('priority', 30)->default('medium')->index();
                $table->string('title');
                $table->text('message')->nullable();
                $table->date('due_date')->nullable()->index();
                $table->string('status', 30)->default('pending')->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('completed_by')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection($connection)->hasTable('myhealth_ai_audit_logs')) {
            Schema::connection($connection)->create('myhealth_ai_audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->unsignedBigInteger('alert_id')->nullable()->index();
                $table->string('action', 100)->index();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->timestamp('performed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $connection = $this->connectionName();
        Schema::connection($connection)->dropIfExists('myhealth_ai_audit_logs');
        Schema::connection($connection)->dropIfExists('myhealth_ai_reminders');
    }
};
