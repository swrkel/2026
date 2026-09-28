<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('saved_lioc_statements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('lioc_report_customized_id')->nullable()->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('period_display', 128)->nullable();
            $table->string('bill_ref_display', 512)->nullable();
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->unsignedInteger('line_count')->default(0);
            $table->json('transaction_ids');
            $table->longText('lines_json')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_lioc_statements');
    }
};
