<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('leads_new_followups')) {
            Schema::create('leads_new_followups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lead_id')->index();
                $table->string('type')->nullable();
                $table->dateTime('followup_at')->index();
                $table->string('status')->default('pending')->index();
                $table->text('remarks')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads_new_followups');
    }
};
