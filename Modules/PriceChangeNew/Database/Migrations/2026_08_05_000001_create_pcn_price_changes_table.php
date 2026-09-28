<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('pcn_price_changes')) return;
        Schema::create('pcn_price_changes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->string('reference_no', 50);
            $table->string('title', 191);
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->dateTime('effective_at')->nullable()->index();
            $table->string('stock_price_mode', 30)->default('all_stock');
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('submitted_by')->nullable()->index();
            $table->unsignedInteger('approved_by')->nullable()->index();
            $table->unsignedInteger('applied_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('applied_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id','reference_no'], 'pcn_changes_business_reference_unique');
            $table->index(['business_id','status','created_at'], 'pcn_changes_business_status_created_idx');
        });
    }
    public function down(): void { Schema::dropIfExists('pcn_price_changes'); }
};
