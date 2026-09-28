<?php
namespace Modules\ManagementReport\Services\Reports\Sections;

use Illuminate\Support\Facades\DB;
use Modules\ManagementReport\Support\ReportContext;
use Modules\ManagementReport\Support\TenantConnection;

/**
 * Legacy Add / Less data service.
 *
 * 8053 removes Add / Less from the selectable report sections, but this class is
 * intentionally kept for backwards compatibility with older saved snapshots and
 * to share the same carefully-scoped movement readers with Total Add and Out.
 */
class AddLessSectionService extends BaseSectionService
{
    public function key() { return 'add_less'; }

    public function build(ReportContext $context)
    {
        $add = [
            ['label' => 'Customer payments received', 'amount' => $this->customerPaymentsReceived($context)],
            ['label' => 'Shortage recovered', 'amount' => $this->shortageRecoveredTotal($context)],
            ['label' => 'Withdraw cash from banks', 'amount' => $this->bankWithdrawalTotal($context)],
            ['label' => 'Purchase returned in cash', 'amount' => $this->transactionDocumentTotal($context, ['purchase_return'])],
        ];

        $less = [
            ['label' => 'Expenses in sales', 'amount' => $this->expenseTotal($context)],
            ['label' => 'Excess and commission paid', 'amount' => $this->excessCommissionPaidTotal($context)],
            ['label' => 'Sales returned', 'amount' => $this->transactionDocumentTotal($context, ['sell_return'])],
            ['label' => 'Supplier payments', 'amount' => $this->paymentTotal($context, ['purchase'])],
            ['label' => 'Purchases', 'amount' => $this->transactionDocumentTotal($context, ['purchase'])],
        ];

        return [
            'add' => $add,
            'less' => $less,
            'add_total' => $this->amount(array_sum(array_column($add, 'amount'))),
            'less_total' => $this->amount(array_sum(array_column($less, 'amount'))),
        ];
    }

    /**
     * Sum genuine customer receipts from both normal customer-payment flows and
     * settlement Customer Payment tabs. Allocated child rows are excluded so a
     * single receipt is never counted once per invoice.
     */
    protected function customerPaymentsReceived(ReportContext $context)
    {
        return $this->amount(
            $this->normalCustomerPaymentReceipts($context)
            + $this->settlementCustomerPaymentReceipts($context)
        );
    }

