<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('myhealth_lab_test_catalogue')) {
            Schema::create('myhealth_lab_test_catalogue', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('test_code')->nullable()->index();
                $table->string('test_name')->index();
                $table->string('department')->nullable()->index();
                $table->string('category')->nullable()->index();
                $table->string('sample_type')->nullable();
                $table->string('normal_range')->nullable();
                $table->string('turnaround_time')->nullable();
                $table->decimal('price', 22, 4)->nullable();
                $table->text('instructions')->nullable();
                $table->string('status')->default('active')->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('myhealth_lab_samples')) {
            Schema::create('myhealth_lab_samples', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->unsignedBigInteger('consultation_id')->nullable()->index();
                $table->unsignedBigInteger('lab_request_id')->nullable()->index();
                $table->string('sample_no')->unique();
                $table->string('barcode')->nullable()->index();
                $table->string('sample_type')->nullable()->index();
                $table->string('priority')->default('routine')->index();
                $table->string('status')->default('collected')->index();
                $table->dateTime('collected_at')->nullable();
                $table->dateTime('received_at')->nullable();
                $table->dateTime('processed_at')->nullable();
                $table->dateTime('verified_at')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->dateTime('released_at')->nullable();
                $table->unsignedBigInteger('collector_id')->nullable();
                $table->unsignedBigInteger('received_by')->nullable();
                $table->unsignedBigInteger('technician_id')->nullable();
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->text('remarks')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('myhealth_lab_results_enterprise')) {
            Schema::create('myhealth_lab_results_enterprise', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('sample_id')->nullable()->index();
                $table->unsignedBigInteger('test_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->string('result_value')->nullable();
                $table->string('unit')->nullable();
                $table->string('reference_range')->nullable();
                $table->text('interpretation')->nullable();
                $table->boolean('is_abnormal')->default(false)->index();
                $table->boolean('is_critical')->default(false)->index();
                $table->text('technician_comments')->nullable();
                $table->text('doctor_comments')->nullable();
                $table->string('status')->default('entered')->index();
                $table->unsignedBigInteger('entered_by')->nullable();
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('entered_at')->nullable();
                $table->dateTime('verified_at')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('myhealth_lab_results_enterprise');
        Schema::dropIfExists('myhealth_lab_samples');
        Schema::dropIfExists('myhealth_lab_test_catalogue');
    }
};
