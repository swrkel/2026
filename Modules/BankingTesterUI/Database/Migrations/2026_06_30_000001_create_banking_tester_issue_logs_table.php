<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('banking_tester_issue_logs')) {
            Schema::create('banking_tester_issue_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->string('module')->nullable();
                $table->string('page')->nullable();
                $table->string('status')->default('open');
                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('banking_tester_issue_logs');
    }
};
