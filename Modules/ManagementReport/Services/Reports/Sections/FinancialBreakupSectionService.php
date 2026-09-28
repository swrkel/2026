<?php
namespace Modules\ManagementReport\Services\Reports\Sections;
use Modules\ManagementReport\Support\TenantConnection;

use Illuminate\Support\Facades\DB;
use Modules\ManagementReport\Support\ReportContext;

class FinancialBreakupSectionService extends BaseSectionService
{
    public function key() { return 'financial_breakup'; }

    public function build(ReportContext $context)
    {
        $columns = ['cash', 'cheque', 'bank', 'card', 'credit'];
        $rows = [];
        $activities = [
            'deposited' => ['Deposited', ['deposit', 'credit']],
            'purchase' => ['Purchase', ['purchase']],
            'expenses' => ['Expenses', ['expense']],
            'journal_in' => ['Journal In', ['journal_in', 'credit']],
            'journal_out' => ['Journal Out', ['journal_out', 'debit']],
        ];
        foreach ($activities as $key => $definition) {
            $row = ['key' => $key, 'label' => $definition[0]];
            foreach ($columns as $column) $row[$column] = 0.0;
            $rows[$key] = $row;
        }

        if ($this->schema->table('transaction_payments') && $this->schema->table('transactions')) {
            $amount = $this->schema->firstColumn('transaction_payments', ['amount', 'payment_amount']);
            $method = $this->schema->firstColumn('transaction_payments', ['method', 'payment_method']);
            if ($amount && $method) {
                $query = TenantConnection::db()->table('transaction_payments')
                    ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                    ->where('transactions.business_id', $context->businessId)
                    ->whereBetween('transaction_payments.paid_on', [$context->startDate, $context->endDate])
                    ->select('transactions.type', 'transaction_payments.' . $method . ' AS method', DB::raw('SUM(transaction_payments.' . $amount . ') AS amount'))
                    ->groupBy('transactions.type', 'transaction_payments.' . $method);
                if ($context->locationId && $this->schema->column('transactions', 'location_id')) $query->where('transactions.location_id', $context->locationId);
                foreach ($query->get() as $item) {
                    $activity = in_array($item->type, ['purchase', 'purchase_return']) ? 'purchase' : (in_array($item->type, ['expense']) ? 'expenses' : 'deposited');
                    $channel = $this->normaliseMethod($item->method);
                    if (isset($rows[$activity][$channel])) $rows[$activity][$channel] += (float) $item->amount;
                }
            }
        }

        if ($this->schema->table('account_transactions')) {
            $amount = $this->schema->firstColumn('account_transactions', ['amount']);
            $type = $this->schema->firstColumn('account_transactions', ['type', 'transaction_type']);
            $date = $this->schema->firstColumn('account_transactions', ['operation_date', 'transaction_date', 'created_at']);
            if ($amount && $type && $date) {
                $query = TenantConnection::db()->table('account_transactions')->whereBetween($date, [$context->startDate, $context->endDate]);
                if ($this->schema->column('account_transactions', 'business_id')) $query->where('business_id', $context->businessId);
                $data = $query->select($type . ' AS flow_type', DB::raw('SUM(' . $amount . ') AS amount'))->groupBy($type)->get();
                foreach ($data as $item) {
                    $key = in_array(strtolower($item->flow_type), ['credit', 'deposit', 'in']) ? 'journal_in' : 'journal_out';
                    $rows[$key]['cash'] += (float) $item->amount;
                }
            }
        }

        foreach ($rows as &$row) {
            $row['total'] = array_sum(array_intersect_key($row, array_flip($columns)));
        }
        unset($row);
        return ['rows' => array_values($rows), 'columns' => $columns];
    }

    protected function normaliseMethod($method)
    {
        $method = strtolower((string) $method);
        if (strpos($method, 'cheque') !== false) return 'cheque';
        if (strpos($method, 'bank') !== false || strpos($method, 'transfer') !== false) return 'bank';
        if (strpos($method, 'card') !== false) return 'card';
        if (strpos($method, 'credit') !== false) return 'credit';
        return 'cash';
    }
}
