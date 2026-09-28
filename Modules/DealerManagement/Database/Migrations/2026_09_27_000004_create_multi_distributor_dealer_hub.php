<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dlr_hub_dealers')) {
            Schema::create('dlr_hub_dealers', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('hub_code', 30)->unique();
                $t->string('name');
                $t->string('mobile', 50)->nullable();
                $t->string('email')->nullable();
                $t->text('address')->nullable();
                $t->string('status', 20)->default('active')->index();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('dlr_hub_outlets')) {
            Schema::create('dlr_hub_outlets', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->index();
                $t->string('outlet_code', 30);
                $t->string('name');
                $t->text('address')->nullable();
                $t->string('mobile', 50)->nullable();
                $t->boolean('is_default')->default(false);
                $t->boolean('is_active')->default(true)->index();
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->unique(['hub_dealer_id','outlet_code'], 'dlr_hub_outlet_code_uq');
            });
        }

        if (!Schema::hasTable('dlr_hub_users')) {
            Schema::create('dlr_hub_users', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->index();
                $t->string('name');
                $t->char('login_code', 4);
                $t->string('mobile', 50)->nullable();
                $t->string('email')->nullable();
                $t->string('password');
                $t->string('role_name', 100)->default('Staff');
                $t->json('permissions_json')->nullable();
                $t->boolean('is_hub_admin')->default(false);
                $t->boolean('is_active')->default(true)->index();
                $t->boolean('must_change_password')->default(true);
                $t->dateTime('last_login_at')->nullable();
                $t->dateTime('password_reset_at')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->unique(['hub_dealer_id','login_code'], 'dlr_hub_user_login_uq');
            });
        }

        if (!Schema::hasTable('dlr_hub_user_outlets')) {
            Schema::create('dlr_hub_user_outlets', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_user_id')->index();
                $t->unsignedBigInteger('hub_outlet_id')->index();
                $t->timestamps();
                $t->unique(['hub_user_id','hub_outlet_id'], 'dlr_hub_user_outlet_uq');
            });
        }

        if (!Schema::hasTable('dlr_hub_distributors')) {
            Schema::create('dlr_hub_distributors', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('distributor_code', 30)->unique();
                $t->string('name');
                $t->string('database_name', 120)->index();
                $t->unsignedBigInteger('business_id')->index();
                $t->string('base_url', 500)->nullable();
                $t->string('status', 20)->default('active')->index();
                $t->timestamp('last_seen_at')->nullable();
                $t->timestamps();
                $t->unique(['database_name','business_id'], 'dlr_hub_distributor_db_business_uq');
            });
        }

        if (!Schema::hasTable('dlr_hub_connections')) {
            Schema::create('dlr_hub_connections', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->index();
                $t->unsignedBigInteger('distributor_id')->index();
                $t->unsignedBigInteger('local_dealer_id')->nullable()->index();
                $t->string('status', 30)->default('pending')->index();
                $t->string('invitation_code', 12)->nullable()->unique();
                $t->string('connection_source', 30)->default('distributor_invite');
                $t->dateTime('invited_at')->nullable();
                $t->dateTime('approved_at')->nullable();
                $t->unsignedBigInteger('approved_by_hub_user_id')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->unique(['hub_dealer_id','distributor_id'], 'dlr_hub_connection_uq');
            });
        }

        if (!Schema::hasTable('dlr_hub_product_sources')) {
            Schema::create('dlr_hub_product_sources', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->index();
                $t->unsignedBigInteger('hub_outlet_id')->index();
                $t->unsignedBigInteger('distributor_id')->index();
                $t->unsignedBigInteger('local_dealer_id')->nullable();
                $t->unsignedBigInteger('local_outlet_id')->nullable();
                $t->unsignedBigInteger('local_product_id')->index();
                $t->unsignedBigInteger('local_variation_id')->nullable()->index();
                $t->string('product_key', 191)->index();
                $t->string('product_name');
                $t->string('sku', 100)->nullable();
                $t->string('allocation_method', 30)->default('fifo');
                $t->decimal('source_qty', 22, 4)->default(0);
                $t->decimal('reorder_level', 22, 4)->default(0);
                $t->boolean('is_active')->default(true)->index();
                $t->dateTime('last_synced_at')->nullable();
                $t->timestamps();
                $t->unique(['hub_dealer_id','hub_outlet_id','distributor_id','local_product_id','local_variation_id'], 'dlr_hub_product_source_uq');
                $t->index(['hub_dealer_id','hub_outlet_id','product_key'], 'dlr_hub_product_key_idx');
            });
        }

        if (!Schema::hasTable('dlr_hub_sales_entries')) {
            Schema::create('dlr_hub_sales_entries', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->index();
                $t->unsignedBigInteger('hub_outlet_id')->index();
                $t->string('entry_no', 60)->unique();
                $t->date('sale_date')->index();
                $t->string('status', 30)->default('posted')->index();
                $t->text('notes')->nullable();
                $t->unsignedBigInteger('submitted_by')->index();
                $t->dateTime('submitted_at');
                $t->timestamps();
                $t->index(['hub_dealer_id','hub_outlet_id','sale_date'], 'dlr_hub_sales_scope_idx');
            });
        }

        if (!Schema::hasTable('dlr_hub_sales_entry_lines')) {
            Schema::create('dlr_hub_sales_entry_lines', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('sales_entry_id')->index();
                $t->string('product_key', 191)->index();
                $t->string('product_name');
                $t->string('sku', 100)->nullable();
                $t->decimal('opening_qty', 22, 4)->default(0);
                $t->decimal('received_qty', 22, 4)->default(0);
                $t->decimal('sold_qty', 22, 4)->default(0);
                $t->decimal('return_qty', 22, 4)->default(0);
                $t->decimal('damaged_qty', 22, 4)->default(0);
                $t->decimal('closing_qty', 22, 4)->default(0);
                $t->string('allocation_method', 30)->default('fifo');
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('dlr_hub_sales_allocations')) {
            Schema::create('dlr_hub_sales_allocations', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('sales_entry_line_id')->index();
                $t->unsignedBigInteger('distributor_id')->index();
                $t->unsignedBigInteger('product_source_id')->index();
                $t->decimal('allocated_sold_qty', 22, 4)->default(0);
                $t->decimal('allocated_return_qty', 22, 4)->default(0);
                $t->decimal('allocated_damage_qty', 22, 4)->default(0);
                $t->string('allocation_method', 30);
                $t->string('sync_status', 30)->default('pending')->index();
                $t->text('sync_error')->nullable();
                $t->dateTime('synced_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('dlr_hub_orders')) {
            Schema::create('dlr_hub_orders', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->index();
                $t->unsignedBigInteger('hub_outlet_id')->index();
                $t->string('hub_order_no', 60)->unique();
                $t->date('order_date')->index();
                $t->date('requested_delivery_date')->nullable();
                $t->string('status', 30)->default('submitted')->index();
                $t->text('notes')->nullable();
                $t->unsignedBigInteger('submitted_by')->index();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('dlr_hub_order_lines')) {
            Schema::create('dlr_hub_order_lines', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_order_id')->index();
                $t->string('product_key', 191)->index();
                $t->string('product_name');
                $t->unsignedBigInteger('preferred_distributor_id')->nullable()->index();
                $t->decimal('current_qty', 22, 4)->default(0);
                $t->decimal('suggested_qty', 22, 4)->default(0);
                $t->decimal('requested_qty', 22, 4)->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('dlr_hub_split_orders')) {
            Schema::create('dlr_hub_split_orders', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_order_id')->index();
                $t->unsignedBigInteger('distributor_id')->index();
                $t->unsignedBigInteger('local_order_id')->nullable()->index();
                $t->string('local_order_no', 60)->nullable();
                $t->string('status', 30)->default('pending')->index();
                $t->json('payload_json')->nullable();
                $t->text('sync_error')->nullable();
                $t->dateTime('synced_at')->nullable();
                $t->timestamps();
                $t->unique(['hub_order_id','distributor_id'], 'dlr_hub_split_order_uq');
            });
        }

        if (!Schema::hasTable('dlr_hub_notifications')) {
            Schema::create('dlr_hub_notifications', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->index();
                $t->unsignedBigInteger('hub_user_id')->nullable()->index();
                $t->unsignedBigInteger('distributor_id')->nullable()->index();
                $t->string('type', 50)->index();
                $t->string('severity', 20)->default('info');
                $t->string('title');
                $t->text('message');
                $t->string('action_url', 500)->nullable();
                $t->boolean('is_read')->default(false)->index();
                $t->dateTime('read_at')->nullable();
                $t->timestamps();
                $t->index(['hub_dealer_id','is_read','created_at'], 'dlr_hub_notification_scope_idx');
            });
        }

        if (!Schema::hasTable('dlr_hub_delivery_feed')) {
            Schema::create('dlr_hub_delivery_feed', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->index();
                $t->unsignedBigInteger('hub_outlet_id')->nullable()->index();
                $t->unsignedBigInteger('distributor_id')->index();
                $t->string('reference_no', 100)->nullable()->index();
                $t->dateTime('delivery_at')->nullable()->index();
                $t->string('status', 30)->default('delivered');
                $t->json('payload_json')->nullable();
                $t->timestamps();
                $t->unique(['distributor_id','reference_no'], 'dlr_hub_delivery_ref_uq');
            });
        }

        if (!Schema::hasTable('dlr_hub_return_feed')) {
            Schema::create('dlr_hub_return_feed', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->index();
                $t->unsignedBigInteger('hub_outlet_id')->nullable()->index();
                $t->unsignedBigInteger('distributor_id')->index();
                $t->string('reference_no', 100)->nullable()->index();
                $t->dateTime('return_at')->nullable()->index();
                $t->string('status', 30)->default('processed');
                $t->json('payload_json')->nullable();
                $t->timestamps();
                $t->unique(['distributor_id','reference_no'], 'dlr_hub_return_ref_uq');
            });
        }

        if (!Schema::hasTable('dlr_hub_audit_logs')) {
            Schema::create('dlr_hub_audit_logs', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('hub_dealer_id')->nullable()->index();
                $t->unsignedBigInteger('hub_user_id')->nullable()->index();
                $t->string('event', 100)->index();
                $t->string('entity_type', 100)->nullable();
                $t->unsignedBigInteger('entity_id')->nullable();
                $t->string('ip_address', 64)->nullable();
                $t->string('user_agent', 500)->nullable();
                $t->json('old_values')->nullable();
                $t->json('new_values')->nullable();
                $t->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasColumn('dlr_dealers','hub_dealer_id')) {
            Schema::table('dlr_dealers', function (Blueprint $t) {
                $t->unsignedBigInteger('hub_dealer_id')->nullable()->after('customer_id')->index();
            });
        }
        if (!Schema::hasColumn('dlr_outlets','hub_outlet_id')) {
            Schema::table('dlr_outlets', function (Blueprint $t) {
                $t->unsignedBigInteger('hub_outlet_id')->nullable()->after('dealer_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('dlr_outlets','hub_outlet_id')) Schema::table('dlr_outlets', fn(Blueprint $t) => $t->dropColumn('hub_outlet_id'));
        if (Schema::hasColumn('dlr_dealers','hub_dealer_id')) Schema::table('dlr_dealers', fn(Blueprint $t) => $t->dropColumn('hub_dealer_id'));
        foreach ([
            'dlr_hub_audit_logs','dlr_hub_return_feed','dlr_hub_delivery_feed','dlr_hub_notifications',
            'dlr_hub_split_orders','dlr_hub_order_lines','dlr_hub_orders','dlr_hub_sales_allocations',
            'dlr_hub_sales_entry_lines','dlr_hub_sales_entries','dlr_hub_product_sources','dlr_hub_connections',
            'dlr_hub_distributors','dlr_hub_user_outlets','dlr_hub_users','dlr_hub_outlets','dlr_hub_dealers'
        ] as $table) Schema::dropIfExists($table);
    }
};
