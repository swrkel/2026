<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
            ALTER TABLE form_f22_details
                MODIFY current_stock DECIMAL(15,5) NULL,
                MODIFY stock_count DECIMAL(15,5) NULL,
                MODIFY unit_purchase_price DECIMAL(20,5) NULL,
                MODIFY unit_sale_price DECIMAL(20,5) NULL,
                MODIFY purchase_price_total DECIMAL(20,5) NULL,
                MODIFY sales_price_total DECIMAL(20,5) NULL,
                MODIFY difference_qty DECIMAL(15,5) NULL,
                MODIFY difference_value DECIMAL(15,5) NULL
        ");
    }

    public function down()
    {
        DB::statement("
            ALTER TABLE form_f22_details
                MODIFY current_stock DECIMAL(15,0) NULL,
                MODIFY stock_count DECIMAL(15,0) NULL,
                MODIFY unit_purchase_price DECIMAL(15,0) NULL,
                MODIFY unit_sale_price DECIMAL(15,0) NULL,
                MODIFY purchase_price_total DECIMAL(15,0) NULL,
                MODIFY sales_price_total DECIMAL(15,0) NULL,
                MODIFY difference_qty DECIMAL(15,0) NULL,
                MODIFY difference_value DECIMAL(10,5) NOT NULL
        ");
    }
};
