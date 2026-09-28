<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rn_kitchen_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('print_kot')->default(true);
            $table->boolean('show_on_kds')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rn_order_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('name');
            $table->string('slug');
            $table->boolean('requires_table')->default(false);
            $table->boolean('requires_customer')->default(false);
            $table->boolean('allow_delivery')->default(false);
            $table->decimal('default_service_charge_percent', 10, 4)->default(0);
            $table->decimal('default_delivery_charge', 22, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rn_numbering_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('document_type');
            $table->string('prefix')->nullable();
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedInteger('padding')->default(5);
            $table->string('suffix')->nullable();
            $table->boolean('reset_yearly')->default(false);
            $table->timestamps();
        });

        Schema::table('rn_dining_areas', function (Blueprint $table) {
            if (!Schema::hasColumn('rn_dining_areas', 'code')) {
                $table->string('code')->nullable()->after('name');
            }
            if (!Schema::hasColumn('rn_dining_areas', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('code');
            }
        });

        Schema::table('rn_tables', function (Blueprint $table) {
            if (!Schema::hasColumn('rn_tables', 'table_code')) {
                $table->string('table_code')->nullable()->after('name');
            }
            if (!Schema::hasColumn('rn_tables', 'qr_code')) {
                $table->string('qr_code')->nullable()->after('table_code');
            }
            if (!Schema::hasColumn('rn_tables', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('status');
            }
            if (!Schema::hasColumn('rn_tables', 'notes')) {
                $table->text('notes')->nullable()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rn_numbering_sequences');
        Schema::dropIfExists('rn_order_types');
        Schema::dropIfExists('rn_kitchen_sections');
    }
};
