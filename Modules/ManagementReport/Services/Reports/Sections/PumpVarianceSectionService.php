<?php
namespace Modules\ManagementReport\Services\Reports\Sections;
use Modules\ManagementReport\Support\TenantConnection;

use Illuminate\Support\Facades\DB;
use Modules\ManagementReport\Support\ReportContext;

class PumpVarianceSectionService extends BaseSectionService
{
    public function key() { return 'pump_variance'; }

    public function build(ReportContext $context)
    {
        $rows = [];
        if (!$this->schema->table('pump_operator_payments')) return ['rows' => [], 'shortage_total' => 0.0, 'excess_total' => 0.0];

        $amount = $this->schema->firstColumn('pump_operator_payments', ['amount', 'payment_amount']);
        $type = $this->schema->firstColumn('pump_operator_payments', ['payment_type', 'type']);
        $operator = $this->schema->firstColumn('pump_operator_payments', ['pump_operator_id', 'pump_operators_id', 'user_id']);
        if (!$amount || !$type) return ['rows' => [], 'shortage_total' => 0.0, 'excess_total' => 0.0];

        $query = TenantConnection::db()->table('pump_operator_payments');
        $this->applyBusinessScope($query, 'pump_operator_payments', $context);
        $this->applyDate($query, 'pump_operator_payments', $context);
        $selectOperator = $operator ? DB::raw('pump_operator_payments.' . $operator . ' AS operator_id') : DB::raw('0 AS operator_id');
        $data = $query->whereIn('pump_operator_payments.' . $type, ['shortage', 'excess'])
            ->select($selectOperator, DB::raw('pump_operator_payments.' . $type . ' AS variance_type'), DB::raw('SUM(pump_operator_payments.' . $amount . ') AS amount'))
            ->groupBy('operator_id', 'variance_type')->get();

        $grouped = [];
        foreach ($data as $item) {
            $id = (int) $item->operator_id;
            if (!isset($grouped[$id])) $grouped[$id] = ['operator' => 'Operator ' . ($id ?: 'N/A'), 'shortage' => 0.0, 'excess' => 0.0];
            $grouped[$id][strtolower($item->variance_type)] += (float) $item->amount;
        }

        return [
            'rows' => array_values($grouped),
            'shortage_total' => array_sum(array_column($grouped, 'shortage')),
            'excess_total' => array_sum(array_column($grouped, 'excess')),
        ];
    }
}
