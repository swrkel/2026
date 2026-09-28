<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 8046 - Customer Reference (QR).
 *
 * TABLE NAME
 *   customer_qr_references, NOT customer_references.
 *
 *   `customer_references` already exists and is SHARED across Petro, PetroPD,
 *   PetroDirect, PetroGeneral, Vat, SettlementSW, PumperDashboard, EVCharging,
 *   EzyInvoice, DailyCollectionSW and app-level controllers. It is the legacy
 *   "Vehicle No" table, with a completely different shape (contact_id,
 *   reference, opening_balance, barcode_src). This feature must not touch it.
 *
 * DOWN()
 *   Drops only customer_qr_references, which nothing else reads. It can never
 *   drop the shared table - the name does not appear in this file.
 *
 * NOTE FOR THIS DEPLOYMENT
 *   Tenants here are at different schema levels, so the raw SQL under
 *   Database/RawSQL/2026_08_28_customer_reference/ is the intended install
 *   path and lets each tenant be inspected first. This migration is provided
 *   for completeness and for any tenant where running migrations is preferred.
 *   Both are guarded, so neither will act twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_qr_references')) {
            return;
        }

        Schema::create('customer_qr_references', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();

            // contacts.id. Named customer_id rather than contact_id to match
            // the Customers module's own naming, and because this table is not
            // related to the legacy customer_references.contact_id column.
            $table->unsignedInteger('customer_id')->index();

            $table->dateTime('reference_datetime')->nullable();

            $table->boolean('is_vehicle')->default(false);
            $table->string('reference_no', 191);

            /*
             * PRODUCT sub-category id (categories.id), meaningful only when
             * is_vehicle = 1. NULL carries the system default "Not Known",
             * which must stay selectable even where no Fuel category exists.
             */
            $table->unsignedInteger('fuel_type_id')->nullable()->index();

            /*
             * Name snapshot. A QR gets printed and stuck on a vehicle; it has
             * to keep saying what it said when printed, even if the product
             * sub-category is later renamed.
             */
            $table->string('fuel_type_name', 191)->nullable();

            $table->text('qr_payload')->nullable();
            $table->string('qr_token', 64)->nullable()->unique();

            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'customer_id'], 'cus_qr_ref_biz_customer_idx');
            $table->index(['business_id', 'reference_datetime'], 'cus_qr_ref_biz_datetime_idx');
            $table->index(['business_id', 'reference_no'], 'cus_qr_ref_biz_refno_idx');
            $table->index(['business_id', 'is_active'], 'cus_qr_ref_biz_active_idx');
        });
    }

    public function down(): void
    {
        // Only the table this migration created. The shared customer_references
        // table is deliberately not named anywhere in this file.
        Schema::dropIfExists('customer_qr_references');
    }
};
