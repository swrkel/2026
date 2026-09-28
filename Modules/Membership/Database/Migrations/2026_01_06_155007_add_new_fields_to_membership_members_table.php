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
        Schema::table('membership_members', function (Blueprint $table) {
            // Add new fields if they don't exist
            if (!Schema::hasColumn('membership_members', 'title')) {
                $table->string('title', 20)->nullable()->after('member_name');
            }
            if (!Schema::hasColumn('membership_members', 'member_address')) {
                $table->text('member_address')->nullable()->after('member_name');
            }
            if (!Schema::hasColumn('membership_members', 'date_joined')) {
                $table->date('date_joined')->nullable()->after('member_address');
            }
            if (!Schema::hasColumn('membership_members', 'nic_no')) {
                $table->string('nic_no', 50)->nullable()->after('date_joined');
            }
            if (!Schema::hasColumn('membership_members', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('nic_no');
            }
            if (!Schema::hasColumn('membership_members', 'gender')) {
                $table->enum('gender', ['Male', 'Female'])->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('membership_members', 'membership_code')) {
                $table->string('membership_code', 100)->nullable()->after('member_number');
            }
            if (!Schema::hasColumn('membership_members', 'membership_type_id')) {
                $table->unsignedBigInteger('membership_type_id')->nullable()->after('membership_business_type_id');
                // Foreign key will be added after membership_types table exists
                // $table->foreign('membership_type_id')->references('id')->on('membership_types')->onDelete('set null');
            }
            if (!Schema::hasColumn('membership_members', 'no_of_shares')) {
                $table->integer('no_of_shares')->default(0)->after('membership_type_id');
            }
            if (!Schema::hasColumn('membership_members', 'total_share_value')) {
                $table->decimal('total_share_value', 15, 2)->default(0)->after('no_of_shares');
            }
            if (!Schema::hasColumn('membership_members', 'membership_status_id')) {
                $table->unsignedBigInteger('membership_status_id')->nullable()->after('total_share_value');
                // Foreign key will be added after membership_statuses table exists
                // $table->foreign('membership_status_id')->references('id')->on('membership_statuses')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_members', function (Blueprint $table) {
            if (Schema::hasColumn('membership_members', 'membership_status_id')) {
                // Drop foreign key if it exists
                try {
                    $table->dropForeign(['membership_status_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('membership_status_id');
            }
            if (Schema::hasColumn('membership_members', 'total_share_value')) {
                $table->dropColumn('total_share_value');
            }
            if (Schema::hasColumn('membership_members', 'no_of_shares')) {
                $table->dropColumn('no_of_shares');
            }
            if (Schema::hasColumn('membership_members', 'membership_type_id')) {
                // Drop foreign key if it exists
                try {
                    $table->dropForeign(['membership_type_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('membership_type_id');
            }
            if (Schema::hasColumn('membership_members', 'membership_code')) {
                $table->dropColumn('membership_code');
            }
            if (Schema::hasColumn('membership_members', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('membership_members', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
            if (Schema::hasColumn('membership_members', 'nic_no')) {
                $table->dropColumn('nic_no');
            }
            if (Schema::hasColumn('membership_members', 'date_joined')) {
                $table->dropColumn('date_joined');
            }
            if (Schema::hasColumn('membership_members', 'member_address')) {
                $table->dropColumn('member_address');
            }
            if (Schema::hasColumn('membership_members', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};