    protected function normalCustomerPaymentReceipts(ReportContext $context)
    {
        if (!$this->schema->table('transaction_payments')) return 0.0;
        $amount = $this->schema->firstColumn('transaction_payments', ['amount', 'payment_amount']);
        $paidOn = $this->schema->firstColumn('transaction_payments', ['paid_on', 'created_at']);
        if (!$amount || !$paidOn) return 0.0;

        $query = TenantConnection::db()->table('transaction_payments');
        if ($this->schema->column('transaction_payments', 'business_id')) {
            $query->where('transaction_payments.business_id', $context->businessId);
        }
        // Payment dates are timestamps on many tenants. Use date boundaries so
        // payments made later on the selected end date are not accidentally excluded.
        $query->whereDate('transaction_payments.' . $paidOn, '>=', $context->startDate)
            ->whereDate('transaction_payments.' . $paidOn, '<=', $context->endDate);

        // pay-contact-due creates a parent receipt and child allocations. Only
        // the parent/standalone receipt represents money received once.
        if ($this->schema->column('transaction_payments', 'parent_id')) {
            $query->whereNull('transaction_payments.parent_id');
        }
        if ($this->schema->column('transaction_payments', 'deleted_at')) {
            $query->whereNull('transaction_payments.deleted_at');
        }
        if ($this->schema->column('transaction_payments', 'is_return')) {
            $query->where(function ($q) {
                $q->whereNull('transaction_payments.is_return')->orWhere('transaction_payments.is_return', 0);
            });
        }

        // Explicit customer-receipt entry points are preferred.  A few current
        // standalone Customer screens save the parent receipt without a
        // paid_in_type marker, so also accept an unattached parent payment
        // (transaction_id IS NULL) when payment_for resolves to a real customer.
        // Normal POS checkout payments remain excluded because they are attached
        // to a sale transaction.
        if ($this->schema->column('transaction_payments', 'paid_in_type')) {
            $hasTransactionId = $this->schema->column('transaction_payments', 'transaction_id');
            $query->where(function ($receipt) use ($hasTransactionId) {
                $receipt->whereIn('transaction_payments.paid_in_type', [
                    'customer_page',
                    'all_sale_page',
                    'customer_bulk',
                    'customer_simple',
                ]);

                if ($hasTransactionId) {
                    $receipt->orWhere(function ($standalone) {
                        $standalone->whereNull('transaction_payments.paid_in_type')
                            ->whereNull('transaction_payments.transaction_id');
                    });
                }
            });
        } elseif ($this->schema->column('transaction_payments', 'transaction_id')) {
            // Legacy fallback: a contact-due parent receipt is not attached to
            // an individual sale transaction.
            $query->whereNull('transaction_payments.transaction_id');
        }

        if ($this->schema->column('transaction_payments', 'payment_for')) {
            $query->whereNotNull('transaction_payments.payment_for');
            if ($this->schema->table('contacts') && $this->schema->column('contacts', 'id')) {
                $query->join('contacts', 'contacts.id', '=', 'transaction_payments.payment_for');
                if ($this->schema->column('contacts', 'business_id')) {
                    $query->where('contacts.business_id', $context->businessId);
                }
                if ($this->schema->column('contacts', 'type')) {
                    $query->whereIn('contacts.type', ['customer', 'both']);
                }
            }
        }

        if ($context->locationId && $this->schema->column('transaction_payments', 'location_id')) {
            // Parent customer receipts created from Customer / Pay Due screens
            // are business-scoped but many installations leave location_id NULL.
            // Do not hide those genuine receipts merely because the report is
            // opened with a location filter.
            $query->where(function ($location) use ($context) {
                $location->where('transaction_payments.location_id', $context->locationId)
                    ->orWhereNull('transaction_payments.location_id');
            });
        }
        if ($context->storeId && $this->schema->column('transaction_payments', 'store_id')) {
            $query->where(function ($store) use ($context) {
                $store->where('transaction_payments.store_id', $context->storeId)
                    ->orWhereNull('transaction_payments.store_id');
            });
        }

        return (float) $query->sum('transaction_payments.' . $amount);
    }

    protected function settlementCustomerPaymentReceipts(ReportContext $context)
    {
        if (!$this->schema->table('customer_payments') || !$this->schema->table('settlements')) return 0.0;
        if (!$this->schema->column('settlements', 'status')) return 0.0;

        $amount = $this->schema->firstColumn('customer_payments', ['amount', 'sub_total', 'payment_amount']);
        $paymentSettlement = $this->schema->firstColumn('customer_payments', ['settlement_no', 'settlement_id']);
        $settlementNumber = $this->schema->firstColumn('settlements', ['settlement_no', 'settlement_number']);
        $settlementDate = $this->schema->firstColumn('settlements', ['transaction_date', 'date', 'created_at']);
        if (!$amount || !$paymentSettlement || !$settlementDate) return 0.0;

        $query = TenantConnection::db()->table('customer_payments')
            ->join('settlements', function ($join) use ($paymentSettlement, $settlementNumber) {
                $join->on('settlements.business_id', '=', 'customer_payments.business_id');
                $referenceMatch = 'CAST(customer_payments.' . $paymentSettlement . ' AS CHAR) = CAST(settlements.id AS CHAR)';
                if ($settlementNumber) {
                    $referenceMatch .= ' OR CAST(customer_payments.' . $paymentSettlement . ' AS CHAR) = CAST(settlements.' . $settlementNumber . ' AS CHAR)';
                }
                $join->whereRaw('(' . $referenceMatch . ')');
            })
            ->where('customer_payments.business_id', $context->businessId)
            ->where('settlements.status', 0)
            ->whereDate('settlements.' . $settlementDate, '>=', $context->startDate)
            ->whereDate('settlements.' . $settlementDate, '<=', $context->endDate);

        if ($this->schema->column('customer_payments', 'deleted_at')) {
            $query->whereNull('customer_payments.deleted_at');
        }
        if ($context->locationId && $this->schema->column('settlements', 'location_id')) {
            $query->where('settlements.location_id', $context->locationId);
        }
        if ($context->storeId && $this->schema->column('settlements', 'store_id')) {
            $query->where('settlements.store_id', $context->storeId);
        }

        return (float) $query->sum('customer_payments.' . $amount);
    }

