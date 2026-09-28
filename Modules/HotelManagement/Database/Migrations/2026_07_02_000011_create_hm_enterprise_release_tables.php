<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hm_guest_portal_requests')) {
            Schema::create('hm_guest_portal_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('guest_id')->nullable()->index();
                $table->unsignedBigInteger('reservation_id')->nullable()->index();
                $table->string('request_no')->nullable()->index();
                $table->string('request_type')->nullable()->index();
                $table->string('status')->default('open')->index();
                $table->text('details')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('hm_report_snapshots')) {
            Schema::create('hm_report_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('report_key')->index();
                $table->date('snapshot_date')->index();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hm_release_audits')) {
            Schema::create('hm_release_audits', function (Blueprint $table) {
                $table->id();
                $table->string('audit_key')->index();
                $table->string('status')->default('pending')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_release_audits');
        Schema::dropIfExists('hm_report_snapshots');
        Schema::dropIfExists('hm_guest_portal_requests');
    }
};
