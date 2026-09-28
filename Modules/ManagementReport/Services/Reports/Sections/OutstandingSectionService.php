<?php
namespace Modules\ManagementReport\Services\Reports\Sections;
use Modules\ManagementReport\Support\TenantConnection;

use Modules\ManagementReport\Support\ReportContext;

class OutstandingSectionService extends BaseSectionService
{
    public function key() { return 'outstanding'; }

    public function build(ReportContext $context)
    {
        $rows = [];
        foreach ([
            ['customer', 'Customer Outstanding', ['sell', 'pos']],
            ['supplier', 'Supplier Outstanding', ['purchase']],
        ] as $definition) {
            list($key, $label, $types) = $definition;
            $amount = $this->outstanding($context, $types);
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'opening' => $amount['opening'],
                'increase' => $amount['invoiced'],
                'settled' => $amount['paid'],
                'balance' => $amount['balance'],
            ];
        }

        $cheques = $this->pendingCheques($context);
        $rows[] = ['key' => 'pending_cheques', 'label' => 'Pending Cheques', 'opening' => 0.0, 'increase' => $cheques, 'settled' => 0.0, 'balance' => $cheques];

        return ['rows' => $rows, 'total' => array_sum(array_column($rows, 'balance'))];
    }

    protected function outstanding(ReportContext $context, array $types)
    {
        $result = ['opening' => 0.0, 'invoiced' => 0.0, 'paid' => 0.0, 'balance' => 0.0];
        if (!$this->schema->table('transactions')) return $result;

        $invoiceBase = TenantConnection::db()->table('transactions')->where('business_id', $context->businessId)->whereIn('type', $types);
        if ($this->schema->column('transactions', 'status')) $invoiceBase->where('status', 'final');
        if ($context->locationId && $this->schema->column('transactions', 'location_id')) $invoiceBase->where('location_id', $context->locationId);
        if ($context->storeId && $this->schema->column('transactions', 'store_id')) $invoiceBase->where('store_id', $context->storeId);

        $openingInvoices = (clone $invoiceBase)->where('transaction_date', '<', $context->startDate)->sum('final_total');
        $periodInvoices = (clone $invoiceBase)->whereBetween('transaction_date', [$context->startDate, $context->endDate])->sum('final_total');

        $openingPaid = 0.0;
        $periodPaid = 0.0;
        if ($this->schema->table('transaction_payments')) {
            $amount = $this->schema->firstColumn('transaction_payments', ['amount', 'payment_amount']);
            $paidOn = $this->schema->firstColumn('transaction_payments', ['paid_on', 'created_at', 'date']);
            if ($amount && $paidOn) {
                $paymentBase = TenantConnection::db()->table('transaction_payments')
                    ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                    ->where('transactions.business_id', $context->businessId)
                    ->whereIn('transactions.type', $types);
                if ($context->locationId && $this->schema->column('transactions', 'location_id')) $paymentBase->where('transactions.location_id', $context->locationId);
                if ($context->storeId && $this->schema->column('transactions', 'store_id')) $paymentBase->where('transactions.store_id', $context->storeId);
                $openingPaid = (clone $paymentBase)->where('transaction_payments.' . $paidOn, '<', $context->startDate)->sum('transaction_payments.' . $amount);
                $periodPaid = (clone $paymentBase)->whereBetween('transaction_payments.' . $paidOn, [$context->startDate, $context->endDate])->sum('transaction_payments.' . $amount);
            }
        }

        $result['opening'] = $this->amount($openingInvoices - $openingPaid);
        $result['invoiced'] = $this->amount($periodInvoices);
        $result['paid'] = $this->amount($periodPaid);
        $result['balance'] = $this->amount($result['opening'] + $result['invoiced'] - $result['paid']);
        return $result;
    }

    protected function pendingCheques(ReportContext $context)
    {
        foreach (['daily_cheque_payments', 'cheque_payments'] as $table) {
            if (!$this->schema->table($table)) {
                continue;
            }

            $amount = $this->schema->firstColumn($table, ['amount', 'cheque_amount']);
            $date = $this->schema->firstColumn($table, ['cheque_date', 'payment_date', 'date', 'created_at']);
            if (!$amount || !$date) {
                continue;
            }

            $query = TenantConnection::db()->table($table);
            $this->applyBusinessScope($query, $table, $context);
            $query->where($table . '.' . $date, '<=', $context->endDate);

            $status = $this->schema->firstColumn($table, ['status', 'cheque_status']);
            if ($status) {
                $completionDate = $this->schema->firstColumn($table, [
                    'cleared_on', 'cleared_date', 'deposited_on', 'deposited_date',
                    'cancelled_on', 'cancelled_date', 'status_date', 'updated_at',
                ]);

                $closedStatuses = ['deposited', 'cleared', 'cancelled', 'canceled', 'returned'];
                $query->where(function ($outer) use ($table, $status, $completionDate, $closedStatuses, $context) {
                    $outer->whereNotIn($table . '.' . $status, $closedStatuses);
                    if ($completionDate) {
                        // A cheque that was cleared/deposited after the selected
                        // historical date was still pending on that date.
                        $outer->orWhere(function ($later) use ($table, $status, $completionDate, $closedStatuses, $context) {
                            $later->whereIn($table . '.' . $status, $closedStatuses)
                                ->where($table . '.' . $completionDate, '>', $context->endDate);
                        });
                    }
                });
            }

            return $this->amount($query->sum($table . '.' . $amount));
        }

        return 0.0;
    }
}