    /**
     * Supplier payments for purchase documents. Every payment method/account is
     * included; the report does not restrict this to Cash/Bank/Card account types.
     */
    protected function paymentTotal(ReportContext $context, array $transactionTypes)
    {
        if (!$this->schema->table('transaction_payments') || !$this->schema->table('transactions')) return 0.0;
        $amount = $this->schema->firstColumn('transaction_payments', ['amount', 'payment_amount']);
        $paidOn = $this->schema->firstColumn('transaction_payments', ['paid_on', 'created_at']);
        if (!$amount || !$paidOn) return 0.0;

        $query = TenantConnection::db()->table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $context->businessId)
            ->whereIn('transactions.type', $transactionTypes)
            ->whereBetween('transaction_payments.' . $paidOn, [$context->startDate, $context->endDate]);

        if ($this->schema->column('transaction_payments', 'deleted_at')) {
            $query->whereNull('transaction_payments.deleted_at');
        }
        if ($this->schema->column('transactions', 'deleted_at')) {
            $query->whereNull('transactions.deleted_at');
        }
        if ($this->schema->column('transaction_payments', 'is_return')) {
            $query->where(function ($q) {
                $q->whereNull('transaction_payments.is_return')->orWhere('transaction_payments.is_return', 0);
            });
        }
        if ($context->locationId && $this->schema->column('transactions', 'location_id')) {
            $query->where('transactions.location_id', $context->locationId);
        }
        if ($context->storeId && $this->schema->column('transactions', 'store_id')) {
            $query->where('transactions.store_id', $context->storeId);
        }

