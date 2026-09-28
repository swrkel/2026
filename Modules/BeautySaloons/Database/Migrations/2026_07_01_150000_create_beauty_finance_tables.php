<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('beauty_finance_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->string('account_key')->index();
            $table->unsignedInteger('account_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('beauty_finance_postings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->date('posting_date')->index();
            $table->string('reference_no')->index();
            $table->string('posting_type')->index();
            $table->string('account_key')->index();
            $table->decimal('debit', 22, 4)->default(0);
            $table->decimal('credit', 22, 4)->default(0);
            $table->string('status')->default('posted')->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('beauty_finance_postings');
        Schema::dropIfExists('beauty_finance_account_mappings');
    }
};
