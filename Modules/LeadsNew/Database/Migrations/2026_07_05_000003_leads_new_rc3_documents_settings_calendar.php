<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LeadsNewRc3DocumentsSettingsCalendar extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('leads_new_documents')) {
            Schema::create('leads_new_documents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('lead_id')->nullable()->index();
                $table->unsignedBigInteger('opportunity_id')->nullable()->index();
                $table->string('title')->nullable();
                $table->string('file_name')->nullable();
                $table->string('file_path', 500)->nullable();
                $table->string('file_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('leads_new_settings')) {
            Schema::create('leads_new_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('key')->index();
                $table->text('value')->nullable();
                $table->string('type', 50)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'key'], 'leads_new_settings_business_key_unique');
            });
        }

        if (! Schema::hasTable('leads_new_notes')) {
            Schema::create('leads_new_notes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('lead_id')->nullable()->index();
                $table->unsignedBigInteger('opportunity_id')->nullable()->index();
                $table->text('note');
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('leads_new_calendar_events')) {
            Schema::create('leads_new_calendar_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('lead_id')->nullable()->index();
                $table->unsignedBigInteger('opportunity_id')->nullable();
                $table->string('title');
                $table->string('event_type', 50)->default('followup')->index();
                $table->dateTime('start_at')->nullable()->index();
                $table->dateTime('end_at')->nullable();
                $table->string('status', 50)->default('scheduled');
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $this->addColumnIfMissing('leads_new_followups', 'business_id', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable()->after('id')->index();
        });
        $this->addColumnIfMissing('leads_new_followups', 'location_id', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('business_id')->index();
        });
        $this->addColumnIfMissing('leads_new_opportunities', 'location_id', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('business_id')->index();
        });
        $this->addColumnIfMissing('leads_new_activities', 'location_id', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('business_id')->index();
        });
    }

    public function down()
    {
        // Production tenant-safe migration: keep existing tenant data intact.
    }

    private function addColumnIfMissing($table, $column, callable $callback)
    {
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
            Schema::table($table, $callback);
        }
    }
}