        return $this->amount((float) $query->selectRaw('SUM(ABS(transaction_payments.' . $amount . ')) AS total')->value('total'));
    }

    /**
     * Shortage recovery is saved by Petro PD / Petro General through the shared
     * excess-shortage payment utility. The actual cash movement is the bulk
     * parent payment; child rows only allocate that same receipt against old
     * shortage dues. No payment method or account type is filtered out.
     */
    protected function shortageRecoveredTotal(ReportContext $context)
    {
        return $this->operatorSettlementPaymentTotal($context, 'shortage');
    }

    /**
     * The "Pay excess and commission" actions use the same bulk-parent + child
     * allocation flow as shortage recovery. Count the real parent movement and
     * retain a parentless-allocation fallback for legacy tenants.
     */
    protected function excessCommissionPaidTotal(ReportContext $context)
    {
        return $this->operatorSettlementPaymentTotal($context, 'excess');
    }

    protected function operatorSettlementPaymentTotal(ReportContext $context, $subType)
    {
        if (!$this->schema->table('transactions') || !$this->schema->column('transactions', 'type')) return 0.0;

        /*
         * IS2219: classify the movement by the AUTHORITATIVE parent transaction
         * type, not by a child allocation row and not by a loosely populated
         * sub_type column.
         *
         * TransactionUtil::payAtOnceExcessShortage() saves exactly one parent:
         *   shortage_bulk_payment -> TOTAL ADD / Shortage Recovered
         *   excess_bulk_payment   -> OUT / Excess and Commission Paid
         *
         * The parent is business/location scoped. It is not store scoped, so a
         * Daily Management Report opened for a Store must not make these genuine
         * operator cash movements disappear.
         */
        $bulkType = $subType === 'excess' ? 'excess_bulk_payment' : 'shortage_bulk_payment';
        $bulkAmount = $this->schema->firstColumn('transactions', [
            'final_total',
            'total_before_tax',
            'total_amount',
        ]);
        $transactionDate = $this->schema->firstColumn('transactions', [
            'transaction_date',
            'operation_date',
            'created_at',
        ]);

        if ($bulkAmount && $transactionDate) {
            $bulkQuery = TenantConnection::db()->table('transactions')
                ->where('transactions.business_id', $context->businessId)
                ->where('transactions.type', $bulkType)
                ->whereDate('transactions.' . $transactionDate, '>=', $context->startDate)
                ->whereDate('transactions.' . $transactionDate, '<=', $context->endDate);

            if ($this->schema->column('transactions', 'status')) {
                $bulkQuery->where('transactions.status', 'final');
            }
            if ($this->schema->column('transactions', 'deleted_at')) {
                $bulkQuery->whereNull('transactions.deleted_at');
            }
            if ($context->locationId && $this->schema->column('transactions', 'location_id')) {
                $bulkQuery->where('transactions.location_id', $context->locationId);
            }

            // Read the exact parent rows first.  If they exist they own this
            // movement, even when a tenant happens to have old child allocation
            // rows as well; this prevents a shortage/excess movement being read
            // twice or crossing to the wrong section.
            $parents = $bulkQuery->select([
                'transactions.id',
                DB::raw('COALESCE(transactions.' . $bulkAmount . ',0) AS movement_amount'),
            ])->get();

            if ($parents->isNotEmpty()) {
                $total = 0.0;
                $zeroParentIds = [];

                foreach ($parents as $parent) {
                    $value = abs((float) $parent->movement_amount);
                    if ($value > 0.0000001) {
                        $total += $value;
                    } else {
                        $zeroParentIds[] = (int) $parent->id;
                    }
                }

                // Defensive compatibility: some older builds created the bulk
                // parent but left final_total at zero while the real amount was
                // written to its one parent TransactionPayment. Use that payment
                // only for zero-valued parents; never add allocation children.
                if ($zeroParentIds && $this->schema->table('transaction_payments')) {
                    $paymentAmount = $this->schema->firstColumn('transaction_payments', ['amount', 'payment_amount']);
                    if ($paymentAmount && $this->schema->column('transaction_payments', 'transaction_id')) {
                        $paymentQuery = TenantConnection::db()->table('transaction_payments')
                            ->whereIn('transaction_payments.transaction_id', $zeroParentIds);

                        if ($this->schema->column('transaction_payments', 'deleted_at')) {
                            $paymentQuery->whereNull('transaction_payments.deleted_at');
                        }
                        if ($this->schema->column('transaction_payments', 'parent_id')) {
                            $paymentQuery->whereNull('transaction_payments.parent_id');
                        }
                        if ($this->schema->column('transaction_payments', 'is_return')) {
                            $paymentQuery->where(function ($q) {
                                $q->whereNull('transaction_payments.is_return')
                                    ->orWhere('transaction_payments.is_return', 0);
                            });
                        }

                        $total += (float) $paymentQuery->selectRaw(
                            'SUM(ABS(COALESCE(transaction_payments.' . $paymentAmount . ',0))) AS total'
                        )->value('total');
                    }
                }

                return $this->amount($total);
            }
        }

        /*
         * Compatibility fallback for tenants that genuinely pre-date the bulk
         * parent transaction.  The transaction's sub_type is authoritative in
         * this old structure.  This path is reached ONLY when no bulk parent of
         * the requested type exists in the selected period.
         */
        if (!$this->schema->table('transaction_payments')) return 0.0;
        $amount = $this->schema->firstColumn('transaction_payments', ['amount', 'payment_amount']);
        $paidOn = $this->schema->firstColumn('transaction_payments', ['paid_on', 'created_at']);
        if (!$amount || !$paidOn) return 0.0;

        $legacyQuery = TenantConnection::db()->table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $context->businessId)
            ->whereIn('transactions.type', ['settlement', 'opening_balance'])
            ->whereDate('transaction_payments.' . $paidOn, '>=', $context->startDate)
            ->whereDate('transaction_payments.' . $paidOn, '<=', $context->endDate);

        if ($this->schema->column('transactions', 'sub_type')) {
            $legacyQuery->where('transactions.sub_type', $subType);
        } else {
            // Without sub_type old settlement allocations cannot be safely
            // distinguished as shortage vs excess; returning zero is safer than
            // putting the same money in both report sections.
            return 0.0;
        }
        if ($this->schema->column('transactions', 'status')) {
            $legacyQuery->where('transactions.status', 'final');
        }
        if ($this->schema->column('transaction_payments', 'parent_id')) {
            $legacyQuery->whereNull('transaction_payments.parent_id');
        }
        if ($this->schema->column('transaction_payments', 'deleted_at')) {
            $legacyQuery->whereNull('transaction_payments.deleted_at');
        }
        if ($this->schema->column('transactions', 'deleted_at')) {
            $legacyQuery->whereNull('transactions.deleted_at');
        }
        if ($this->schema->column('transaction_payments', 'is_return')) {
            $legacyQuery->where(function ($q) {
                $q->whereNull('transaction_payments.is_return')
                    ->orWhere('transaction_payments.is_return', 0);
            });
        }
        if ($context->locationId && $this->schema->column('transactions', 'location_id')) {
            $legacyQuery->where('transactions.location_id', $context->locationId);
        }

        return $this->amount((float) $legacyQuery->selectRaw(
            'SUM(ABS(COALESCE(transaction_payments.' . $amount . ',0))) AS total'
        )->value('total'));
    }

    /**
     * Withdraw Cash From Bank = a finalized fund transfer whose destination is
     * a Cash account and whose source is a Bank account. Account classification
     * checks Account Group, Account Type and account name so businesses using
     * different account hierarchies are all supported.
     */
    protected function bankWithdrawalTotal(ReportContext $context)
    {
        if (!$this->schema->table('account_transactions') || !$this->schema->table('accounts')) return 0.0;
        if (!$this->schema->column('account_transactions', 'account_id')) return 0.0;

        $amount = $this->schema->firstColumn('account_transactions', ['amount']);
        $date = $this->schema->firstColumn('account_transactions', ['operation_date', 'transaction_date', 'created_at']);
        $type = $this->schema->firstColumn('account_transactions', ['type', 'transaction_type']);
        if (!$amount || !$date || !$type) return 0.0;

        $query = TenantConnection::db()->table('account_transactions as cash_tx')
            ->join('accounts as cash_account', 'cash_tx.account_id', '=', 'cash_account.id')
            ->where('cash_account.business_id', $context->businessId)
            ->whereBetween('cash_tx.' . $date, [$context->startDate, $context->endDate])
            ->whereRaw('LOWER(cash_tx.' . $type . ") = 'debit'");

        if ($this->schema->column('account_transactions', 'sub_type')) {
            $query->where('cash_tx.sub_type', 'fund_transfer');
        }
        if ($this->schema->column('account_transactions', 'deleted_at')) {
            $query->whereNull('cash_tx.deleted_at');
        }
        if ($context->locationId && $this->schema->column('accounts', 'location_id')) {
            $query->where('cash_account.location_id', $context->locationId);
        }

        $hasGroups = $this->schema->table('account_groups') && $this->schema->column('accounts', 'asset_type');
        $hasTypes = $this->schema->table('account_types') && $this->schema->column('accounts', 'account_type_id');

        if ($hasGroups) {
            $query->leftJoin('account_groups as cash_group', 'cash_account.asset_type', '=', 'cash_group.id');
        }
        if ($hasTypes) {
            $query->leftJoin('account_types as cash_type', 'cash_account.account_type_id', '=', 'cash_type.id');
        }

        // Identify the transfer source. Newer rows have transfer_account_id;
        // older rows can be resolved through the paired transaction id.
        $hasTransferAccount = $this->schema->column('account_transactions', 'transfer_account_id');
        $hasTransferTransaction = $this->schema->column('account_transactions', 'transfer_transaction_id');

        if ($hasTransferAccount) {
            $query->leftJoin('accounts as source_account', 'cash_tx.transfer_account_id', '=', 'source_account.id');
            if ($hasGroups) {
                $query->leftJoin('account_groups as source_group', 'source_account.asset_type', '=', 'source_group.id');
            }
            if ($hasTypes) {
                $query->leftJoin('account_types as source_type', 'source_account.account_type_id', '=', 'source_type.id');
            }
        }

        if ($hasTransferTransaction) {
            $query->leftJoin('account_transactions as source_tx', 'cash_tx.transfer_transaction_id', '=', 'source_tx.id')
                ->leftJoin('accounts as paired_source_account', 'source_tx.account_id', '=', 'paired_source_account.id');
            if ($hasGroups) {
                $query->leftJoin('account_groups as paired_source_group', 'paired_source_account.asset_type', '=', 'paired_source_group.id');
            }
            if ($hasTypes) {
                $query->leftJoin('account_types as paired_source_type', 'paired_source_account.account_type_id', '=', 'paired_source_type.id');
            }
            if ($this->schema->column('account_transactions', 'deleted_at')) {
                $query->where(function ($q) {
                    $q->whereNull('source_tx.id')->orWhereNull('source_tx.deleted_at');
                });
            }
        }

        $query->where(function ($q) use ($hasGroups, $hasTypes) {
            if ($hasGroups) $q->orWhereRaw("LOWER(COALESCE(cash_group.name,'')) LIKE '%cash%'");
            if ($hasTypes) $q->orWhereRaw("LOWER(COALESCE(cash_type.name,'')) LIKE '%cash%'");
            $q->orWhereRaw("LOWER(COALESCE(cash_account.name,'')) = 'cash'")
                ->orWhereRaw("LOWER(COALESCE(cash_account.name,'')) LIKE '%cash account%'");
        });

        if ($hasTransferAccount || $hasTransferTransaction) {
            $query->where(function ($q) use ($hasGroups, $hasTypes, $hasTransferAccount, $hasTransferTransaction) {
                if ($hasTransferAccount) {
                    if ($hasGroups) $q->orWhereRaw("LOWER(COALESCE(source_group.name,'')) LIKE '%bank%'");
                    if ($hasTypes) $q->orWhereRaw("LOWER(COALESCE(source_type.name,'')) LIKE '%bank%'");
                    $q->orWhereRaw("LOWER(COALESCE(source_account.name,'')) LIKE '%bank%'");
                }
                if ($hasTransferTransaction) {
                    if ($hasGroups) $q->orWhereRaw("LOWER(COALESCE(paired_source_group.name,'')) LIKE '%bank%'");
                    if ($hasTypes) $q->orWhereRaw("LOWER(COALESCE(paired_source_type.name,'')) LIKE '%bank%'");
                    $q->orWhereRaw("LOWER(COALESCE(paired_source_account.name,'')) LIKE '%bank%'");
                }
            });
        }

        return $this->amount((float) $query->selectRaw('SUM(ABS(cash_tx.' . $amount . ')) AS total')->value('total'));
    }

    /**
     * Document totals used by the new Total Add / Out sections. Purchases use
     * the normal received/final lifecycle; cancelled/draft rows are not counted.
     */
    protected function transactionDocumentTotal(ReportContext $context, array $types)
    {
        if (!$this->schema->table('transactions')) return 0.0;
        $amount = $this->schema->firstColumn('transactions', ['final_total', 'total_amount']);
        $date = $this->schema->firstColumn('transactions', ['transaction_date', 'date', 'created_at']);
        if (!$amount || !$date || !$this->schema->column('transactions', 'type')) return 0.0;

        $query = TenantConnection::db()->table('transactions')
            ->where('transactions.business_id', $context->businessId)
            ->whereIn('transactions.type', $types)
            ->whereBetween('transactions.' . $date, [$context->startDate, $context->endDate]);

        if ($this->schema->column('transactions', 'status')) {
            $query->whereIn('transactions.status', ['final', 'received']);
        }
        if ($this->schema->column('transactions', 'deleted_at')) {
            $query->whereNull('transactions.deleted_at');
        }
        if ($context->locationId && $this->schema->column('transactions', 'location_id')) {
            $query->where('transactions.location_id', $context->locationId);
        }
        if ($context->storeId && $this->schema->column('transactions', 'store_id')) {
            $query->where('transactions.store_id', $context->storeId);
        }

        return $this->amount((float) $query->selectRaw('SUM(ABS(transactions.' . $amount . ')) AS total')->value('total'));
    }

    /**
     * Include the normal ERP Expense documents plus standalone Expenses-New
     * records when that independent module/table exists in the tenant database.
     */
    protected function expenseTotal(ReportContext $context)
    {
        $total = $this->transactionDocumentTotal($context, ['expense']);

        if (!$this->schema->table('expnew_expenses')) {
            return $total;
        }

        $amount = $this->schema->firstColumn('expnew_expenses', ['total_amount', 'amount']);
        $date = $this->schema->firstColumn('expnew_expenses', ['expense_date', 'transaction_date', 'date', 'created_at']);
        if (!$amount || !$date) return $total;

        $query = TenantConnection::db()->table('expnew_expenses');
        if ($this->schema->column('expnew_expenses', 'business_id')) {
            $query->where('expnew_expenses.business_id', $context->businessId);
        }
        $query->whereBetween('expnew_expenses.' . $date, [$context->startDate, $context->endDate]);

        if ($context->locationId) {
            if ($this->schema->column('expnew_expenses', 'location_id')) {
                $query->where('expnew_expenses.location_id', $context->locationId);
            } elseif ($this->schema->column('expnew_expenses', 'business_location_id')) {
                $query->where('expnew_expenses.business_location_id', $context->locationId);
            }
        }
        if ($context->storeId && $this->schema->column('expnew_expenses', 'store_id')) {
            $query->where('expnew_expenses.store_id', $context->storeId);
        }
        if ($this->schema->column('expnew_expenses', 'deleted_at')) {
            $query->whereNull('expnew_expenses.deleted_at');
        }
        if ($this->schema->column('expnew_expenses', 'status')) {
            $query->where(function ($q) {
                $q->whereNull('expnew_expenses.status')
                    ->orWhereRaw("LOWER(expnew_expenses.status) NOT IN ('cancelled','canceled','void','rejected','deleted')");
            });
        }

        $newExpenses = (float) $query->selectRaw('SUM(ABS(expnew_expenses.' . $amount . ')) AS total')->value('total');
        return $this->amount($total + $newExpenses);
    }

    /**
     * Retained for compatibility with older extensions that may subclass this
     * service. New 8053 totals intentionally use the posted bulk transactions.
     */
    protected function paymentTypeTotal(ReportContext $context, array $types)
    {
        if (!$this->schema->table('pump_operator_payments')) return 0.0;
        $amount = $this->schema->firstColumn('pump_operator_payments', ['amount', 'payment_amount']);
        $type = $this->schema->firstColumn('pump_operator_payments', ['payment_type', 'type']);
        if (!$amount || !$type) return 0.0;
        return $this->sum('pump_operator_payments', $amount, $context, function ($query) use ($type, $types) {
            $query->whereIn('pump_operator_payments.' . $type, $types);
        });
    }

    /**
     * Retained for compatibility with old callers. Unlike the pre-8053 version,
     * this now honours the requested account classification instead of summing
     * every account transaction in the business.
     */
    protected function accountFlow(ReportContext $context, $type, array $accountTypes)
    {
        if (in_array('bank', $accountTypes, true) && strtolower((string) $type) === 'credit') {
            // Historic Add/Less requested "withdraw cash from banks" here. The
            // dedicated reader is more precise than a generic account credit sum.
            return $this->bankWithdrawalTotal($context);
        }

        if (!$this->schema->table('account_transactions')) return 0.0;
        $amount = $this->schema->firstColumn('account_transactions', ['amount']);
        if (!$amount) return 0.0;
        return $this->sum('account_transactions', $amount, $context, function ($query) use ($type) {
            if ($this->schema->column('account_transactions', 'type')) {
                $query->where('account_transactions.type', $type);
            }
        }, 'operation_date');
    }
}
