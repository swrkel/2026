<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('auto_service_payments')) {
            Schema::table('auto_service_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_payments', 'invoice_id')) $table->unsignedBigInteger('invoice_id')->nullable()->index()->after('job_id');
                if (!Schema::hasColumn('auto_service_payments', 'location_id')) $table->unsignedBigInteger('location_id')->nullable()->index()->after('business_id');
                if (!Schema::hasColumn('auto_service_payments', 'contact_id')) $table->unsignedBigInteger('contact_id')->nullable()->index()->after('invoice_id');
                if (!Schema::hasColumn('auto_service_payments', 'status')) $table->string('status')->default('received')->index()->after('amount');
                if (!Schema::hasColumn('auto_service_payments', 'created_by')) $table->unsignedBigInteger('created_by')->nullable()->index()->after('note');
            });
        }
        if (Schema::hasTable('auto_service_jobs')) {
            Schema::table('auto_service_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_jobs', 'delivered_at')) $table->dateTime('delivered_at')->nullable()->index();
                if (!Schema::hasColumn('auto_service_jobs', 'delivered_by')) $table->unsignedBigInteger('delivered_by')->nullable()->index();
                if (!Schema::hasColumn('auto_service_jobs', 'delivery_note')) $table->text('delivery_note')->nullable();
            });
        }
    }
    public function down() {}
};
