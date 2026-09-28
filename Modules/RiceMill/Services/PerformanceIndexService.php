<?php

namespace Modules\RiceMill\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safe, idempotent Rice Mill performance indexes for the ACTIVE database.
 *
 * The caller is responsible for initializing the correct tenant first. This
 * service never switches tenant/business context by itself.
 */
class PerformanceIndexService
{
    /** @return array<string,array<string,array<int,string>>> */
    public function definitions(): array
    {
        return [
            'rcm_paddy_varieties' => [
                'rcm_var_biz_active_name' => ['business_id','active','name'],
                'rcm_var_biz_code' => ['business_id','code'],
            ],
            'rcm_mills' => [
                'rcm_mill_biz_active_name' => ['business_id','active','name'],
            ],
            'rcm_products' => [
                'rcm_prod_biz_active_name' => ['business_id','active','name'],
            ],
            'rcm_paddy_purchases' => [
                'rcm_pp_biz_date' => ['business_id','purchase_date'],
                'rcm_pp_biz_status_id' => ['business_id','status','id'],
            ],
            'rcm_paddy_purchase_lines' => [
                'rcm_ppl_biz_variety' => ['business_id','paddy_variety_id'],
            ],
            'rcm_paddy_receipts' => [
                'rcm_pr_biz_received' => ['business_id','received_at'],
                'rcm_pr_biz_variety' => ['business_id','paddy_variety_id'],
            ],
            'rcm_paddy_lots' => [
                'rcm_pl_biz_balance_date' => ['business_id','balance_qty','received_date'],
                'rcm_pl_biz_loc_store_bal' => ['business_id','location_id','store_id','balance_qty'],
                'rcm_pl_biz_variety' => ['business_id','paddy_variety_id'],
            ],
            'rcm_paddy_stock_movements' => [
                'rcm_psm_biz_lot_id' => ['business_id','paddy_lot_id','id'],
                'rcm_psm_biz_loc_store_date' => ['business_id','location_id','store_id','movement_date'],
            ],
            'rcm_production_batches' => [
                'rcm_pb_biz_id' => ['business_id','id'],
                'rcm_pb_biz_status_complete' => ['business_id','status','completed_at'],
                'rcm_pb_biz_loc_store_complete' => ['business_id','location_id','store_id','completed_at'],
            ],
            'rcm_production_outputs' => [
                'rcm_po_biz_product' => ['business_id','product_id'],
            ],
            'rcm_byproduct_movements' => [
                'rcm_bpm_biz_type_id' => ['business_id','byproduct_type','id'],
            ],
            'rcm_packing_batches' => [
                'rcm_pack_biz_id' => ['business_id','id'],
            ],
            'rcm_packing_lines' => [
                'rcm_packline_biz_product' => ['business_id','product_id'],
            ],
            'rcm_finished_stock_movements' => [
                'rcm_fsm_biz_product_id' => ['business_id','product_id','id'],
                'rcm_fsm_biz_loc_store_date' => ['business_id','location_id','store_id','movement_date'],
            ],
            'rcm_dispatches' => [
                'rcm_disp_biz_id' => ['business_id','id'],
                'rcm_disp_biz_status_date' => ['business_id','status','dispatch_date'],
                'rcm_disp_biz_loc_store_date' => ['business_id','location_id','store_id','dispatch_date'],
            ],
            'rcm_dispatch_lines' => [
                'rcm_displine_biz_product' => ['business_id','product_id'],
            ],
            'rcm_cost_entries' => [
                'rcm_cost_biz_batch' => ['business_id','production_batch_id'],
            ],
        ];
    }

    /** @return array{created:int,existing:int,missing:int} */
    public function apply(): array
    {
        $result = ['created' => 0, 'existing' => 0, 'missing' => 0];

        foreach ($this->definitions() as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                $result['missing'] += count($indexes);
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (! $this->columnsExist($table, $columns)) {
                    $result['missing']++;
                    continue;
                }
                if ($this->indexExists($table, $name)) {
                    $result['existing']++;
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                    $blueprint->index($columns, $name);
                });
                $result['created']++;
            }
        }

        return $result;
    }

    public function rollback(): void
    {
        foreach (array_reverse($this->definitions(), true) as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (array_reverse($indexes, true) as $name => $columns) {
                if ($this->indexExists($table, $name)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($name) {
                        $blueprint->dropIndex($name);
                    });
                }
            }
        }
    }

    /** @param array<int,string> $columns */
    private function columnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }
        return true;
    }

    private function indexExists(string $table, string $name): bool
    {
        $rows = DB::select('SHOW INDEX FROM `' . str_replace('`', '``', $table) . '`');
        foreach ($rows as $row) {
            $key = $row->Key_name ?? $row->key_name ?? null;
            if ((string) $key === $name) {
                return true;
            }
        }
        return false;
    }
}
