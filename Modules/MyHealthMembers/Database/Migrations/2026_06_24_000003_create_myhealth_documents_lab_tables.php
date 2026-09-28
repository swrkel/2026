<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyhealthDocumentsLabTables extends Migration
{
    public function up(): void
    {
        $connection = config('myhealthmembers.central_connection');

        if (! Schema::connection($connection)->hasColumn('myhealth_members', 'qr_token')) {
            Schema::connection($connection)->table('myhealth_members', function (Blueprint $table) {
                $table->string('qr_token')->nullable()->unique()->after('myhealth_code');
                $table->string('blood_group')->nullable()->after('gender');
            });
        }

        Schema::connection($connection)->create('myhealth_lab_requests', function (Blueprint $table) {
            $table->id();
            $table->string('lab_request_no')->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->date('request_date');
            $table->string('test_name');
            $table->text('clinical_notes')->nullable();
            $table->string('status')->default('pending'); // pending | resulted | cancelled
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_lab_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lab_request_id')->index();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('resulted_by')->nullable()->index();
            $table->date('result_date');
            $table->text('result_summary')->nullable();
            $table->text('result_details')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->foreign('lab_request_id')->references('id')->on('myhealth_lab_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->dropIfExists('myhealth_lab_results');
        Schema::connection($connection)->dropIfExists('myhealth_lab_requests');

        if (Schema::connection($connection)->hasColumn('myhealth_members', 'qr_token')) {
            Schema::connection($connection)->table('myhealth_members', function (Blueprint $table) {
                $table->dropColumn(['qr_token', 'blood_group']);
            });
        }
    }
}
