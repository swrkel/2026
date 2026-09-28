<?php
namespace Modules\ManagementReport\Services\Reports\Sections;
use Modules\ManagementReport\Support\TenantConnection;

use Modules\ManagementReport\Support\ReportContext;

class FinancialStatusSectionService extends BaseSectionService
{
    public function key() { return 'financial_status'; }

    public function build(ReportContext $context)
    {
        $categories = [
            'cash' => ['Cash', ['cash']],
            'customer_cheques' => ['Customer Cheques', ['cheque', 'check']],
            'banks' => ['Banks', ['bank']],
            'card' => ['Card', ['card']],
            'credit_sales' => ['Credit Sales', ['receivable', 'debtor', 'credit sales']],
            'account_payable' => ['Account Payable', ['payable', 'creditor', 'current liabilities']],
        ];

        $rows = [];
        foreach ($categories as $key => $definition) {
            list($label, $patterns) = $definition;
            $movement = $this->accountMovement($context, $patterns);
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'previous_balance' => $movement['opening'],
                'total_in' => $movement['in'],
                'total_out' => $movement['out'],
                'balance' => $movement['opening'] + $movement['in'] - $movement['out'],
            ];
        }

        return ['rows' => $rows, 'total_balance' => array_sum(array_column($rows, 'balance'))];
    }

    protected function accountMovement(ReportContext $context, array $patterns)
    {
        $result = ['opening' => 0.0, 'in' => 0.0, 'out' => 0.0];
        if (!$this->schema->table('accounts') || !$this->schema->table('account_transactions')) {
            return $result;
        }

        $date = $this->schema->firstColumn('account_transactions', ['operation_date', 'transaction_date', 'created_at']);
        $amount = $this->schema->firstColumn('account_transactions', ['amount']);
        $flow = $this->schema->firstColumn('account_transactions', ['type', 'transaction_type']);
        if (!$amount || !$date) return $result;

        $base = TenantConnection::db()->table('account_transactions')->join('accounts', 'account_transactions.account_id', '=', 'accounts.id');
        $hasAccountTypes = $this->schema->table('account_types') && $this->schema->column('accounts', 'account_type_id');
        if ($hasAccountTypes) {
            $base->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
        }

        if ($this->schema->column('accounts', 'business_id')) $base->where('accounts.business_id', $context->businessId);
        if ($context->locationId && $this->schema->column('accounts', 'location_id')) $base->where('accounts.location_id', $context->locationId);

        $base->where(function ($query) use ($patterns, $hasAccountTypes) {
            foreach ($patterns as $pattern) {
                if ($this->schema->column('accounts', 'name')) {
                    $query->orWhereRaw('LOWER(accounts.name) LIKE ?', ['%' . strtolower($pattern) . '%']);
                }
                if ($hasAccountTypes && $this->schema->column('account_types', 'name')) {
                    $query->orWhereRaw('LOWER(account_types.name) LIKE ?', ['%' . strtolower($pattern) . '%']);
                }
                if ($this->schema->column('accounts', 'account_type')) {
                    $query->orWhereRaw('LOWER(accounts.account_type) LIKE ?', ['%' . strtolower($pattern) . '%']);
                }
            }
        });

        $openingQuery = clone $base;
        $openingQuery->where('account_transactions.' . $date, '<', $context->startDate);
        if ($flow) {
            $openingQuery->selectRaw("SUM(CASE WHEN LOWER(account_transactions.$flow) IN ('credit','deposit','in') THEN account_transactions.$amount ELSE -account_transactions.$amount END) AS balance");
        } else {
            $openingQuery->selectRaw("SUM(account_transactions.$amount) AS balance");
        }
        $result['opening'] = $this->amount(optional($openingQuery->first())->balance);

        $period = clone $base;
        $period->whereBetween('account_transactions.' . $date, [$context->startDate, $context->endDate]);
        if ($flow) {
            $period->selectRaw("SUM(CASE WHEN LOWER(account_transactions.$flow) IN ('credit','deposit','in') THEN account_transactions.$amount ELSE 0 END) AS total_in")
                ->selectRaw("SUM(CASE WHEN LOWER(account_transactions.$flow) IN ('debit','withdrawal','out') THEN account_transactions.$amount ELSE 0 END) AS total_out");
            $row = $period->first();
            $result['in'] = $this->amount(optional($row)->total_in);
            $result['out'] = $this->amount(optional($row)->total_out);
        } else {
            $result['in'] = $this->amount($period->sum('account_transactions.' . $amount));
        }

        return $result;
    }
}
