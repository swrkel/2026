<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Church Management — phase 2: donations, attendance and events.
 *
 * Same conventions as phase 1: chc_ prefix, no foreign keys, business-scoped,
 * soft deletes where the record is part of the congregation's history.
 */
return new class extends Migration
{
    private string $prefix = 'chc_';

    public function up(): void
    {
        $this->createDonationTypes();
        $this->createDonations();
        $this->createServices();
        $this->createAttendance();
        $this->createEvents();
    }

    /**
     * Tithe, offering, building fund and so on.
     *
     * A table rather than an enum: every congregation names these differently,
     * and they add new ones without wanting a migration.
     */
    private function createDonationTypes(): void
    {
        $table = $this->prefix . 'donation_types';
        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('business_id')->index();
            $t->string('name', 100);
            $t->string('description', 255)->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['business_id', 'is_active'], 'chc_dtype_biz_active_idx');
        });
    }

    private function createDonations(): void
    {
        $table = $this->prefix . 'donations';
        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('business_id')->index();
            $t->unsignedInteger('business_location_id')->nullable()->index();

            $t->string('receipt_no', 50)->nullable();

            /*
             | Nullable on purpose: a collection plate is anonymous. Where the
             | giver is unknown, donor_name can carry a written name without
             | creating a member record for a one-off visitor.
             */
            $t->unsignedBigInteger('member_id')->nullable()->index();
            $t->string('donor_name', 191)->nullable();

            $t->unsignedBigInteger('donation_type_id')->nullable()->index();

            /*
             | decimal(22,4), matching the money columns elsewhere in this
             | application. A float would lose cents on a large annual total,
             | which is exactly the number a treasurer checks.
             */
            $t->decimal('amount', 22, 4)->default(0);

            $t->date('donation_date')->index();
            $t->string('payment_method', 50)->nullable();
            $t->string('reference_no', 100)->nullable();
            $t->text('notes')->nullable();

            $t->unsignedInteger('created_by')->nullable();
            $t->unsignedInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['business_id', 'donation_date'], 'chc_don_biz_date_idx');
            $t->index(['business_id', 'member_id'], 'chc_don_biz_member_idx');
            $t->index(['business_id', 'donation_type_id'], 'chc_don_biz_type_idx');
        });
    }

    /**
     * A service or meeting that attendance is taken against.
     *
     * Separate from attendance itself so a service exists once, with one date,
     * time and title, rather than being repeated on every member's row.
     */
    private function createServices(): void
    {
        $table = $this->prefix . 'services';
        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('business_id')->index();
            $t->unsignedInteger('business_location_id')->nullable()->index();

            $t->string('title', 191);
            $t->date('service_date')->index();
            $t->time('service_time')->nullable();
            $t->string('service_type', 50)->nullable();

            /*
             | A headcount for services where names are not taken. Many
             | congregations count the room rather than mark a register, and
             | forcing per-member rows would make the feature unusable for them.
             */
            $t->unsignedInteger('headcount')->nullable();

            $t->text('notes')->nullable();
            $t->unsignedInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['business_id', 'service_date'], 'chc_svc_biz_date_idx');
        });
    }

    private function createAttendance(): void
    {
        $table = $this->prefix . 'attendance';
        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('business_id')->index();
            $t->unsignedBigInteger('service_id')->index();
            $t->unsignedBigInteger('member_id')->index();

            $t->enum('status', ['present', 'absent', 'excused'])->default('present');
            $t->text('notes')->nullable();

            $t->unsignedInteger('created_by')->nullable();
            $t->timestamps();

            /*
             | One row per member per service. Without this a double-submitted
             | register would silently count someone twice, and every attendance
             | figure downstream would be wrong.
             */
            $t->unique(['service_id', 'member_id'], 'chc_att_service_member_unique');
            $t->index(['business_id', 'service_id'], 'chc_att_biz_service_idx');
            $t->index(['business_id', 'member_id'], 'chc_att_biz_member_idx');
        });
    }

    private function createEvents(): void
    {
        $table = $this->prefix . 'events';
        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('business_id')->index();
            $t->unsignedInteger('business_location_id')->nullable()->index();

            $t->string('title', 191);
            $t->string('event_type', 50)->nullable();

            $t->date('event_date')->index();
            // Nullable so a whole-day or multi-day event does not need invented
            // times, and so an end date is optional for a single-day event.
            $t->date('end_date')->nullable();
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();

            $t->string('venue', 191)->nullable();
            $t->unsignedBigInteger('organiser_member_id')->nullable()->index();
            $t->text('description')->nullable();

            $t->enum('status', ['planned', 'confirmed', 'completed', 'cancelled'])
                ->default('planned');

            $t->unsignedInteger('created_by')->nullable();
            $t->unsignedInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['business_id', 'event_date'], 'chc_evt_biz_date_idx');
            $t->index(['business_id', 'status'], 'chc_evt_biz_status_idx');
        });
    }

    public function down(): void
    {
        // Reverse dependency order. Only this module's own tables are named.
        Schema::dropIfExists($this->prefix . 'events');
        Schema::dropIfExists($this->prefix . 'attendance');
        Schema::dropIfExists($this->prefix . 'services');
        Schema::dropIfExists($this->prefix . 'donations');
        Schema::dropIfExists($this->prefix . 'donation_types');
    }
};
