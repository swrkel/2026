<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('leads_new_leads')) {
            Schema::create('leads_new_leads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('lead_no')->unique();
                $table->string('name');
                $table->string('mobile')->nullable()->index();
                $table->string('email')->nullable();
                $table->string('nic')->nullable()->index();
                $table->string('source')->nullable()->index();
                $table->string('status')->nullable()->index();
                $table->string('priority')->nullable()->index();
                $table->date('transaction_date')->nullable();
                $table->dateTime('next_followup_at')->nullable()->index();
                $table->text('note')->nullable();
                $table->boolean('is_archived')->default(false)->index();
                $table->dateTime('archived_at')->nullable();
                $table->unsignedBigInteger('archived_by')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads_new_leads');
    }
};
