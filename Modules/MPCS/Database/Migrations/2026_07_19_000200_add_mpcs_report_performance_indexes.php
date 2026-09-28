<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMpcsReportPerformanceIndexes extends Migration
{
    public function up()
    {
        $this->addIndex('transactions', 'idx_mpcs_txn_biz_type_date_loc', [
            'business_id', 'type', 'transaction_date', 'location_id',
        ]);
        $this->addIndex('transactions', 'idx_mpcs_txn_biz_credit_date', [
            'business_id', 'is_credit_sale', 'payment_status', 'transaction_date',
        ]);
        $this->addIndex('transactions', 'idx_mpcs_txn_credit_sale_id', ['credit_sale_id']);
        $this->addIndex('transactions', 'idx_mpcs_txn_petro_settlement_id', ['petro_settlement_id']);
        $this->addIndex('transactions', 'idx_mpcs_txn_biz_updated', ['business_id', 'updated_at', 'id']);

        $this->addIndex('transaction_sell_lines', 'idx_mpcs_tsl_txn_product_var', [
            'transaction_id', 'product_id', 'variation_id',
        ]);
        $this->addIndex('transaction_payments', 'idx_mpcs_tp_txn_deleted', [
            'transaction_id', 'deleted_at',
        ]);

        $this->addIndex('settlement_credit_sale_payments', 'idx_mpcs_scsp_biz_order_product', [
            'business_id', 'order_date', 'product_id', 'transaction_id',
        ]);
        $this->addIndex('settlement_credit_sale_payments', 'idx_mpcs_scsp_biz_created_product', [
            'business_id', 'created_at', 'product_id',
        ]);
        $this->addIndex('settlement_credit_sale_payments', 'idx_mpcs_scsp_biz_updated', [
            'business_id', 'updated_at', 'id',
        ]);

        $this->addIndex('products', 'idx_mpcs_products_biz_filters', [
            'business_id', 'category_id', 'sub_category_id', 'id',
        ]);
        $this->addIndex('products', 'idx_mpcs_products_biz_updated', [
            'business_id', 'updated_at', 'id',
        ]);

        $this->addIndex('mpcs_9c_cash_form_settings', 'idx_mpcs_9ccash_biz_date', [
            'business_id', 'date_time', 'id',
        ]);
        $this->addIndex('mpcs_9c_credit_form_settings', 'idx_mpcs_9ccredit_biz_date', [
            'business_id', 'date_time', 'id',
        ]);
        $this->addIndex('mpcs_9a_form_settings', 'idx_mpcs_9a_biz_date', [
            'business_id', 'date', 'id',
        ]);
    }

    public function down()
    {
        $this->dropIndex('transactions', 'idx_mpcs_txn_biz_type_date_loc');
        $this->dropIndex('transactions', 'idx_mpcs_txn_biz_credit_date');
        $this->dropIndex('transactions', 'idx_mpcs_txn_credit_sale_id');
        $this->dropIndex('transactions', 'idx_mpcs_txn_petro_settlement_id');
        $this->dropIndex('transactions', 'idx_mpcs_txn_biz_updated');
        $this->dropIndex('transaction_sell_lines', 'idx_mpcs_tsl_txn_product_var');
        $this->dropIndex('transaction_payments', 'idx_mpcs_tp_txn_deleted');
        $this->dropIndex('settlement_credit_sale_payments', 'idx_mpcs_scsp_biz_order_product');
        $this->dropIndex('settlement_credit_sale_payments', 'idx_mpcs_scsp_biz_created_product');
        $this->dropIndex('settlement_credit_sale_payments', 'idx_mpcs_scsp_biz_updated');
        $this->dropIndex('products', 'idx_mpcs_products_biz_filters');
        $this->dropIndex('products', 'idx_mpcs_products_biz_updated');
        $this->dropIndex('mpcs_9c_cash_form_settings', 'idx_mpcs_9ccash_biz_date');
        $this->dropIndex('mpcs_9c_credit_form_settings', 'idx_mpcs_9ccredit_biz_date');
        $this->dropIndex('mpcs_9a_form_settings', 'idx_mpcs_9a_biz_date');
    }

    private function addIndex($tableName, $indexName, array $columns)
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable $exception) {
            // Some legacy tenant schemas use a TEXT order_date or already contain an
            // equivalent unnamed index. A single optional index must not stop deployment.
        }
    }

    private function dropIndex($tableName, $indexName)
    {
        if (! Schema::hasTable($tableName) || ! $this->indexExists($tableName, $indexName)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Throwable $exception) {
            // Safe rollback on heterogeneous tenant schemas.
        }
    }

    private function indexExists($tableName, $indexName)
    {
        try {
            return ! empty(DB::select(
                'SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '` WHERE Key_name = ?',
                [$indexName]
            ));
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
