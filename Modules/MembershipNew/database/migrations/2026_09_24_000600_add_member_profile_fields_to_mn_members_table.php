<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('mn_members')) {
            return;
        }

        $columns = [
            'title', 'region_id', 'other_mobile_nos', 'business_name', 'membership_type_id',
            'no_of_shares', 'total_share_value', 'gender',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('mn_members', $column)) {
                continue;
            }

            Schema::table('mn_members', function (Blueprint $table) use ($column) {
                switch ($column) {
                    case 'title':
                        $table->string('title', 30)->nullable()->after('member_code');
                        break;
                    case 'region_id':
                        $table->unsignedBigInteger('region_id')->nullable()->index()->after('full_name_second_language');
                        break;
                    case 'other_mobile_nos':
                        $table->text('other_mobile_nos')->nullable()->after('mobile');
                        break;
                    case 'business_name':
                        $table->string('business_name', 191)->nullable()->after('other_mobile_nos');
                        break;
                    case 'membership_type_id':
                        $table->unsignedBigInteger('membership_type_id')->nullable()->index()->after('business_name');
                        break;
                    case 'no_of_shares':
                        $table->decimal('no_of_shares', 22, 4)->nullable()->after('membership_type_id');
                        break;
                    case 'total_share_value':
                        $table->decimal('total_share_value', 22, 4)->nullable()->after('no_of_shares');
                        break;
                    case 'gender':
                        $table->string('gender', 30)->nullable()->after('total_share_value');
                        break;
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('mn_members')) {
            return;
        }

        foreach (['gender', 'total_share_value', 'no_of_shares', 'membership_type_id', 'business_name', 'other_mobile_nos', 'region_id', 'title'] as $column) {
            if (!Schema::hasColumn('mn_members', $column)) {
                continue;
            }

            Schema::table('mn_members', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
