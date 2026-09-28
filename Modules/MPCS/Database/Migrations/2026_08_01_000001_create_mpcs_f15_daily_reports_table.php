<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMpcsF15DailyReportsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('mpcs_f15_daily_reports')) {
            return;
        }

        Schema::create('mpcs_f15_daily_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->date('report_date');
            $table->string('form_no', 100)->nullable();

            $table->decimal('changes_addition', 22, 4)->default(0);
            $table->decimal('changes_deduction', 22, 4)->default(0);
            $table->decimal('damaged', 22, 4)->default(0);
            $table->decimal('others', 22, 4)->default(0);
            $table->decimal('total_return', 22, 4)->default(0);

            $table->text('notes')->nullable();
            $table->string('prepared_by', 191)->nullable();
            $table->date('prepared_date')->nullable();
            $table->string('checked_by', 191)->nullable();
            $table->date('checked_date')->nullable();
            $table->string('approved_by', 191)->nullable();
            $table->date('approved_date')->nullable();
            $table->longText('totals_json')->nullable();

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(
                ['business_id', 'location_id', 'report_date'],
                'mpcs_f15_daily_business_location_date_unique'
            );
            $table->index(
                ['business_id', 'report_date'],
                'mpcs_f15_daily_business_date_index'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('mpcs_f15_daily_reports');
    }
}
