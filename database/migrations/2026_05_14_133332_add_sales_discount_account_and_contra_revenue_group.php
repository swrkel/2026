<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Account;
use App\Business;
use App\AccountGroup;
use App\AccountType;
use Illuminate\Support\Facades\DB;

class AddSalesDiscountAccountAndContraRevenueGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //uncomment if you need this to update your old data
        // 1. Process all existing businesses
        // $businesses = Business::pluck('id');
        // foreach ($businesses as $business_id) {
        //     Account::ensureSalesDiscountAccount($business_id);
        // }

        // 2. Update default_account_groups for business ID 1 (template)
        // $income_type_id = DB::table('default_account_types')
        //     ->where('business_id', 1)
        //     ->where('name', 'Income')
        //     ->value('id');

        // if ($income_type_id) {
        //     $contra_group_id = DB::table('default_account_groups')
        //         ->where('business_id', 1)
        //         ->where('name', 'Contra Revenue Account')
        //         ->value('id');

        //     if (!$contra_group_id) {
        //         $contra_group_id = DB::table('default_account_groups')->insertGetId([
        //             'business_id' => 1,
        //             'name' => 'Contra Revenue Account',
        //             'account_type_id' => $income_type_id,
        //             'note' => 'Default Contra Revenue Account Group',
        //             'created_at' => now(),
        //             'updated_at' => now()
        //         ]);
        //     }

            // 3. Update default_accounts for business ID 1 (template)
        //     $sales_discount_exists = DB::table('default_accounts')
        //         ->where('business_id', 1)
        //         ->where('name', 'Sales Discount')
        //         ->exists();

        //     if (!$sales_discount_exists) {
        //         DB::table('default_accounts')->insert([
        //             'business_id' => 1,
        //             'name' => 'Sales Discount',
        //             'account_number' => 'SD100',
        //             'account_type_id' => $income_type_id,
        //             'asset_type' => $contra_group_id,
        //             'created_by' => 1,
        //             'is_main_account' => 0,
        //             'is_closed' => 0,
        //             'visible' => 1,
        //             'show_in_balance_sheet' => 1,
        //             'created_at' => now(),
        //             'updated_at' => now()
        //         ]);
        //     } else {
        //         DB::table('default_accounts')
        //             ->where('business_id', 1)
        //             ->where('name', 'Sales Discount')
        //             ->update([
        //                 'account_type_id' => $income_type_id,
        //                 'asset_type' => $contra_group_id,
        //                 'updated_at' => now()
        //             ]);
        //     }
        // }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Optional: logic to revert changes if needed
    }
}
