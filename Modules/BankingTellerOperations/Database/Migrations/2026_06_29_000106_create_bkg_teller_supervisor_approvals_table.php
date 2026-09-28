<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkg_teller_supervisor_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_no')->unique();
            $table->string('approval_type')->index();
            $table->string('reference_type')->index();
            $table->unsignedBigInteger('reference_id')->index();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_teller_supervisor_approvals');
    }
};
