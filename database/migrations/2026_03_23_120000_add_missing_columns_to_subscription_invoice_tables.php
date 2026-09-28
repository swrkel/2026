<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('subscription_invoice_prefixes')) {
            Schema::table('subscription_invoice_prefixes', function (Blueprint $table) {
                if (!Schema::hasColumn('subscription_invoice_prefixes', 'business_id')) {
                    $table->unsignedBigInteger('business_id')->nullable()->after('user_id');
                }

                if (!Schema::hasColumn('subscription_invoice_prefixes', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('current_number');
                }
            });
        }

        if (Schema::hasTable('subscription_invoices')) {
            Schema::table('subscription_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('subscription_invoices', 'business_id')) {
                    $table->unsignedBigInteger('business_id')->nullable()->after('invoice_no');
                }

                if (!Schema::hasColumn('subscription_invoices', 'customer_address')) {
                    $table->text('customer_address')->nullable()->after('customer_code');
                }

                if (!Schema::hasColumn('subscription_invoices', 'payment_method')) {
                    $table->string('payment_method')->nullable()->after('banner_id');
                }
            });
        }

        if (Schema::hasTable('subscription_invoice_items')) {
            Schema::table('subscription_invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('subscription_invoice_items', 'setting_id')) {
                    $table->unsignedBigInteger('setting_id')->nullable()->after('invoice_id');
                }

                if (!Schema::hasColumn('subscription_invoice_items', 'cycle')) {
                    $table->string('cycle')->nullable()->after('description');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscription_invoice_items')) {
            Schema::table('subscription_invoice_items', function (Blueprint $table) {
                if (Schema::hasColumn('subscription_invoice_items', 'cycle')) {
                    $table->dropColumn('cycle');
                }

                if (Schema::hasColumn('subscription_invoice_items', 'setting_id')) {
                    $table->dropColumn('setting_id');
                }
            });
        }

        if (Schema::hasTable('subscription_invoices')) {
            Schema::table('subscription_invoices', function (Blueprint $table) {
                if (Schema::hasColumn('subscription_invoices', 'payment_method')) {
                    $table->dropColumn('payment_method');
                }

                if (Schema::hasColumn('subscription_invoices', 'customer_address')) {
                    $table->dropColumn('customer_address');
                }

                if (Schema::hasColumn('subscription_invoices', 'business_id')) {
                    $table->dropColumn('business_id');
                }
            });
        }

        if (Schema::hasTable('subscription_invoice_prefixes')) {
            Schema::table('subscription_invoice_prefixes', function (Blueprint $table) {
                if (Schema::hasColumn('subscription_invoice_prefixes', 'created_by')) {
                    $table->dropColumn('created_by');
                }

                if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id')) {
                    $table->dropColumn('business_id');
                }
            });
        }
    }
};
