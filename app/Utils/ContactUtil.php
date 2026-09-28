<?php

namespace App\Utils;

use App\Contact;
use App\ContactGroup;
use App\ContactLedger;
use App\Transaction;
use App\TransactionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Vat\Entities\VatPayment;
use Modules\Vat\Entities\VatPayableToAccount;

class ContactUtil
{

    public function __construct()
    {
    }

    public $payable_customer_txns = ['cheque_return', 'direct_customer_loan', 'customer_loan', 'property_sell', 'route_operation', 'expense', 'sell', 'fpos_sale', 'vat_price_adjustment', 'opening_balance', 'fleet_opening_balance', 'advance_payment', 'settlement'];
    public $payable_supplier_txns = ['cheque_return', 'property_purchase', 'expense', 'opening_balance', 'purchase'];

    public $transaction_types = ['vat_price_adjustment', 'direct_customer_loan', 'fleet_opening_balance', 'cheque_return', 'property_sell', 'route_operation', 'expense', 'sell', 'hms_booking', 'opening_balance', 'sell_return', 'fpos_sale', 'ledger_discount', 'ledger'];
    public $supplier_types = ['cheque_return', 'property_purchase', 'expense', 'opening_balance', 'purchase', 'purchase_return', '_deleted_purchase', 'ledger', 'sell_return'];

    /**
     * Transaction statuses included in supplier running balance / Total Purchase Due.
     * Must match {@see getSupplierLedger()} so the supplier list matches Contact → Ledger.
     */
    public $supplier_balance_statuses = ['final', 'received', 'ordered', 'pending'];

    public $tax_txn_types = ['purchase', 'sell', 'expense', 'vat_penalty'];

    public function getCustomerBalance($contact_id, $business_id, $get_balance = false)
    {
        // Input validation
        if (empty($contact_id) || empty($business_id)) {
            Log::warning('ContactUtil::getCustomerBalance called with invalid parameters', [
                'contact_id' => $contact_id,
                'business_id' => $business_id
            ]);
            return $get_balance ? 0 : array('opening_balance' => 0, 'total_sale' => 0, 'total_paid' => 0, 'total_balance' => 0);
        }

        $balance_details = array('opening_balance' => 0, 'total_sale' => 0, 'total_paid' => 0);

        try {
            $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transactions.business_id', $business_id)
                ->where(function ($query) {
                    $query->whereIn('transactions.type', $this->transaction_types)
                        ->orWhere(function ($query) {
                            $query->where('transactions.type', 'settlement')->where('transactions.sub_type', 'customer_loan');
                        });
                })
                ->where(function ($query) {
                    $query->where('transactions.sub_type', '!=', 'settlement')
                        ->orWhereNull('transactions.sub_type');
                })
                ->whereNull('transactions.deleted_at')
                ->where('transactions.status', 'final')
                ->where('transactions.contact_id', $contact_id);

            $walkIn = $this->getWalkInCustomer($business_id);
            $is_walking_customer = (!empty($walkIn) && $walkIn['id'] == $contact_id);

            if ($is_walking_customer) {
                $txns->where(function ($q) {
                    $q->where('transactions.is_settlement', '!=', 1)
                        ->orWhereNull('transactions.is_settlement');
                })->where(function ($q) {
                    $q->where('transactions.sub_type', '!=', 'settlement')
                        ->orWhereNull('transactions.sub_type');
                });
            }

            $txns = $txns->select([
                    DB::raw("SUM(IF(transactions.type = 'fleet_opening_balance', final_total, 0)) as fleet_opening_balance"),
                    DB::raw("SUM(IF(transactions.type = 'vat_price_adjustment', final_total, 0)) as vat_price_adjustment"),
                    DB::raw("SUM(IF(transactions.type = 'cheque_return', final_total, 0)) as cheque_return"),
                    DB::raw("SUM(IF(transactions.type = 'property_sell', final_total, 0)) as property_sell"),
                    DB::raw("SUM(IF(transactions.type = 'route_operation', final_total, 0)) as route_operation"),
                    DB::raw("SUM(IF(transactions.type = 'expense', final_total, 0)) as expense"),
                    DB::raw("SUM(IF(transactions.type = 'sell' OR transactions.type = 'fpos_sale', final_total, 0)) as sell"),
                    DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(transactions.type = 'sell_return', final_total, 0)) as sell_return"),
                    DB::raw("SUM(IF(transactions.type = 'direct_customer_loan', final_total, 0)) as direct_customer_loan"),
                    DB::raw("SUM(IF(transactions.sub_type = 'customer_loan', final_total, 0)) as customer_loan"),
                    DB::raw("SUM(IF(transactions.type = 'ledger_discount', final_total, 0)) as ledger_discount"),
                ])
                ->groupBy('contact_id')
                ->first();

            $pmts_result = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
                ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transaction_payments.business_id', $business_id)
                ->whereNull('transaction_payments.deleted_at')
                ->whereNull('transaction_payments.parent_id')
                ->where(function ($query) {
                    $query->whereNull('transaction_payments.transaction_id')
                        ->orWhere(function ($query) {
                            $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                        });
                })
                ->where('transaction_payments.payment_for', $contact_id)
                ->select(DB::raw('SUM(IF(transaction_payments.deleted_at IS NULL, IF(transaction_payments.is_return = 0, transaction_payments.amount, -transaction_payments.amount), 0)) as total_paid'))
                ->first();

            $pmts = ($pmts_result && $pmts_result->total_paid !== null) ? $pmts_result->total_paid : 0;

            // Handle transaction data - check for null and validate numeric values
            if (!empty($txns)) {
                $balance_details['opening_balance'] = (float)($txns->fleet_opening_balance ?? 0) + (float)($txns->opening_balance ?? 0);
                $balance_details['total_sale'] = (float)(
                    ($txns->cheque_return ?? 0) +
                    ($txns->direct_customer_loan ?? 0) +
                    ($txns->customer_loan ?? 0) +
                    ($txns->property_sell ?? 0) +
                    ($txns->route_operation ?? 0) +
                    ($txns->expense ?? 0) +
                    ($txns->sell ?? 0) +
                    ($txns->vat_price_adjustment ?? 0)
                ) - (float)(
                    ($txns->sell_return ?? 0) +
                    ($txns->ledger_discount ?? 0)
                );
            }

            $balance_details['total_paid'] = (float)$pmts;

            // Add customer payments from CustomerPayment model
            $customer_payments = CustomerPayment::where('customer_id', $contact_id)->sum('amount');
            if (!empty($customer_payments)) {
                $balance_details['total_paid'] += (float)$customer_payments;
            }

            // Add Petro PD / settlement ledger rows from ContactLedger using the same debit-credit
            // direction as the ledger page. This keeps Contacts > Customers > Total Due equal
            // to the ledger final balance, including Walk-In Customer cash/card debit+credit pairs.
            $settlementLedger = \App\ContactLedger::where('business_id', $business_id)
                ->where('contact_id', $contact_id)
                ->whereIn('sub_type', $this->petroPdWalkInLedgerSubTypes())
                ->whereNull('deleted_at')
                ->select([
                    DB::raw("SUM(IF(type = 'debit', amount, 0)) as total_debit"),
                    DB::raw("SUM(IF(type = 'credit', amount, 0)) as total_credit"),
                ])
                ->first();
            if (!empty($settlementLedger)) {
                $balance_details['total_sale'] += (float) ($settlementLedger->total_debit ?? 0);
                $balance_details['total_paid'] += (float) ($settlementLedger->total_credit ?? 0);
            }

        } catch (\Exception $e) {
            Log::error('Error calculating customer balance', [
                'contact_id' => $contact_id,
                'business_id' => $business_id,
                'error' => $e->getMessage()
            ]);
            // Return zero balance on error
            return $get_balance ? 0 : array('opening_balance' => 0, 'total_sale' => 0, 'total_paid' => 0, 'total_balance' => 0);
        }

        $balance = $balance_details['total_sale'] + $balance_details['opening_balance'] - $balance_details['total_paid'];

        // Walk-In Customer Total Due must match the final ledger balance exactly.
        // Cash/Card settlement rows are now posted as debit + immediate credit, so this is normally 0.00.
        if (!empty($is_walking_customer)) {
            $balance = $this->getContactLedgerDebitCreditBalance((int) $contact_id, (int) $business_id);
        }

        // For due amount display, never show negative balances (overpaid customers should show 0 due)
        if (!empty($get_balance)) {
            return max(0, $balance);
        }

        $balance_details['total_balance'] = $balance;

        return $balance_details;
    }

    public function getContactsBalance($contact_ids, $business_id)
    {
        if (empty($contact_ids)) {
            return [];
        }

        $balances = [];
        foreach ($contact_ids as $id) {
            $balances[$id] = [
                'opening_balance' => 0, 
                'total_sale' => 0, 
                'total_paid' => 0, 
                'total_balance' => 0,
                'total_sell_return' => 0,
                'sell_return_paid' => 0
            ];
        }

        try {
            // 1. Fetch all transaction totals for the contacts
            $txns = Transaction::where('business_id', $business_id)
                ->whereIn('contact_id', $contact_ids)
                ->where(function ($query) {
                    $query->whereIn('transactions.type', $this->transaction_types)
                        ->orWhere(function ($query) {
                            $query->where('transactions.type', 'settlement')->where('transactions.sub_type', 'customer_loan');
                        });
                })
                ->where(function ($query) {
                    $query->where('transactions.sub_type', '!=', 'settlement')
                        ->orWhereNull('transactions.sub_type');
                })
                ->whereNull('deleted_at')
                ->where('status', 'final')
                ->select([
                    'contact_id',
                    DB::raw("SUM(IF(type = 'fleet_opening_balance', final_total, 0)) as fleet_opening_balance"),
                    DB::raw("SUM(IF(type = 'vat_price_adjustment', final_total, 0)) as vat_price_adjustment"),
                    DB::raw("SUM(IF(type = 'cheque_return', final_total, 0)) as cheque_return"),
                    DB::raw("SUM(IF(type = 'property_sell', final_total, 0)) as property_sell"),
                    DB::raw("SUM(IF(type = 'route_operation', final_total, 0)) as route_operation"),
                    DB::raw("SUM(IF(type = 'expense', final_total, 0)) as expense"),
                    DB::raw("SUM(IF(type = 'sell' OR type = 'fpos_sale', final_total, 0)) as sell"),
                    DB::raw("SUM(IF(type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(type = 'sell_return', final_total, 0)) as sell_return"),
                    DB::raw("SUM(IF(type = 'direct_customer_loan', final_total, 0)) as direct_customer_loan"),
                    DB::raw("SUM(IF(sub_type = 'customer_loan', final_total, 0)) as customer_loan"),
                    DB::raw("SUM(IF(type = 'ledger_discount', final_total, 0)) as ledger_discount"),
                ])
                ->groupBy('contact_id')
                ->get();

            foreach ($txns as $txn) {
                $id = $txn->contact_id;
                $balances[$id]['opening_balance'] = (float)($txn->fleet_opening_balance ?? 0) + (float)($txn->opening_balance ?? 0);
                $balances[$id]['total_sell_return'] = (float)($txn->sell_return ?? 0);
                $balances[$id]['total_sale'] = (float)(
                    ($txn->cheque_return ?? 0) +
                    ($txn->direct_customer_loan ?? 0) +
                    ($txn->customer_loan ?? 0) +
                    ($txn->property_sell ?? 0) +
                    ($txn->route_operation ?? 0) +
                    ($txn->expense ?? 0) +
                    ($txn->sell ?? 0) +
                    ($txn->vat_price_adjustment ?? 0)
                ) - (float)(
                    ($txn->sell_return ?? 0) +
                    ($txn->ledger_discount ?? 0)
                );
            }

            // 2. Fetch all payment totals for the contacts
            $payments = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
                ->where('transaction_payments.business_id', $business_id)
                ->whereIn('transaction_payments.payment_for', $contact_ids)
                ->whereNull('transaction_payments.deleted_at')
                ->whereNull('transaction_payments.parent_id')
                ->where(function ($query) {
                    $query->whereNull('transaction_payments.transaction_id')
                        ->orWhere(function ($query) {
                            $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                        });
                })
                ->select([
                    'transaction_payments.payment_for as contact_id',
                    DB::raw('SUM(IF(transaction_payments.is_return = 0, transaction_payments.amount, -transaction_payments.amount)) as total_paid')
                ])
                ->groupBy('transaction_payments.payment_for')
                ->get();

            foreach ($payments as $payment) {
                $balances[$payment->contact_id]['total_paid'] = (float)$payment->total_paid;
            }

            // 3. Add customer payments from CustomerPayment model
            $customer_pmts = CustomerPayment::whereIn('customer_id', $contact_ids)
                ->select(['customer_id', DB::raw('SUM(amount) as total_paid')])
                ->groupBy('customer_id')
                ->get();

            foreach ($customer_pmts as $cp) {
                $balances[$cp->customer_id]['total_paid'] += (float)$cp->total_paid;
            }

            // 4. Add Petro PD / settlement ledger rows from ContactLedger using debit-credit direction.
            // This keeps Contacts > Customers > Total Due equal to each customer's ledger final balance.
            $this->addContactLedgerDebitCreditToCustomerBalances($balances, $contact_ids, (int) $business_id);

            // 5. Fetch sell return paid totals separately to avoid complex joins in main transaction query
            $sell_return_payments = TransactionPayment::leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.contact_id', $contact_ids)
                ->where('transactions.type', 'sell_return')
                ->whereNull('transaction_payments.deleted_at')
                ->select(['transactions.contact_id', DB::raw('SUM(transaction_payments.amount) as total_paid')])
                ->groupBy('transactions.contact_id')
                ->get();

            foreach ($sell_return_payments as $srp) {
                $balances[$srp->contact_id]['sell_return_paid'] = (float)$srp->total_paid;
            }

            // Finalize balance
            foreach ($balances as $id => &$details) {
                $details['total_balance'] = $details['total_sale'] + $details['opening_balance'] - $details['total_paid'];
            }

            // Contacts > Customers list: the Walk-In Customer Total Due must be the same as
            // the final customer ledger balance, not an invoice/payment-derived balance.
            $walkIn = $this->getWalkInCustomer($business_id);
            $walkInId = !empty($walkIn['id']) ? (int) $walkIn['id'] : 0;
            if ($walkInId > 0 && isset($balances[$walkInId])) {
                $ledgerBalance = $this->getContactLedgerDebitCreditBalance($walkInId, (int) $business_id);
                $balances[$walkInId]['total_sale'] = $ledgerBalance > 0 ? $ledgerBalance : 0;
                $balances[$walkInId]['total_paid'] = $ledgerBalance < 0 ? abs($ledgerBalance) : 0;
                $balances[$walkInId]['opening_balance'] = 0;
                $balances[$walkInId]['total_balance'] = $ledgerBalance;
            }

        } catch (\Exception $e) {
            Log::error('Error calculating customer balances in batch', [
                'contact_ids' => $contact_ids,
                'business_id' => $business_id,
                'error' => $e->getMessage()
            ]);
        }

        return $balances;
    }


    protected function getContactLedgerDebitCreditBalance(int $contactId, int $businessId): float
    {
        $row = \App\ContactLedger::where('business_id', $businessId)
            ->where('contact_id', $contactId)
            ->whereNull('deleted_at')
            ->select([
                DB::raw("SUM(IF(type = 'debit', amount, 0)) as total_debit"),
                DB::raw("SUM(IF(type = 'credit', amount, 0)) as total_credit"),
            ])
            ->first();

        return (float) (($row->total_debit ?? 0) - ($row->total_credit ?? 0));
    }


    protected function petroPdWalkInLedgerSubTypes(): array
    {
        return [
            'cash_payment',
            'card_payment',
            'cheque_payment',
            'cash_deposit',
            'settlement_cash_payment',
            'settlement_card_payment',
            'pd_walkin_sale',
            'pd_walkin_payment',
            'pd_walkin_cash_debit',
            'pd_walkin_cash_credit',
            'pd_walkin_card_debit',
            'pd_walkin_card_credit',
        ];
    }

    protected function addContactLedgerDebitCreditToCustomerBalances(array &$balances, array $contactIds, int $businessId): void
    {
        if (empty($contactIds)) {
            return;
        }

        $rows = \App\ContactLedger::where('business_id', $businessId)
            ->whereIn('contact_id', $contactIds)
            ->whereIn('sub_type', $this->petroPdWalkInLedgerSubTypes())
            ->whereNull('deleted_at')
            ->select([
                'contact_id',
                DB::raw("SUM(IF(type = 'debit', amount, 0)) as total_debit"),
                DB::raw("SUM(IF(type = 'credit', amount, 0)) as total_credit"),
            ])
            ->groupBy('contact_id')
            ->get();

        foreach ($rows as $row) {
            $contactId = (int) $row->contact_id;
            if (!isset($balances[$contactId])) {
                continue;
            }

            $balances[$contactId]['total_sale'] += (float) ($row->total_debit ?? 0);
            $balances[$contactId]['total_paid'] += (float) ($row->total_credit ?? 0);
        }
    }

    /**
     * Batch supplier balances for the supplier contact list (same rules as getSupplierBalance() / supplier ledger).
     * Do not use getContactsBalance() for suppliers — it only aggregates customer/sale transaction types
     * and omits purchases, so "Total Purchase Due" would not reflect new purchase transactions.
     *
     * @param  array<int>  $contact_ids
     * @return array<int, array<string, float|int>>
     */
    public function getSuppliersContactsBalance($contact_ids, $business_id)
    {
        if (empty($contact_ids)) {
            return [];
        }

        $balances = [];
        foreach ($contact_ids as $id) {
            $balances[$id] = [
                'opening_balance' => 0,
                'opening_balance_paid' => 0,
                'total_purchase' => 0,
                'purchase_paid' => 0,
                'total_purchase_return' => 0,
                'purchase_return_paid' => 0,
                'total_balance' => 0,
            ];
        }

        try {
            $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.contact_id', $contact_ids)
                ->whereIn('transactions.type', $this->supplier_types)
                ->whereNull('transactions.deleted_at')
                ->whereIn('transactions.status', $this->supplier_balance_statuses)
                ->select([
                    'transactions.contact_id',
                    DB::raw("SUM(IF(transactions.type = 'cheque_return', final_total, 0)) as cheque_return"),
                    DB::raw("SUM(IF(transactions.type = 'property_purchase', final_total, 0)) as property_purchase"),
                    DB::raw("SUM(IF(transactions.type = 'expense', final_total, 0)) as expense"),
                    DB::raw("SUM(IF(transactions.type = 'purchase', final_total, 0)) as purchase"),
                    DB::raw("SUM(IF(transactions.type = '_deleted_purchase', final_total, 0)) as purchase_deleted"),
                    DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(transactions.type = 'purchase_return', final_total, 0)) as purchase_return"),
                    DB::raw("SUM(IF(transactions.type = 'ledger_discount', final_total, 0)) as ledger_discount"),
                ])
                ->groupBy('transactions.contact_id')
                ->get();

            foreach ($txns as $txn) {
                $id = $txn->contact_id;
                if (! isset($balances[$id])) {
                    continue;
                }
                $balances[$id]['opening_balance'] = (float) ($txn->opening_balance ?? 0);
                $balances[$id]['total_purchase_return'] = (float) ($txn->purchase_return ?? 0);
                $balances[$id]['total_purchase'] = (float) (
                    ($txn->cheque_return ?? 0) + ($txn->property_purchase ?? 0) + ($txn->expense ?? 0) + ($txn->purchase ?? 0)
                ) - (float) (
                    ($txn->purchase_deleted ?? 0) + ($txn->purchase_return ?? 0) + ($txn->ledger_discount ?? 0)
                );
            }

            $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
                ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transaction_payments.business_id', $business_id)
                ->whereNull('transaction_payments.deleted_at')
                ->whereNull('transaction_payments.parent_id')
                ->where(function ($query) {
                    $query->whereNull('transaction_payments.transaction_id')
                        ->orWhere(function ($query) {
                            $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                        });
                })
                ->whereIn('transaction_payments.payment_for', $contact_ids)
                ->select([
                    'transaction_payments.payment_for as contact_id',
                    DB::raw('SUM(IF(transaction_payments.deleted_at IS NULL, IF(transaction_payments.is_return = 0, transaction_payments.amount, -transaction_payments.amount), 0)) as total_paid'),
                ])
                ->groupBy('transaction_payments.payment_for')
                ->get();

            foreach ($pmts as $p) {
                if (isset($balances[$p->contact_id])) {
                    $balances[$p->contact_id]['purchase_paid'] = (float) ($p->total_paid ?? 0);
                }
            }

            $purchaseReturnPaid = TransactionPayment::leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase_return')
                ->whereIn('transactions.contact_id', $contact_ids)
                ->whereNull('transaction_payments.deleted_at')
                ->select([
                    'transactions.contact_id',
                    DB::raw('SUM(transaction_payments.amount) as purchase_return_paid'),
                ])
                ->groupBy('transactions.contact_id')
                ->get();

            foreach ($purchaseReturnPaid as $row) {
                if (isset($balances[$row->contact_id])) {
                    $balances[$row->contact_id]['purchase_return_paid'] = (float) ($row->purchase_return_paid ?? 0);
                }
            }

            foreach ($balances as $id => &$details) {
                $details['total_balance'] = $details['total_purchase'] + $details['opening_balance'] - $details['purchase_paid'];
            }
            unset($details);
        } catch (\Exception $e) {
            Log::error('Error calculating supplier balances in batch', [
                'contact_ids' => $contact_ids,
                'business_id' => $business_id,
                'error' => $e->getMessage(),
            ]);
        }

        return $balances;
    }

    public function getSupplierBalance($contact_id, $business_id, $get_balance = false)
    {

        $balance_details = array('opening_balance' => 0, 'total_purchase' => 0, 'total_paid' => 0);

        $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', $this->supplier_types)
            ->whereNull('transactions.deleted_at')
            ->whereIn('transactions.status', $this->supplier_balance_statuses)
            ->where('transactions.contact_id', $contact_id)
            ->select([
                DB::raw("SUM(IF(transactions.type = 'cheque_return', final_total, 0)) as cheque_return"),
                DB::raw("SUM(IF(transactions.type = 'property_purchase', final_total, 0)) as property_purchase"),
                DB::raw("SUM(IF(transactions.type = 'expense', final_total, 0)) as expense"),
                DB::raw("SUM(IF(transactions.type = 'purchase', final_total, 0)) as purchase"),
                DB::raw("SUM(IF(transactions.type = '_deleted_purchase', final_total, 0)) as purchase_deleted"),
                DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(transactions.type = 'purchase_return', final_total, 0)) as purchase_return"),
                DB::raw("SUM(IF(transactions.type = 'ledger_discount', final_total, 0)) as ledger_discount"),
            ])
            ->groupBy('contact_id')
            ->first();

        $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
            ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('transaction_payments.deleted_at')
            ->whereNull('transaction_payments.parent_id')
            ->where(function ($query) {
                $query->whereNull('transaction_payments.transaction_id')
                    ->orWhere(function ($query) {
                        $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                    });
            })
            ->where('transaction_payments.payment_for', $contact_id)
            ->select(DB::raw('SUM(IF(transaction_payments.deleted_at IS NULL, IF(transaction_payments.is_return = 0, transaction_payments.amount, -transaction_payments.amount), 0)) as total_paid'))
            ->first()
            ->total_paid;

        if (!empty($txns)) {
            $balance_details['opening_balance'] = ($txns->opening_balance);
            $balance_details['total_purchase'] = ($txns->cheque_return + $txns->property_purchase + $txns->expense + $txns->purchase) - ($txns->purchase_deleted + $txns->purchase_return + $txns->ledger_discount);
        }

        $balance_details['total_paid'] = $pmts ?? 0;



        $balance = $balance_details['total_purchase'] + $balance_details['opening_balance'] - $balance_details['total_paid'];
        if (!empty($get_balance)) {
            return $balance;
        }

        $balance_details['total_balance'] = $balance;

        return $balance_details;
    }

    public function getCustomerBf($contact_id, $business_id, $start_date)
    {
        $balance_details = array('opening_balance' => 0, 'total_sale' => 0, 'total_paid' => 0);

        $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->whereIn('transactions.type', $this->transaction_types)
                    ->orWhere(function ($q) {
                        $q->where('transactions.type', 'settlement')
                            ->where('transactions.sub_type', 'customer_loan');
                    });
            })
            ->where(function ($query) {
                $query->where('transactions.sub_type', '!=', 'settlement')
                    ->orWhereNull('transactions.sub_type');
            })
            ->whereNull('transactions.deleted_at')
            ->where('transactions.status', 'final')
            ->whereDate('transactions.transaction_date', '<', $start_date)
            ->where('transactions.contact_id', $contact_id);

        $walkIn = $this->getWalkInCustomer($business_id);
        $is_walking_customer = (!empty($walkIn) && $walkIn['id'] == $contact_id);

        if ($is_walking_customer) {
            $txns->where(function ($q) {
                $q->where('transactions.is_settlement', '!=', 1)
                    ->orWhereNull('transactions.is_settlement');
            })->where(function ($q) {
                $q->where('transactions.sub_type', '!=', 'settlement')
                    ->orWhereNull('transactions.sub_type');
            });
        }

        $txns = $txns->select([
                DB::raw("SUM(IF(transactions.type = 'fleet_opening_balance', final_total, 0)) as fleet_opening_balance"),
                DB::raw("SUM(IF(transactions.type = 'vat_price_adjustment', final_total, 0)) as vat_price_adjustment"),
                DB::raw("SUM(IF(transactions.type = 'cheque_return', final_total, 0)) as cheque_return"),
                DB::raw("SUM(IF(transactions.type = 'property_sell', final_total, 0)) as property_sell"),
                DB::raw("SUM(IF(transactions.type = 'route_operation', final_total, 0)) as route_operation"),
                DB::raw("SUM(IF(transactions.type = 'expense', final_total, 0)) as expense"),
                DB::raw("SUM(IF(transactions.type = 'sell' OR transactions.type = 'fpos_sale', final_total, 0)) as sell"),
                DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(transactions.type = 'sell_return', final_total, 0)) as sell_return"),
                DB::raw("SUM(IF(transactions.type = 'direct_customer_loan', final_total, 0)) as direct_customer_loan"),
                DB::raw("SUM(IF(transactions.sub_type = 'customer_loan', final_total, 0)) as customer_loan"),
                DB::raw("SUM(IF(transactions.type = 'ledger_discount', final_total, 0)) as ledger_discount"),
            ])
            ->groupBy('contact_id')
            ->first();
        $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
            ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('transaction_payments.deleted_at')
            ->whereNull('transaction_payments.parent_id')
            ->where(function ($query) {
                $query->whereNull('transaction_payments.transaction_id')
                    ->orWhere(function ($query) {
                        $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                    });
            })
            ->whereDate('transaction_payments.paid_on', '<', $start_date)
            ->where('transaction_payments.payment_for', $contact_id)->sum('transaction_payments.amount');



        if (!empty($txns)) {
            $balance_details['opening_balance'] = ($txns->fleet_opening_balance + $txns->opening_balance);
            $balance_details['total_sale'] = ($txns->cheque_return + $txns->direct_customer_loan + $txns->customer_loan + $txns->property_sell + $txns->route_operation + $txns->expense + $txns->sell + $txns->vat_price_adjustment) - ($txns->sell_return + $txns->ledger_discount);
        }

        $balance_details['total_paid'] = $pmts;

        $customer_payments = CustomerPayment::leftjoin('settlements', 'customer_payments.settlement_no', 'settlements.id')
            ->whereDate('settlements.transaction_date', '<', $start_date)
            ->where('customer_payments.customer_id', $contact_id)
            ->sum('customer_payments.amount');
        if (!empty($customer_payments)) {
            $balance_details['total_paid'] += $customer_payments;
        }

        // Add Petro settlement and other manual entries from ContactLedger (before start date)
        // Some builds store cash/card on transactions.sub_type but leave contact_ledgers.sub_type null.
        $cl_results = \App\ContactLedger::query()
            ->from('contact_ledgers')
            ->leftJoin('transactions', 'transactions.id', '=', 'contact_ledgers.transaction_id')
            ->where('contact_ledgers.contact_id', $contact_id)
            ->whereNull('contact_ledgers.deleted_at')
            ->whereDate('contact_ledgers.operation_date', '<', $start_date)
            ->where(function ($query) use ($is_walking_customer) {
                if ($is_walking_customer) {
                    $query->where(function ($q) {
                        $q->whereIn('contact_ledgers.sub_type', [
                            'cash_payment', 'card_payment', 'settlement_cash_payment', 'settlement_card_payment', 'cash_deposit', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit',
                        ])
                            ->orWhere(function ($q2) {
                                $q2->whereIn('transactions.sub_type', ['cash_payment', 'card_payment', 'cash_deposit'])
                                    ->whereIn('contact_ledgers.type', ['debit', 'credit']);
                            });
                    });
                } else {
                    // Match getCustomerLedger / settlement lines: do not use orWhereNull(sub_type). Rows with NULL
                    // sub_type are often AR mirrors of sells/payments already counted via transactions / transaction_payments;
                    // including them here double-counts credits (or debits) and collapses B/F balance (IS1140).
                    $query->whereIn('contact_ledgers.sub_type', ['cash_payment', 'card_payment', 'cheque_payment', 'cash_deposit', 'settlement_cash_payment', 'settlement_card_payment', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit'])
                        ->orWhere(function ($q2) {
                            $q2->whereIn('transactions.sub_type', ['cash_payment', 'card_payment', 'cheque_payment', 'cash_deposit', 'settlement_cash_payment', 'settlement_card_payment', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit'])
                                ->where('contact_ledgers.type', 'credit');
                        });
                }
            })
            ->where(function ($q) {
                $q->where('contact_ledgers.sub_type', '!=', 'cheque_return_charges')
                    ->orWhereNull('contact_ledgers.sub_type');
            })
            ->where(function ($query) use ($contact_id, $is_walking_customer) {
                // Prefer transaction_payments (payment_for) when present; only use CL for legacy
                // rows or when payment_for was never set (avoids double-counting B/F).
                if ($is_walking_customer) {
                    $query->where('contact_ledgers.type', 'debit')
                        ->orWhereNull('contact_ledgers.transaction_payment_id')
                        ->orWhereNotExists(function ($q) use ($contact_id) {
                            $q->select(DB::raw(1))
                                ->from('transaction_payments as tp')
                                ->whereRaw('tp.id = contact_ledgers.transaction_payment_id')
                                ->where('tp.payment_for', $contact_id);
                        });

                    return;
                }

                $query->whereNull('contact_ledgers.transaction_payment_id')
                    ->orWhereNotExists(function ($q) use ($contact_id) {
                        $q->select(DB::raw(1))
                            ->from('transaction_payments as tp')
                            ->whereRaw('tp.id = contact_ledgers.transaction_payment_id')
                            ->where('tp.payment_for', $contact_id);
                    });
            })
            ->select([
                DB::raw("SUM(IF(contact_ledgers.type = 'debit', contact_ledgers.amount, 0)) as total_debit"),
                DB::raw("SUM(IF(contact_ledgers.type = 'credit', contact_ledgers.amount, 0)) as total_credit"),
            ])->first();

        if (!empty($cl_results)) {
            $balance_details['total_sale'] += $cl_results->total_debit;
            $balance_details['total_paid'] += $cl_results->total_credit;
        }

        // Add cheque return charges from contact_ledgers (before start date) only
        // Charges are stored in both transactions.cheque_return_charges and contact_ledgers table
        // We use contact_ledgers as the single source of truth to avoid double counting
        $total_cheque_return_charges_bf = \App\ContactLedger::where('contact_id', $contact_id)
            ->where('sub_type', 'cheque_return_charges')
            ->whereNull('deleted_at')
            ->whereDate('operation_date', '<', $start_date)
            ->sum('amount');

        $balance = $balance_details['total_sale'] + $balance_details['opening_balance'] + $total_cheque_return_charges_bf - $balance_details['total_paid'];
        return $balance;
    }

    public function getCustomerTaxBf($business_id, $start_date, $minimum_date)
    {
        $balance = 0;

        $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->whereIn('transactions.type', $this->tax_txn_types);
            })
            ->where('transactions.tax_amount', '>', 0)
            ->where(function ($query) {
                $query->whereIn('transactions.type', ['sell', 'vat_penalty'])
                    ->orWhere('transactions.is_vat', 1);
            })
            ->whereNull('transactions.deleted_at')
            ->whereDate('transactions.transaction_date', '<', $start_date)
            ->whereDate('transactions.transaction_date', '>', $minimum_date)
            ->select([
                DB::raw("SUM(IF(transactions.type = 'purchase', tax_amount, 0)) as purchase_tax"),
                DB::raw("SUM(IF(transactions.type = 'expense', tax_amount, 0)) as expense_tax"),
                DB::raw("SUM(IF(transactions.type = 'sell', tax_amount, 0)) as sell_tax"),
                DB::raw("SUM(IF(transactions.type = 'vat_penalty', tax_amount, 0)) as penalty_tax")
            ])
            // ->groupBy('contact_id')
            ->first();

        if (!empty($txns)) {
            $balance = $txns->sell_tax + $txns->penalty_tax - ($txns->purchase_tax + $txns->expense_tax);
        }

        $pmts = VatPayment::whereDate('date', '<', $start_date)
            ->where('business_id', $business_id)
            ->whereDate('date', '>', $minimum_date)
            ->sum('amount');
        $obs = VatPayableToAccount::whereDate('created_at', '<', $start_date)
            ->whereDate('created_at', '>', $minimum_date)
            ->where('business_id', $business_id)
            ->select([
                DB::raw("SUM(IF(type = 'vat_receivable_account', amount, 0)) as input_ob"),
                DB::raw("SUM(IF(type = 'vat_payable_account', amount, 0)) as output_ob")
            ])->first();

        if (!empty($obs)) {
            $balance += $obs->ouput_ob - $obs->input_ob;
        }

        $balance -= $pmts;



        return $balance;
    }

    public function getCustomerTaxBalance($business_id, $minimum_date, $get_balance = false)
    {
        $balance_details = array('input_tax' => 0, 'output_tax' => 0, 'total_paid' => 0);

        $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->whereIn('transactions.type', $this->tax_txn_types);
            })
            ->where('transactions.tax_amount', '>', 0)
            ->where(function ($query) {
                $query->whereIn('transactions.type', ['sell', 'vat_penalty'])
                    ->orWhere('transactions.is_vat', 1);
            })
            ->whereNull('transactions.deleted_at')
            ->whereDate('transactions.transaction_date', '>', $minimum_date)
            ->select([
                DB::raw("SUM(IF(transactions.type = 'purchase', tax_amount, 0)) as purchase_tax"),
                DB::raw("SUM(IF(transactions.type = 'expense', tax_amount, 0)) as expense_tax"),
                DB::raw("SUM(IF(transactions.type = 'sell', tax_amount, 0)) as sell_tax"),
                DB::raw("SUM(IF(transactions.type = 'vat_penalty', tax_amount, 0)) as penalty_tax")
            ])
            ->groupBy('contact_id')
            ->first();

        if (!empty($txns)) {
            $balance['input_tax'] = $txns->purchase_tax + $txns->expense_tax;
            $balance['output_tax'] = $txns->sell_tax + $txns->penalty_tax;
        }

        $pmts = VatPayment::whereDate('date', '>', $minimum_date)
            ->where('business_id', $business_id)
            ->sum('amount');
        $balance['total_paid'] = $pmts;

        $obs = VatPayableToAccount::whereDate('created_at', '>', $minimum_date)
            ->where('business_id', $business_id)
            ->select([
                DB::raw("SUM(IF(type = 'vat_receivable_account', amount, 0)) as input_ob"),
                DB::raw("SUM(IF(type = 'vat_payable_account', amount, 0)) as output_ob")
            ])->first();

        if (!empty($obs)) {
            $balance += $obs->ouput_ob - $obs->input_ob;
        }



        $balance = $balance_details['output_tax'] - $balance_details['input_tax'] - $balance_details['total_paid'];
        if (!empty($get_balance)) {
            return $balance;
        }

        $balance_details['total_balance'] = $balance;

        return $balance_details;
    }

    public function getCustomerTaxLedger($business_id, $start_date, $end_date, $minimum_date)
    {
        $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->whereIn('transactions.type', $this->tax_txn_types);
            })
            ->where('transactions.tax_amount', '>', 0)
            ->where(function ($query) {
                $query->whereIn('transactions.type', ['sell', 'vat_penalty'])
                    ->orWhere('transactions.is_vat', 1);
            })
            ->whereNull('transactions.deleted_at')
            ->whereDate('transactions.transaction_date', '>', $minimum_date)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select([
                'transactions.id',
                'transactions.transaction_date as date',
                'transactions.type as type',
                'transactions.tax_amount as amount',
                'transactions.transaction_note'

            ]);

        $pmts = VatPayment::where('vat_payments.business_id', $business_id)
            ->whereDate('date', '>', $minimum_date)
            ->whereDate('date', '>=', $start_date)
            ->whereDate('date', '<=', $end_date)
            ->select([
                'id',
                'date as date',
                DB::raw('"vat_payment" as type'),
                'amount as amount',
                'note as transaction_note'

            ]);

        $obs = VatPayableToAccount::where('business_id', $business_id)
            ->whereDate('created_at', '>', $minimum_date)
            ->whereDate('created_at', '>=', $start_date)
            ->whereDate('created_at', '<=', $end_date)
            ->select([
                'id',
                'created_at as date',
                DB::raw(
                    '
                            CASE 
                                WHEN type = "vat_receivable_account" THEN "input_ob"
                                WHEN type = "vat_payable_account" THEN "output_ob" 
                            END as type'
                ),
                'amount as amount',
                'note as transaction_note'

            ]);



        $txnResult = $txns->unionAll($pmts)->unionAll($obs)->orderBy('date', 'asc');


        return $txnResult->get();
    }

    public function getSupplierBf($contact_id, $business_id, $start_date)
    {
        $balance_details = array('opening_balance' => 0, 'total_purchase' => 0, 'total_paid' => 0);
        $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', $this->supplier_types)
            ->whereNull('transactions.deleted_at')
            ->whereIn('transactions.status', $this->supplier_balance_statuses)
            ->whereDate('transactions.transaction_date', '<', $start_date)
            ->where('transactions.contact_id', $contact_id)
            ->select([
                DB::raw("SUM(IF(transactions.type = 'cheque_return', final_total, 0)) as cheque_return"),
                DB::raw("SUM(IF(transactions.type = 'property_purchase', final_total, 0)) as property_purchase"),
                DB::raw("SUM(IF(transactions.type = 'expense', final_total, 0)) as expense"),
                DB::raw("SUM(IF(transactions.type = 'purchase', final_total, 0)) as purchase"),
                DB::raw("SUM(IF(transactions.type = '_deleted_purchase', final_total, 0)) as purchase_deleted"),
                DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(transactions.type = 'purchase_return', final_total, 0)) as purchase_return"),
                DB::raw("SUM(IF(transactions.type = 'ledger_discount', final_total, 0)) as ledger_discount"),
            ])
            ->groupBy('contact_id')
            ->first();

        $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
            ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('transaction_payments.deleted_at')
            ->whereNull('transaction_payments.parent_id')
            ->where(function ($query) {
                $query->whereNull('transaction_payments.transaction_id')
                    ->orWhere(function ($query) {
                        $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                    });
            })
            ->whereDate('transaction_payments.paid_on', '<', $start_date)
            ->where('transaction_payments.payment_for', $contact_id)->sum('transaction_payments.amount');



        if (!empty($txns)) {
            $balance_details['opening_balance'] = ($txns->opening_balance);
            $balance_details['total_purchase'] = ($txns->cheque_return + $txns->property_purchase + $txns->expense + $txns->purchase) - ($txns->purchase_deleted + $txns->purchase_return + $txns->ledger_discount);
        }

        $balance_details['total_paid'] = $pmts;


        $balance = $balance_details['total_purchase'] + $balance_details['opening_balance'] - $balance_details['total_paid'];
        return $balance;
    }

    public function getContactSummaryBf($contact_id, $business_id, $start_date)
    {
        $balance_details = 0;
        $total_sale = 0;
        $total_paid = 0;
        $opening_balance = 0;
        $total_purchase = 0;
        $contact = Contact::findOrFail($contact_id);
        if ($contact->type == 'supplier' || $contact->type == 'both') {
            $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', $this->supplier_types)
                ->whereNull('transactions.deleted_at')
                ->whereIn('transactions.status', $this->supplier_balance_statuses)
                ->whereDate('transactions.transaction_date', '<', $start_date)
                ->where('transactions.contact_id', $contact_id)
                ->select([
                    DB::raw("SUM(IF(transactions.type = 'cheque_return', final_total, 0)) as cheque_return"),
                    DB::raw("SUM(IF(transactions.type = 'property_purchase', final_total, 0)) as property_purchase"),
                    DB::raw("SUM(IF(transactions.type = 'expense', final_total, 0)) as expense"),
                    DB::raw("SUM(IF(transactions.type = 'purchase', final_total, 0)) as purchase"),
                    DB::raw("SUM(IF(transactions.type = '_deleted_purchase', final_total, 0)) as purchase_deleted"),
                    DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(transactions.type = 'purchase_return', final_total, 0)) as purchase_return"),
                    DB::raw("SUM(IF(transactions.type = 'ledger_discount', final_total, 0)) as ledger_discount"),
                ])
                ->groupBy('contact_id')
                ->first();

            $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
                ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transaction_payments.business_id', $business_id)
                ->whereNull('transaction_payments.deleted_at')
                ->whereNull('transaction_payments.parent_id')
                ->where(function ($query) {
                    $query->whereNull('transaction_payments.transaction_id')
                        ->orWhere(function ($query) {
                            $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                        });
                })
                ->whereDate('transaction_payments.paid_on', '<', $start_date)
                ->where('transaction_payments.payment_for', $contact_id)->sum('transaction_payments.amount');



            if (!empty($txns)) {
                $opening_balance = ($txns->opening_balance);
                $total_purchase = ($txns->cheque_return + $txns->property_purchase + $txns->expense + $txns->purchase) - ($txns->purchase_deleted + $txns->purchase_return + $txns->ledger_discount);
            }

            $total_paid = $pmts;

            $balance = $total_purchase + $opening_balance - $total_paid;

            return $balance;
        } else {


            $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transactions.business_id', $business_id)
                ->where(function ($query) {
                    $query->whereIn('transactions.type', $this->transaction_types)
                        ->orWhere(function ($query) {
                            $query->where('transactions.type', 'settlement')->where('transactions.sub_type', 'customer_loan');
                        });
                })
                ->where(function ($query) {
                    $query->where('transactions.sub_type', '!=', 'settlement')
                        ->orWhereNull('transactions.sub_type');
                })
                ->whereNull('transactions.deleted_at')
                ->where('transactions.status', 'final')
                ->whereDate('transactions.transaction_date', '<', $start_date)
                ->where('transactions.contact_id', $contact_id)
                ->select([
                    DB::raw("SUM(IF(transactions.type = 'fleet_opening_balance', final_total, 0)) as fleet_opening_balance"),
                    DB::raw("SUM(IF(transactions.type = 'vat_price_adjustment', final_total, 0)) as vat_price_adjustment"),
                    DB::raw("SUM(IF(transactions.type = 'cheque_return', final_total, 0)) as cheque_return"),
                    DB::raw("SUM(IF(transactions.type = 'property_sell', final_total, 0)) as property_sell"),
                    DB::raw("SUM(IF(transactions.type = 'route_operation', final_total, 0)) as route_operation"),
                    DB::raw("SUM(IF(transactions.type = 'expense', final_total, 0)) as expense"),
                    DB::raw("SUM(IF(transactions.type = 'sell' OR transactions.type = 'fpos_sale', final_total, 0)) as sell"),
                    DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(transactions.type = 'sell_return', final_total, 0)) as sell_return"),
                    DB::raw("SUM(IF(transactions.type = 'direct_customer_loan', final_total, 0)) as direct_customer_loan"),
                    DB::raw("SUM(IF(transactions.sub_type = 'customer_loan', final_total, 0)) as customer_loan"),
                    DB::raw("SUM(IF(transactions.type = 'ledger_discount', final_total, 0)) as ledger_discount"),
                ])
                ->groupBy('contact_id')
                ->first();
            $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
                ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transaction_payments.business_id', $business_id)
                ->whereNull('transaction_payments.deleted_at')
                ->whereNull('transaction_payments.parent_id')
                ->where(function ($query) {
                    $query->whereNull('transaction_payments.transaction_id')
                        ->orWhere(function ($query) {
                            $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                        });
                })
                ->whereDate('transaction_payments.paid_on', '<', $start_date)
                ->where('transaction_payments.payment_for', $contact_id)->sum('transaction_payments.amount');



            if (!empty($txns)) {
                $opening_balance = ($txns->fleet_opening_balance + $txns->opening_balance);
                $total_sale = ($txns->cheque_return + $txns->direct_customer_loan + $txns->customer_loan + $txns->property_sell + $txns->route_operation + $txns->expense + $txns->sell + $txns->vat_price_adjustment) - ($txns->sell_return + $txns->ledger_discount);
            }

            $total_paid = $pmts;

            $customer_payments = CustomerPayment::leftjoin('settlements', 'customer_payments.settlement_no', 'settlements.id')
                ->whereDate('settlements.transaction_date', '<', $start_date)
                ->where('customer_payments.customer_id', $contact_id)
                ->sum('customer_payments.amount');
            if (!empty($customer_payments)) {
                $total_paid += $customer_payments;
            }

            // Add Petro settlement payments from ContactLedger (before start date)
            $settlement_payments_bf = \App\ContactLedger::where('contact_id', $contact_id)
                ->whereIn('sub_type', ['cash_payment', 'card_payment', 'cheque_payment', 'cash_deposit', 'settlement_cash_payment', 'settlement_card_payment', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit'])
                ->whereNull('deleted_at')
                ->whereDate('operation_date', '<', $start_date)
                ->sum('amount');
            if (!empty($settlement_payments_bf)) {
                $total_paid += $settlement_payments_bf;
            }


            $balance = $total_sale + $opening_balance - $total_paid;
            return $balance;
        }
    }

    public function getContactSummaryLedger($contact_id, $business_id, $start_date)
    {
        $balance_details = array('total_in' => 0, 'total_out' => 0);

        $contact = Contact::findOrFail($contact_id);
        if ($contact->type == 'supplier' || $contact->type == 'both') {
            $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', $this->supplier_types)
                ->whereNull('transactions.deleted_at')
                ->whereIn('transactions.status', $this->supplier_balance_statuses)
                ->whereDate('transactions.transaction_date', '=', $start_date)
                ->where('transactions.contact_id', $contact_id)
                ->select([
                    DB::raw("SUM(IF(transactions.type = 'cheque_return', final_total, 0)) as cheque_return"),
                    DB::raw("SUM(IF(transactions.type = 'property_purchase', final_total, 0)) as property_purchase"),
                    DB::raw("SUM(IF(transactions.type = 'expense', final_total, 0)) as expense"),
                    DB::raw("SUM(IF(transactions.type = 'purchase', final_total, 0)) as purchase"),
                    DB::raw("SUM(IF(transactions.type = '_deleted_purchase', final_total, 0)) as purchase_deleted"),
                    DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(transactions.type = 'purchase_return', final_total, 0)) as purchase_return"),
                    DB::raw("SUM(IF(transactions.type = 'ledger_discount', final_total, 0)) as ledger_discount"),
                ])
                ->groupBy('contact_id')
                ->first();

            $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
                ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transaction_payments.business_id', $business_id)
                ->whereNull('transaction_payments.deleted_at')
                ->whereNull('transaction_payments.parent_id')
                ->where(function ($query) {
                    $query->whereNull('transaction_payments.transaction_id')
                        ->orWhere(function ($query) {
                            $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                        });
                })
                ->whereDate('transaction_payments.paid_on', '=', $start_date)
                ->where('transaction_payments.payment_for', $contact_id)->sum('transaction_payments.amount');



            if (!empty($txns)) {
                $balance_details['total_in'] = ($txns->cheque_return + $txns->property_purchase + $txns->expense + $txns->purchase) - ($txns->purchase_deleted + $txns->purchase_return + $txns->ledger_discount) + ($txns->opening_balance);
            }
            $balance_details['total_out'] = $pmts;
            return $balance_details;
        } else {

            $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transactions.business_id', $business_id)
                ->where(function ($query) {
                    $query->whereIn('transactions.type', $this->transaction_types)
                        ->orWhere(function ($query) {
                            $query->where('transactions.type', 'settlement')->where('transactions.sub_type', 'customer_loan');
                        });
                })
                ->whereNull('transactions.deleted_at')
                ->where('transactions.status', 'final')
                ->whereDate('transactions.transaction_date', '=', $start_date)
                ->where('transactions.contact_id', $contact_id)
                ->select([
                    DB::raw("SUM(IF(transactions.type = 'fleet_opening_balance', final_total, 0)) as fleet_opening_balance"),
                    DB::raw("SUM(IF(transactions.type = 'vat_price_adjustment', final_total, 0)) as vat_price_adjustment"),
                    DB::raw("SUM(IF(transactions.type = 'cheque_return', final_total, 0)) as cheque_return"),
                    DB::raw("SUM(IF(transactions.type = 'property_sell', final_total, 0)) as property_sell"),
                    DB::raw("SUM(IF(transactions.type = 'route_operation', final_total, 0)) as route_operation"),
                    DB::raw("SUM(IF(transactions.type = 'expense', final_total, 0)) as expense"),
                    DB::raw("SUM(IF(transactions.type = 'sell' OR transactions.type = 'fpos_sale', final_total, 0)) as sell"),
                    DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(transactions.type = 'sell_return', final_total, 0)) as sell_return"),
                    DB::raw("SUM(IF(transactions.type = 'direct_customer_loan', final_total, 0)) as direct_customer_loan"),
                    DB::raw("SUM(IF(transactions.sub_type = 'customer_loan', final_total, 0)) as customer_loan"),
                    DB::raw("SUM(IF(transactions.type = 'ledger_discount', final_total, 0)) as ledger_discount"),
                ])
                ->groupBy('contact_id')
                ->first();
            $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
                ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
                ->where('transaction_payments.business_id', $business_id)
                ->whereNull('transaction_payments.deleted_at')
                ->whereNull('transaction_payments.parent_id')
                ->where(function ($query) {
                    $query->whereNull('transaction_payments.transaction_id')
                        ->orWhere(function ($query) {
                            $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                        });
                })
                ->whereDate('transaction_payments.paid_on', '=', $start_date)
                ->where('transaction_payments.payment_for', $contact_id)->sum('transaction_payments.amount');



            if (!empty($txns)) {
                $balance_details['total_in'] = ($txns->cheque_return + $txns->direct_customer_loan + $txns->customer_loan + $txns->property_sell + $txns->route_operation + $txns->expense + $txns->sell + $txns->vat_price_adjustment) - ($txns->sell_return + $txns->ledger_discount) + ($txns->fleet_opening_balance + $txns->opening_balance);
            }

            $balance_details['total_out'] = $pmts;

            $customer_payments = CustomerPayment::leftjoin('settlements', 'customer_payments.settlement_no', 'settlements.id')
                ->whereDate('settlements.transaction_date', '=', $start_date)
                ->where('customer_payments.customer_id', $contact_id)
                ->sum('customer_payments.amount');
            if (!empty($customer_payments)) {
                $balance_details['total_out'] += $customer_payments;
            }

            // Add Petro settlement payments from ContactLedger (on start date)
            $settlement_payments = \App\ContactLedger::where('contact_id', $contact_id)
                ->whereIn('sub_type', ['cash_payment', 'card_payment', 'cheque_payment', 'cash_deposit', 'settlement_cash_payment', 'settlement_card_payment', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit'])
                ->whereNull('deleted_at')
                ->whereDate('operation_date', '=', $start_date)
                ->sum('amount');
            if (!empty($settlement_payments)) {
                $balance_details['total_out'] += $settlement_payments;
            }


            $balance = $balance_details;
            return $balance;
        }
    }

    public function getCustomerLedger($contact_id, $business_id, $start_date, $end_date)
    {
        $walkIn = $this->getWalkInCustomer($business_id);
        $is_walking_customer = (!empty($walkIn) && $walkIn['id'] == $contact_id);

        $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->leftjoin('air_ticket_invoices', 'transactions.id', 'air_ticket_invoices.transaction_id')
            ->leftjoin('contact_ledgers', 'contact_ledgers.transaction_id', 'transactions.id')
            ->leftJoin('settlements', 'settlements.settlement_no', '=', 'transactions.invoice_no')
            ->leftJoin('pump_operators', 'pump_operators.id', '=', 'settlements.pump_operator_id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('customer_statement_details', 'customer_statement_details.transaction_id', '=', 'transactions.id')
            ->leftJoin('customer_statements', 'customer_statement_details.statement_id', '=', 'customer_statements.id')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('contacts.manual_bill_settlement')
                        ->orWhere('contacts.manual_bill_settlement', 0);
                })
                    ->orWhere(function ($q) {
                        $q->where('contacts.manual_bill_settlement', 1)
                            ->where('transactions.invoice_no', 'LIKE', 'INV%');
                    })
                    ->orWhere(function ($q) {
                        $q->where('transactions.is_credit_sale', 1)
                            ->orWhere('transactions.sub_type', 'credit_sale');
                    })
                    // Always include cheque_return transactions regardless of manual_bill_settlement
                    ->orWhere('transactions.type', 'cheque_return');
            })
            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->whereIn('transactions.type', $this->transaction_types)
                    ->orWhere(function ($q) {
                        $q->where('transactions.type', 'settlement')
                            ->where('transactions.sub_type', 'customer_loan');
                    })
                    ->orWhere(function ($query) {
                        $query->where('transactions.type', 'refund');
                    });
            })
            ->where(function ($query) {
                $query->where('transactions.sub_type', '!=', 'settlement')
                    ->orWhereNull('transactions.sub_type');
            })
            ->whereNull('transactions.deleted_at')
            ->whereIn('transactions.status', ['final', 'received'])
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->where('transactions.contact_id', $contact_id)
            // Exclude already-paid non-credit sells (regular POS sales) to prevent duplication
            // in the ledger. Credit sales (is_credit_sale=1) and unpaid sells must always show.
            ->where(function ($query) use ($is_walking_customer) {
                if ($is_walking_customer) {
                    $query->where('transactions.is_credit_sale', 1)
                        ->orWhere('transactions.payment_status', '!=', 'paid')
                        ->orWhereNotIn('transactions.type', ['sell'])
                        ->orWhere(function ($q) {
                            $q->where('transactions.type', 'sell')
                                ->where('transactions.payment_status', 'paid');
                        });
                } else {
                    $query->where('transactions.is_credit_sale', 1)
                        ->orWhere('transactions.payment_status', '!=', 'paid')
                        ->orWhereNotIn('transactions.type', ['sell']);
                }
            })
            ->where(function ($query) {
                $query->whereRaw(
                    'NOT (
                        transactions.type = ?
                        AND IFNULL(transactions.is_credit_sale, 0) = ?
                        AND IFNULL(transactions.is_settlement, 0) = ?
                        AND IFNULL(transactions.sub_type, \'\') = \'\'
                    )',
                    ['sell', 1, 0]
                );
            })
            // Petro / settlement (Doc 7912): mirror "Sale / Paid" rows (often blank invoice, not always is_settlement)
            // that duplicate a real credit_sale line same day / same amount. Hide when an open credit_sale matches.
            // Covers sell and fpos_sale; cs.id <> outer row so the real credit_sale line is not removed.
            ->where(function ($query) use ($contact_id, $business_id) {
                $query->whereRaw(
                    'NOT (
                        transactions.type IN (\'sell\', \'fpos_sale\')
                        AND LOWER(transactions.payment_status) = \'paid\'
                        AND IFNULL(transactions.is_credit_sale, 0) = 0
                        AND EXISTS (
                            SELECT 1 FROM transactions AS cs
                            WHERE cs.contact_id = ?
                            AND cs.business_id = ?
                            AND cs.type = \'sell\'
                            AND cs.sub_type = \'credit_sale\'
                            AND cs.deleted_at IS NULL
                            AND cs.id <> transactions.id
                            AND DATE(cs.transaction_date) = DATE(transactions.transaction_date)
                            AND ABS(cs.final_total - transactions.final_total) < 0.02
                            AND (
                                cs.payment_status IN (\'due\', \'partial\')
                                OR (
                                    cs.final_total - COALESCE((
                                        SELECT SUM(IF(tp.deleted_at IS NULL, IF(IFNULL(tp.is_return, 0) = 0, tp.amount, -tp.amount), 0))
                                        FROM transaction_payments AS tp
                                        WHERE tp.transaction_id = cs.id
                                        AND tp.method NOT IN (\'credit_sale\', \'credit_expense\')
                                    ), 0) > 0.02
                                )
                            )
                        )
                    )',
                    [$contact_id, $business_id]
                );
            });

        if ($is_walking_customer) {
            $txns->where(function ($q) {
                $q->where('transactions.is_settlement', '!=', 1)
                    ->orWhereNull('transactions.is_settlement')
                    ->orWhere('transactions.sub_type', 'cash_deposit');
            })->where(function ($q) {
                $q->where('transactions.sub_type', '!=', 'settlement')
                    ->orWhereNull('transactions.sub_type')
                    ->orWhere('transactions.sub_type', 'cash_deposit');
            })->where(function ($q) {
                $q->whereNull('settlements.id')
                    ->orWhere('transactions.sub_type', 'cash_deposit');
            });
        }

        $txns->select([
                'transactions.id',
                'transactions.transaction_date as date',
                DB::raw("CONVERT(CASE WHEN transactions.type = 'hms_booking' THEN 'sell' ELSE transactions.type END USING utf8mb4) COLLATE utf8mb4_unicode_ci as type"),
                DB::raw('CONVERT(transactions.type USING utf8mb4) COLLATE utf8mb4_unicode_ci as transaction_type'),
                DB::raw('CONVERT(transactions.invoice_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as invoice_no'),
                DB::raw('NULL as page'),
                DB::raw('CONVERT(business_locations.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as location_name'),
                DB::raw("CONVERT(CASE WHEN transactions.type = 'sell' AND IFNULL(transactions.is_credit_sale, 0) = 1 AND transactions.payment_status = 'paid' THEN 'credit_sale' ELSE transactions.payment_status END USING utf8mb4) COLLATE utf8mb4_unicode_ci as payment_status"),
                DB::raw("CASE WHEN transactions.type = 'ledger' THEN contact_ledgers.amount ELSE transactions.final_total END as amount"),
                'transactions.cheque_return_charges as ch_charges',
                DB::raw('NULL as payment_row'),
                DB::raw('NULL as account_id'),
                'transactions.deleted_at',
                'transactions.deleted_by',
                'transactions.created_at as created_at',
                DB::raw('CONVERT(transactions.ref_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as bill_no'),
                DB::raw('CONVERT(air_ticket_invoices.airticket_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as airticket_no'),
                DB::raw('NULL as paid_in_type'),
                DB::raw('CASE WHEN settlements.id IS NOT NULL THEN 1 ELSE NULL END as is_settlement_customer_payment'),
                DB::raw('CONVERT(pump_operators.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as pump_operator'),
                DB::raw("CONVERT(CASE WHEN transactions.type = 'ledger' THEN COALESCE(contact_ledgers.note, transactions.additional_notes) ELSE NULL END USING utf8mb4) COLLATE utf8mb4_unicode_ci as note"),
                DB::raw('CONVERT(settlements.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as settlement_no'),
                DB::raw('CONVERT(customer_statements.statement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as statement_no'),
                'transactions.rp_earned',
                'transactions.rp_redeemed',
                DB::raw("CONVERT(CASE 
                    WHEN transactions.type = 'ledger' THEN contact_ledgers.type
                    WHEN transactions.type = 'cheque_return' THEN 'debit'
                    WHEN transactions.type = 'sell' THEN 'credit'
                    WHEN transactions.type = 'sell_return' THEN 'debit'
                    WHEN transactions.type = 'purchase' THEN 'credit'
                    WHEN transactions.type = 'payment' THEN 'credit'
                    ELSE 'debit'
                END USING utf8mb4) COLLATE utf8mb4_unicode_ci as acc_transaction_type"),
                DB::raw('NULL as sub_type'),
            ])
            ->groupBy('transactions.id');

        $pmtsBase = TransactionPayment::leftJoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
            ->leftJoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->leftJoin('air_ticket_invoices', 'transactions.id', 'air_ticket_invoices.transaction_id')
            ->leftJoin('settlements', 'settlements.settlement_no', '=', 'transactions.invoice_no')
            ->leftJoin('pump_operators', 'pump_operators.id', '=', 'settlements.pump_operator_id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('contact_ledgers', 'contact_ledgers.transaction_payment_id', 'transaction_payments.id')
            ->leftJoin('customer_statements', 'customer_statements.id', '=', 'transaction_payments.linked_customer_statement')
            ->leftJoin('customer_statement_details', 'customer_statement_details.statement_id', '=', 'customer_statements.id')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('contacts.manual_bill_settlement')
                        ->orWhere('contacts.manual_bill_settlement', 0);
                })
                    ->orWhere(function ($q) {
                        $q->where('contacts.manual_bill_settlement', 1)
                            ->where('transactions.invoice_no', 'LIKE', 'INV%');
                    })
                    ->orWhere(function ($q) {
                        $q->where('transactions.is_credit_sale', 1)
                            ->orWhere('transactions.sub_type', 'credit_sale');
                    });
            })

            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('transaction_payments.parent_id')
            ->where(function ($query) {
                $query->whereNull('transaction_payments.transaction_id')
                    ->orWhere(function ($query) {
                        $query->whereNotIn('transactions.type', [
                            'security_deposit',
                            'refund_security_deposit',
                            'security_deposit_refund',
                            'cheque_opening_balance'
                        ]);
                    });
            })
            ->where('transaction_payments.payment_for', $contact_id)
            ->whereDate('transaction_payments.paid_on', '>=', $start_date)
            ->whereDate('transaction_payments.paid_on', '<=', $end_date)
            // Credit-sale finalization can create synthetic payment rows.
            // Keep excluding only those synthetic method types, while allowing real receipts
            // (cash/card/cheque/bank etc.) so customer bulk sale payments appear in ledger.
            ->where(function ($query) {
                $query->whereNull('transaction_payments.transaction_id')
                    ->orWhereRaw(
                        'NOT (
                            transactions.type = ?
                            AND IFNULL(transactions.is_credit_sale, 0) = ?
                            AND transaction_payments.method IN (?, ?)
                        )',
                        ['sell', 1, 'credit_sale', 'credit_expense']
                    );
            })
            // Petro settlement: payments are often posted against the parent transaction (sell + sub_type settlement),
            // which is stored as payment_status paid, while the customer's credit_sale row (same invoice_no) stays due.
            // Those rows look like customer "Paid" payments before any real receipt — hide them until credit_sale is cleared.
            // Use outstanding balance (final_total − real payments on the credit_sale transaction), not payment_status alone,
            // because status can be wrong/stale while the credit sale is still unpaid (Doc 7912 duplicate "Paid" lines).
            ->where(function ($query) use ($contact_id, $business_id, $is_walking_customer) {
                if ($is_walking_customer) {
                    $query->where(function ($q) {
                        $q->whereNull('transactions.sub_type')
                            ->orWhere('transactions.sub_type', '!=', 'settlement')
                            ->orWhere('transactions.sub_type', 'cash_deposit');
                    })
                    ->where(function ($q) {
                        $q->where('transactions.type', '!=', 'settlement')
                            ->orWhereNull('transactions.type')
                            ->orWhere('transactions.sub_type', 'cash_deposit');
                    })
                    ->where(function ($q) {
                        $q->where('transactions.is_settlement', '!=', 1)
                            ->orWhereNull('transactions.is_settlement')
                            ->orWhere('transactions.sub_type', 'cash_deposit');
                    });
                } else {
                    $query->where(function ($q) {
                        $q->whereNull('transactions.sub_type')
                            ->orWhere('transactions.sub_type', '!=', 'settlement');
                    })
                        ->orWhereNotExists(function ($sub) use ($contact_id, $business_id) {
                            // Petro credit sale: identify by sub_type (some rows may not have is_credit_sale set in DB).
                            // Match invoice_no as strings (settlement_no can be int in one table and string in another).
                            $sub->select(DB::raw(1))
                                ->from('transactions as cs_ledger_settlement')
                                ->whereRaw(
                                    'CAST(cs_ledger_settlement.invoice_no AS CHAR) = CAST(transactions.invoice_no AS CHAR)'
                                )
                                ->where('cs_ledger_settlement.business_id', $business_id)
                                ->where('cs_ledger_settlement.contact_id', $contact_id)
                                ->where('cs_ledger_settlement.type', 'sell')
                                ->where('cs_ledger_settlement.sub_type', 'credit_sale')
                                ->whereNull('cs_ledger_settlement.deleted_at')
                                ->whereRaw(
                                    '(cs_ledger_settlement.final_total - COALESCE((
                                        SELECT SUM(IF(tp.deleted_at IS NULL, IF(IFNULL(tp.is_return, 0) = 0, tp.amount, -tp.amount), 0))
                                        FROM transaction_payments AS tp
                                        WHERE tp.transaction_id = cs_ledger_settlement.id
                                        AND tp.method NOT IN (\'credit_sale\', \'credit_expense\')
                                    ), 0)) > 0.02'
                                );
                        });
                }
            })
            ->when($is_walking_customer, function ($query) {
                // Restrict walk-in ledger to cash/card (not cheque/bank); do not exclude pump settlements
                // that have meter_sales — those payments are valid customer receipts.
                return $query->whereIn('transaction_payments.method', ['cash', 'card']);
            });

        $pmtsNonBulk = (clone $pmtsBase)
            ->where(function ($q) {
                $q->whereNull('transaction_payments.paid_in_type')
                    ->orWhere('transaction_payments.paid_in_type', '!=', 'customer_bulk');
            })
            ->select([
                'transactions.id',
                'transaction_payments.paid_on as date',
                DB::raw('"payment" COLLATE utf8mb4_unicode_ci as type'),
                DB::raw('CONVERT(transactions.type USING utf8mb4) COLLATE utf8mb4_unicode_ci as transaction_type'),
                DB::raw('CONVERT(transaction_payments.payment_ref_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as invoice_no'),
                'contact_ledgers.page',
                DB::raw('CONVERT(business_locations.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as location_name'),
                DB::raw('CONVERT(transactions.payment_status USING utf8mb4) COLLATE utf8mb4_unicode_ci as payment_status'),
                'transaction_payments.amount as amount',
                DB::raw('NULL as ch_charges'),
                DB::raw('transaction_payments.id as payment_row'),
                DB::raw('CONVERT(transaction_payments.account_id USING utf8mb4) COLLATE utf8mb4_unicode_ci as account_id'),
                'transaction_payments.deleted_at',
                'transaction_payments.deleted_by',
                'transaction_payments.created_at as created_at',
                DB::raw('CONVERT(COALESCE(
                    transactions.invoice_no,
                    (
                        SELECT t2.invoice_no
                        FROM transaction_payments AS tp_child
                        LEFT JOIN transactions AS t2 ON t2.id = tp_child.transaction_id
                        WHERE tp_child.parent_id = transaction_payments.id
                        LIMIT 1
                    )
                ) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS bill_no'),
                DB::raw('CONVERT(air_ticket_invoices.airticket_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as airticket_no'),
                DB::raw('CONVERT(transaction_payments.paid_in_type USING utf8mb4) COLLATE utf8mb4_unicode_ci as paid_in_type'),
                DB::raw('CASE WHEN settlements.id IS NOT NULL THEN 1 ELSE NULL END as is_settlement_customer_payment'),
                DB::raw('CONVERT(pump_operators.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as pump_operator'),
                DB::raw('CONVERT(transaction_payments.note USING utf8mb4) COLLATE utf8mb4_unicode_ci as note'),
                DB::raw('CONVERT(settlements.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as settlement_no'),
                DB::raw('CONVERT(customer_statements.statement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as statement_no'),
                'transactions.rp_earned',
                'transactions.rp_redeemed',
                DB::raw("'credit' COLLATE utf8mb4_unicode_ci as acc_transaction_type"),
                DB::raw('NULL as sub_type'),
            ]);

        $bulkPmts = (clone $pmtsBase)
            ->where('transaction_payments.paid_in_type', 'customer_bulk')
            ->groupBy('transaction_payments.payment_ref_no')
            ->select([
                DB::raw('MIN(transactions.id) as id'),
                DB::raw('MIN(transaction_payments.paid_on) as date'),
                DB::raw('"payment" COLLATE utf8mb4_unicode_ci as type'),
                DB::raw('CONVERT(MAX(transactions.type) USING utf8mb4) COLLATE utf8mb4_unicode_ci as transaction_type'),
                DB::raw('CONVERT(transaction_payments.payment_ref_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as invoice_no'),
                DB::raw('CONVERT(MAX(contact_ledgers.page) USING utf8mb4) COLLATE utf8mb4_unicode_ci as page'),
                DB::raw('CONVERT(MAX(business_locations.name) USING utf8mb4) COLLATE utf8mb4_unicode_ci as location_name'),
                DB::raw("CONVERT(CASE WHEN SUM(CASE WHEN transactions.payment_status = 'paid' THEN 1 ELSE 0 END) = COUNT(DISTINCT transactions.id) THEN 'paid' ELSE 'partial' END USING utf8mb4) COLLATE utf8mb4_unicode_ci as payment_status"),
                DB::raw('SUM(transaction_payments.amount) as amount'),
                DB::raw('NULL as ch_charges'),
                DB::raw('MIN(transaction_payments.id) as payment_row'),
                DB::raw('CONVERT(MIN(transaction_payments.account_id) USING utf8mb4) COLLATE utf8mb4_unicode_ci as account_id'),
                DB::raw('MAX(transaction_payments.deleted_at) as deleted_at'),
                DB::raw('MAX(transaction_payments.deleted_by) as deleted_by'),
                DB::raw('MIN(transaction_payments.created_at) as created_at'),
                DB::raw('NULL as bill_no'),
                DB::raw('CONVERT(MAX(air_ticket_invoices.airticket_no) USING utf8mb4) COLLATE utf8mb4_unicode_ci as airticket_no'),
                DB::raw("'customer_bulk' COLLATE utf8mb4_unicode_ci as paid_in_type"),
                DB::raw('CASE WHEN MAX(settlements.id) IS NOT NULL THEN 1 ELSE NULL END as is_settlement_customer_payment'),
                DB::raw('CONVERT(MAX(pump_operators.name) USING utf8mb4) COLLATE utf8mb4_unicode_ci as pump_operator'),
                DB::raw('CONVERT(MAX(transaction_payments.note) USING utf8mb4) COLLATE utf8mb4_unicode_ci as note'),
                DB::raw('CONVERT(MAX(settlements.settlement_no) USING utf8mb4) COLLATE utf8mb4_unicode_ci as settlement_no'),
                DB::raw('CONVERT(MAX(customer_statements.statement_no) USING utf8mb4) COLLATE utf8mb4_unicode_ci as statement_no'),
                DB::raw('NULL as rp_earned'),
                DB::raw('NULL as rp_redeemed'),
                DB::raw("'credit' COLLATE utf8mb4_unicode_ci as acc_transaction_type"),
                DB::raw('NULL as sub_type'),
            ]);

        $pmts = $pmtsNonBulk->unionAll($bulkPmts);

        $customer_payments = CustomerPayment::leftjoin('settlements', 'customer_payments.settlement_no', 'settlements.id')
            ->leftjoin('transactions', function ($join) {
                $join->on('transactions.invoice_no', '=', 'settlements.settlement_no')
                    ->where('transactions.sub_type', '=', 'settlement');
            })
            ->leftjoin('air_ticket_invoices', 'transactions.id', 'air_ticket_invoices.transaction_id')
            ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->leftjoin('transaction_payments', function($join) {
                $join->on('transaction_payments.transaction_id', '=', 'transactions.id')
                    ->whereNull('transaction_payments.parent_id');
            })
            ->leftjoin('contact_ledgers', 'contact_ledgers.transaction_payment_id', 'transaction_payments.id')
            ->leftJoin('pump_operators', 'settlements.pump_operator_id', '=', 'pump_operators.id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('customer_statement_details', function($join) {
                $join->on('customer_statement_details.transaction_id', '=', 'settlements.settlement_no')
                    ->whereRaw('customer_statement_details.id = (
                        SELECT MIN(csd.id)
                        FROM customer_statement_details AS csd
                        WHERE csd.transaction_id = settlements.settlement_no
                    )');
            })
            ->leftJoin('customer_statements', 'customer_statement_details.statement_id', '=', 'customer_statements.id')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('contacts.manual_bill_settlement')
                        ->orWhere('contacts.manual_bill_settlement', 0);
                })
                    ->orWhere(function ($q) {
                        $q->where('contacts.manual_bill_settlement', 1)
                            ->where('transactions.invoice_no', 'LIKE', 'INV%');
                    });
            })
            ->whereDate('settlements.transaction_date', '>=', $start_date)
            ->whereDate('settlements.transaction_date', '<=', $end_date)
            ->where('customer_payments.customer_id', $contact_id)
            ->when($is_walking_customer, function ($query) {
                return $query->whereIn('customer_payments.payment_method', ['cash', 'card']);
            })
            ->select([
                'transactions.id',
                'settlements.transaction_date as date',
                DB::raw('"customer_payment" COLLATE utf8mb4_unicode_ci as type'),
                DB::raw('CONVERT(transactions.type USING utf8mb4) COLLATE utf8mb4_unicode_ci as transaction_type'),
                DB::raw('CONVERT(settlements.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as invoice_no'),
                'contact_ledgers.page',
                DB::raw('CONVERT(business_locations.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as location_name'),
                DB::raw('CONVERT(transactions.payment_status USING utf8mb4) COLLATE utf8mb4_unicode_ci as payment_status'),
                'customer_payments.amount as amount',
                DB::raw('NULL as ch_charges'),
                DB::raw('customer_payments.id as payment_row'),
                DB::raw('CONVERT(COALESCE(customer_payments.payment_method, customer_payments.bank_name) USING utf8mb4) COLLATE utf8mb4_unicode_ci as account_id'),
                DB::raw('NULL as deleted_at'),
                DB::raw('NULL as deleted_by'),
                'customer_payments.created_at as created_at',
                DB::raw('CONVERT(settlements.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as bill_no'),
                DB::raw('CONVERT(air_ticket_invoices.airticket_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as airticket_no'),
                DB::raw('NULL as paid_in_type'),
                DB::raw('CASE WHEN settlements.id IS NOT NULL THEN 1 ELSE NULL END as is_settlement_customer_payment'),
                DB::raw('CONVERT(pump_operators.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as pump_operator'),
                DB::raw('CONVERT(transaction_payments.note USING utf8mb4) COLLATE utf8mb4_unicode_ci as note'),
                DB::raw('CONVERT(settlements.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as settlement_no'),
                DB::raw('CONVERT(customer_statements.statement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as statement_no'),
                'transactions.rp_earned',
                'transactions.rp_redeemed',
                DB::raw("'credit' COLLATE utf8mb4_unicode_ci as acc_transaction_type"),
                DB::raw('NULL as sub_type'),
            ])
            ->groupBy('customer_payments.id');

        $pointsTxn = Transaction::leftJoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->leftJoin('air_ticket_invoices', 'air_ticket_invoices.transaction_id', 'transactions.id')
            ->leftJoin('settlements', 'settlements.settlement_no', 'transactions.invoice_no')
            ->leftJoin('pump_operators', 'pump_operators.id', 'settlements.pump_operator_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.contact_id', $contact_id)
            ->whereIn('transactions.type', ['points_earned', 'points_redeemed'])
            ->whereNull('transactions.deleted_at')
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select([
                'transactions.id',
                'transactions.transaction_date as date',
                DB::raw('"points_redeemed" COLLATE utf8mb4_unicode_ci as type'),
                DB::raw('CONVERT(transactions.type USING utf8mb4) COLLATE utf8mb4_unicode_ci as transaction_type'),
                DB::raw('CONVERT(transactions.ref_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as invoice_no'),
                DB::raw('NULL as page'),
                DB::raw('CONVERT(business_locations.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as location_name'),
                DB::raw("'paid' COLLATE utf8mb4_unicode_ci as payment_status"),
                DB::raw('transactions.rp_earned - transactions.rp_redeemed as amount'),
                DB::raw('NULL as ch_charges'),
                'transactions.id as payment_row',
                DB::raw('NULL as account_id'),
                'transactions.deleted_at',
                'transactions.deleted_by',
                'transactions.created_at as created_at',
                DB::raw('CONVERT(transactions.ref_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as bill_no'),
                DB::raw('CONVERT(air_ticket_invoices.airticket_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as airticket_no'),
                DB::raw('NULL as paid_in_type'),
                DB::raw('CASE WHEN settlements.id IS NOT NULL THEN 1 ELSE NULL END as is_settlement_customer_payment'),
                DB::raw('CONVERT(pump_operators.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as pump_operator'),
                DB::raw("CONVERT(CASE WHEN transactions.type = 'points_redeemed' THEN CONCAT('Points Redeemed. Bill No. ', transactions.ref_no) 
                   WHEN transactions.type = 'points_earned' THEN CONCAT('Add Points Form No. ', transactions.ref_no) 
                   ELSE NULL END USING utf8mb4) COLLATE utf8mb4_unicode_ci as note"),
                DB::raw('CONVERT(settlements.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as settlement_no'),
                DB::raw('NULL as statement_no'),
                'transactions.rp_earned',
                'transactions.rp_redeemed',
                DB::raw("CONVERT(CASE 
                    WHEN transactions.type = 'points_redeemed' THEN 'credit'
                    WHEN transactions.type = 'points_earned' THEN 'debit'
                    ELSE 'debit'
                END USING utf8mb4) COLLATE utf8mb4_unicode_ci as acc_transaction_type"),
                DB::raw('NULL as sub_type'),
            ]);

        // Add cheque return charges from contact_ledgers
        $chequeReturnCharges = \App\ContactLedger::leftJoin('transactions', 'contact_ledgers.transaction_id', 'transactions.id')
            ->leftJoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->leftJoin('account_transactions', function($join) {
                $join->on('account_transactions.transaction_id', '=', 'transactions.id')
                    ->where('account_transactions.sub_type', '=', 'cheque_return_charges');
            })
            ->leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')
            ->where('contact_ledgers.contact_id', $contact_id)
            ->where('transactions.business_id', $business_id)
            ->where('contact_ledgers.sub_type', 'cheque_return_charges')
            ->whereNull('contact_ledgers.deleted_at')
            ->whereDate('contact_ledgers.operation_date', '>=', $start_date)
            ->whereDate('contact_ledgers.operation_date', '<=', $end_date)
            ->select([
                'contact_ledgers.id',
                'contact_ledgers.operation_date as date',
                DB::raw('"cheque_return_charges" COLLATE utf8mb4_unicode_ci as type'),
                DB::raw('"cheque_return_charges" COLLATE utf8mb4_unicode_ci as transaction_type'),
                DB::raw('CONVERT(transactions.invoice_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as invoice_no'),
                DB::raw('NULL as page'),
                DB::raw('CONVERT(business_locations.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as location_name'),
                DB::raw('NULL as payment_status'),
                'contact_ledgers.amount as amount',
                'contact_ledgers.amount as ch_charges',
                DB::raw('NULL as payment_row'),
                DB::raw('NULL as account_id'),
                'contact_ledgers.deleted_at',
                DB::raw('NULL as deleted_by'),
                'contact_ledgers.created_at as created_at',
                DB::raw('CONVERT(transactions.ref_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as bill_no'),
                DB::raw('NULL as airticket_no'),
                DB::raw('NULL as paid_in_type'),
                DB::raw('NULL as is_settlement_customer_payment'),
                DB::raw('NULL as pump_operator'),
                DB::raw('NULL as note'),
                DB::raw('NULL as settlement_no'),
                DB::raw('NULL as statement_no'),
                DB::raw('NULL as rp_earned'),
                DB::raw('NULL as rp_redeemed'),
                DB::raw("'debit' COLLATE utf8mb4_unicode_ci as acc_transaction_type"),
                DB::raw("'cheque_return_charges' COLLATE utf8mb4_unicode_ci as sub_type"),
            ]);

        // Settlement payments may exist in contact_ledgers as a fallback source.
        // For regular customers, only include rows that are not already linked to a transaction payment
        // so the same customer payment does not appear twice in the ledger.
        $settlementCashCardPayments = \App\ContactLedger::leftJoin('contacts', 'contacts.id', '=', 'contact_ledgers.contact_id')
            ->leftJoin('transactions', 'transactions.id', '=', 'contact_ledgers.transaction_id')
            ->leftJoin('settlements', 'settlements.settlement_no', '=', 'transactions.invoice_no')
            ->where('contact_ledgers.contact_id', $contact_id)
            ->where('contacts.business_id', $business_id)
            ->where(function ($query) use ($is_walking_customer) {
                if ($is_walking_customer) {
                    $query->where(function ($q) {
                        $q->whereIn('contact_ledgers.sub_type', [
                            'cash_payment', 'card_payment', 'settlement_cash_payment', 'settlement_card_payment', 'cash_deposit', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit',
                        ])
                            ->orWhere(function ($q2) {
                                $q2->whereIn('transactions.sub_type', ['cash_payment', 'card_payment', 'cash_deposit'])
                                    ->whereIn('contact_ledgers.type', ['debit', 'credit']);
                            });
                    });
                } else {
                    // Do not use orWhereNull(sub_type): AR / mirror rows often have NULL sub_type and show as "Sale / Paid"
                    // in the ledger (debit CL → type sell) while Total paid stays 0 (Doc 7912).
                    $query->whereIn('contact_ledgers.sub_type', ['cash_payment', 'card_payment', 'cheque_payment', 'cash_deposit', 'settlement_cash_payment', 'settlement_card_payment', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit'])
                        ->orWhere(function ($q2) {
                            $q2->whereIn('transactions.sub_type', ['cash_payment', 'card_payment', 'cheque_payment', 'cash_deposit', 'settlement_cash_payment', 'settlement_card_payment', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit'])
                                ->where('contact_ledgers.type', 'credit');
                        });
                }
            })
            ->where(function ($query) use ($contact_id, $is_walking_customer) {
                if ($is_walking_customer) {
                    // S276: Walk-in Customer ledger must show both debit and credit mirror rows
                    // created by PD/settlement cash-card collections.
                    $query->whereIn('contact_ledgers.type', ['debit', 'credit']);
                    return;
                }

                $query->whereNull('contact_ledgers.transaction_payment_id')
                    ->orWhereNotExists(function ($q) use ($contact_id) {
                        $q->select(DB::raw(1))
                            ->from('transaction_payments as tp')
                            ->whereRaw('tp.id = contact_ledgers.transaction_payment_id')
                            ->where('tp.payment_for', $contact_id);
                    });
            })
            ->whereNull('contact_ledgers.deleted_at')
            // Doc 7912: never treat missing join or sell/settlement/credit_sale parents as settlement cash/card lines.
            ->where(function ($q) use ($is_walking_customer) {
                if ($is_walking_customer) {
                    // For Walk-in Customer, include PD settlement debit+credit mirror rows even when linked to a settlement transaction.
                    $q->whereIn('contact_ledgers.sub_type', [
                        'cash_payment', 'card_payment', 'settlement_cash_payment', 'settlement_card_payment', 'cash_deposit', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit',
                    ])->orWhereIn('transactions.sub_type', ['cash_payment', 'card_payment', 'cash_deposit']);
                    return;
                }

                $q->where(function ($q2) {
                    $q2->whereNull('contact_ledgers.transaction_id')
                        ->whereIn('contact_ledgers.sub_type', ['cash_payment', 'card_payment', 'cheque_payment', 'cash_deposit', 'settlement_cash_payment', 'settlement_card_payment', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit']);
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('contact_ledgers.transaction_id')
                        ->whereNotNull('transactions.id')
                        ->whereRaw('LOWER(IFNULL(transactions.sub_type, "")) NOT IN ("credit_sale", "settlement")')
                        ->whereRaw('(LOWER(IFNULL(transactions.type, "")) != "settlement" OR LOWER(IFNULL(transactions.sub_type, "")) = "cash_deposit")')
                        ->whereRaw('(IFNULL(transactions.is_settlement, 0) != 1 OR LOWER(IFNULL(transactions.sub_type, "")) = "cash_deposit")')
                        ->whereRaw('IFNULL(transactions.is_credit_sale, 0) != 1');
                });
            })
            ->whereDate('contact_ledgers.operation_date', '>=', $start_date)
            ->whereDate('contact_ledgers.operation_date', '<=', $end_date)
            ->select([
                'contact_ledgers.id',
                'contact_ledgers.operation_date as date',
                DB::raw("CASE WHEN contact_ledgers.type = 'debit' THEN 'sell' ELSE 'payment' END COLLATE utf8mb4_unicode_ci as type"),
                DB::raw('CONVERT(contact_ledgers.sub_type USING utf8mb4) COLLATE utf8mb4_unicode_ci as transaction_type'),
                DB::raw('NULL as invoice_no'),
                'contact_ledgers.page',
                DB::raw('NULL as location_name'),
                DB::raw("'paid' COLLATE utf8mb4_unicode_ci as payment_status"),
                'contact_ledgers.amount as amount',
                DB::raw('NULL as ch_charges'),
                DB::raw('NULL as payment_row'),
                DB::raw('NULL as account_id'),
                'contact_ledgers.deleted_at',
                DB::raw('NULL as deleted_by'),
                'contact_ledgers.created_at as created_at',
                DB::raw('NULL as bill_no'),
                DB::raw('NULL as airticket_no'),
                DB::raw('NULL as paid_in_type'),
                DB::raw('1 as is_settlement_customer_payment'),
                DB::raw('NULL as pump_operator'),
                DB::raw("CONVERT(CASE                     WHEN contact_ledgers.note LIKE 'Invoice No / Bill no / settlement No:%' THEN contact_ledgers.note                     WHEN contact_ledgers.note REGEXP 'PDST[0-9A-Za-z\\-\\/]+' THEN CONCAT('Invoice No / Bill no / settlement No: ', SUBSTRING(contact_ledgers.note, LOCATE('PDST', contact_ledgers.note)))                     ELSE CONCAT('Invoice No / Bill no / settlement No: ', IFNULL(contact_ledgers.note, 'N/A'))                 END USING utf8mb4) COLLATE utf8mb4_unicode_ci as note"),
                DB::raw('NULL as settlement_no'),
                DB::raw('NULL as statement_no'),
                DB::raw('NULL as rp_earned'),
                DB::raw('NULL as rp_redeemed'),
                DB::raw('CONVERT(contact_ledgers.type USING utf8mb4) COLLATE utf8mb4_unicode_ci as acc_transaction_type'),
                DB::raw('CONVERT(contact_ledgers.sub_type USING utf8mb4) COLLATE utf8mb4_unicode_ci as sub_type'),
            ]);

        $vatInvoices = \App\ContactLedger::leftJoin('contacts', 'contacts.id', '=', 'contact_ledgers.contact_id')
            ->where('contact_ledgers.contact_id', $contact_id)
            ->where('contacts.business_id', $business_id)
            ->where('contact_ledgers.note', 'like', 'VAT Invoice No %')
            ->whereNull('contact_ledgers.deleted_at')
            ->whereDate('contact_ledgers.operation_date', '>=', $start_date)
            ->whereDate('contact_ledgers.operation_date', '<=', $end_date)
            ->select([
                'contact_ledgers.id',
                'contact_ledgers.operation_date as date',
                DB::raw("'sell' COLLATE utf8mb4_unicode_ci as type"),
                DB::raw("'vat_invoice' COLLATE utf8mb4_unicode_ci as transaction_type"),
                DB::raw('NULL as invoice_no'),
                DB::raw('NULL as page'),
                DB::raw('NULL as location_name'),
                DB::raw("'paid' COLLATE utf8mb4_unicode_ci as payment_status"),
                'contact_ledgers.amount as amount',
                DB::raw('NULL as ch_charges'),
                DB::raw('NULL as payment_row'),
                DB::raw('NULL as account_id'),
                'contact_ledgers.deleted_at',
                DB::raw('NULL as deleted_by'),
                'contact_ledgers.created_at as created_at',
                DB::raw('NULL as bill_no'),
                DB::raw('NULL as airticket_no'),
                DB::raw('NULL as paid_in_type'),
                DB::raw('NULL as is_settlement_customer_payment'),
                DB::raw('NULL as pump_operator'),
                DB::raw('CONVERT(contact_ledgers.note USING utf8mb4) COLLATE utf8mb4_unicode_ci as note'),
                DB::raw('NULL as settlement_no'),
                DB::raw('NULL as statement_no'),
                DB::raw('NULL as rp_earned'),
                DB::raw('NULL as rp_redeemed'),
                DB::raw("CONVERT(contact_ledgers.type USING utf8mb4) COLLATE utf8mb4_unicode_ci as acc_transaction_type"),
                DB::raw("'vat_invoice' COLLATE utf8mb4_unicode_ci as sub_type"),
            ]);

        // IS1483: Ledger must follow transaction date order even for back-dated entries.
        // The final stable order is: transaction/operation date, created time, then row id.
        $txnResult = $txns->unionAll($pmts)
            ->unionAll($customer_payments)
            ->unionAll($pointsTxn)
            ->unionAll($chequeReturnCharges)
            ->unionAll($settlementCashCardPayments)
            ->unionAll($vatInvoices)
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        return $this->deduplicateLedgerTransactions($txnResult->get());
    }

    public function getCustomerLedgerList($contact_id, $business_id, $start_date, $end_date)
    {

        $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->leftjoin('air_ticket_invoices', 'transactions.id', 'air_ticket_invoices.transaction_id')
            ->leftjoin('account_transactions', 'account_transactions.transaction_id', 'transactions.id')
            ->where(function ($query) {
                $query->whereIn('transactions.type', $this->transaction_types)
                    ->orWhere(function ($query) {
                        $query->where('transactions.type', 'settlement')->where('transactions.sub_type', 'customer_loan');
                    })
                    ->orWhere(function ($query) {
                        $query->where('transactions.type', 'refund');
                    });
            })
            ->where(function ($query) {
                $query->where('transactions.sub_type', '!=', 'settlement')
                    ->orWhereNull('transactions.sub_type');
            })
            ->whereNull('transactions.deleted_at')
            ->whereIn('transactions.status', ['final', 'received'])
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select([
                'transactions.id',
                'transactions.transaction_date as date',
                DB::raw("CASE WHEN transactions.type = 'hms_booking' THEN 'sell' ELSE transactions.type END COLLATE utf8mb4_unicode_ci as type"),
                DB::raw('transactions.invoice_no COLLATE utf8mb4_unicode_ci as invoice_no'),
                DB::raw('business_locations.name COLLATE utf8mb4_unicode_ci as location_name'),
                'transactions.payment_status as payment_status',
                'transactions.final_total as amount',
                'transactions.cheque_return_charges as ch_charges',
                DB::raw('NULL as payment_row'),
                DB::raw('NULL as account_id'),
                'transactions.deleted_at',
                'transactions.deleted_by',
                'transactions.created_at as created_at',
                DB::raw('transactions.ref_no COLLATE utf8mb4_unicode_ci as bill_no'),
                'air_ticket_invoices.airticket_no as airticket_no',
                'account_transactions.cheque_number as cheque_number',
                'account_transactions.operation_date as operation_date',
                'account_transactions.bank_name as bank_name',
                DB::raw('NULL as paid_in_type'),
                DB::raw('NULL as is_settlement_customer_payment'),
                DB::raw('NULL as pump_operator'),
            ])->groupBy(['transactions.id']); // Ensure unique records

        $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
            ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->leftjoin('air_ticket_invoices', 'transactions.id', 'air_ticket_invoices.transaction_id')
            ->leftjoin('account_transactions', 'account_transactions.transaction_id', 'transactions.id') // Direct join with account_transactions table
            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('transaction_payments.parent_id')
            ->where(function ($query) {
                $query->whereNull('transaction_payments.transaction_id')
                    ->orWhere(function ($query) {
                        $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                    });
            })
            ->where('transaction_payments.payment_for', $contact_id)
            ->whereDate('transaction_payments.paid_on', '>=', $start_date)
            ->whereDate('transaction_payments.paid_on', '<=', $end_date)
            ->withTrashed()
            ->select([
                'transactions.id',
                'transaction_payments.paid_on as date',
                DB::raw('"payment" as type'),
                'transaction_payments.payment_ref_no as invoice_no',
                'business_locations.name as location_name',
                'transactions.payment_status as payment_status',
                'transaction_payments.amount as amount',
                DB::raw('NULL as ch_charges'),
                DB::raw('transaction_payments.id as payment_row'),
                DB::raw('transaction_payments.account_id as account_id'),
                'transaction_payments.deleted_at',
                'transaction_payments.deleted_by',
                'transaction_payments.created_at as created_at',
                'transaction_payments.payment_ref_no as bill_no',
                'air_ticket_invoices.airticket_no as airticket_no',
                'account_transactions.cheque_number as cheque_number',
                'account_transactions.operation_date as operation_date',
                'account_transactions.bank_name as bank_name',
                'transaction_payments.paid_in_type',
                DB::raw('NULL as is_settlement_customer_payment'),
                DB::raw('NULL as pump_operator'),
            ])->groupBy(['transactions.id']); // Ensure unique records;

        $customer_payments = CustomerPayment::leftjoin('settlements', 'customer_payments.settlement_no', 'settlements.id')
            ->leftjoin('transactions', function ($join) {
                $join->on('transactions.invoice_no', '=', 'settlements.settlement_no')
                    ->where('transactions.sub_type', '=', 'settlement');
            })
            ->leftjoin('air_ticket_invoices', 'transactions.id', 'air_ticket_invoices.transaction_id')
            ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->leftJoin('pump_operators', 'settlements.pump_operator_id', '=', 'pump_operators.id')
            ->leftjoin('account_transactions', 'account_transactions.transaction_id', 'transactions.id') // Direct join with account_transactions table
            ->whereDate('settlements.transaction_date', '>=', $start_date)
            ->whereDate('settlements.transaction_date', '<=', $end_date)
            ->where('customer_payments.customer_id', $contact_id)
            ->select([
                'transactions.id',
                'settlements.transaction_date as date',
                DB::raw('"customer_payment" as type'),
                'settlements.settlement_no as invoice_no',
                'business_locations.name as location_name',
                'transactions.payment_status as payment_status',
                'customer_payments.amount as amount',
                DB::raw('NULL as ch_charges'),
                DB::raw('customer_payments.id as payment_row'),
                DB::raw('COALESCE(customer_payments.payment_method, customer_payments.bank_name) as account_id'),
                DB::raw('NULL as deleted_at'),
                DB::raw('NULL as deleted_by'),
                'customer_payments.created_at as created_at',
                'settlements.settlement_no as bill_no',
                'air_ticket_invoices.airticket_no as airticket_no',
                DB::raw('NULL as paid_in_type'),
                DB::raw('"1" as is_settlement_customer_payment'),
                DB::raw('pump_operators.name as pump_operator'),
                'account_transactions.cheque_number as cheque_number',
                'account_transactions.operation_date as operation_date',
                'account_transactions.bank_name as bank_name',
            ])->groupBy('customer_payments.id', 'transactions.id'); // Ensure unique records;

        $settlementCashCardPayments = ContactLedger::leftJoin('contacts', 'contacts.id', '=', 'contact_ledgers.contact_id')
            ->where('contact_ledgers.contact_id', $contact_id)
            ->where('contacts.business_id', $business_id)
            ->whereIn('contact_ledgers.sub_type', ['cash_payment', 'card_payment', 'cheque_payment', 'cash_deposit', 'settlement_cash_payment', 'settlement_card_payment', 'pd_walkin_sale', 'pd_walkin_payment', 'pd_walkin_cash_debit', 'pd_walkin_cash_credit', 'pd_walkin_card_debit', 'pd_walkin_card_credit'])
            ->whereNull('contact_ledgers.transaction_payment_id')
            ->whereNull('contact_ledgers.deleted_at')
            ->whereDate('contact_ledgers.operation_date', '>=', $start_date)
            ->whereDate('contact_ledgers.operation_date', '<=', $end_date)
            ->select([
                'contact_ledgers.id',
                'contact_ledgers.operation_date as date',
                DB::raw('"payment" as type'),
                DB::raw('NULL as invoice_no'),
                DB::raw('NULL as location_name'),
                DB::raw('"paid" as payment_status'),
                'contact_ledgers.amount as amount',
                DB::raw('NULL as ch_charges'),
                DB::raw('NULL as payment_row'),
                DB::raw('NULL as account_id'),
                'contact_ledgers.deleted_at',
                DB::raw('NULL as deleted_by'),
                'contact_ledgers.created_at as created_at',
                DB::raw('NULL as bill_no'),
                DB::raw('NULL as airticket_no'),
                DB::raw('NULL as cheque_number'),
                DB::raw('NULL as operation_date'),
                DB::raw('NULL as bank_name'),
                DB::raw('NULL as paid_in_type'),
                DB::raw('"1" as is_settlement_customer_payment'),
                DB::raw('NULL as pump_operator'),
            ]);

        $txnResult = $txns->unionAll($pmts)->unionAll($customer_payments)->unionAll($settlementCashCardPayments)->orderBy('id', 'asc');

        $transactions = $txnResult->get();

        return $this->deduplicateLedgerTransactions($transactions);
    }

    private function deduplicateLedgerTransactions($transactions)
    {
        return $transactions->unique(function ($row) {
            if (in_array($row->type ?? null, ['payment', 'customer_payment']) && ! empty($row->payment_row)) {
                return ($row->type ?? '') . '|payment_row|' . $row->payment_row;
            }

            return implode('|', [
                $row->type ?? '',
                $row->id ?? '',
                $row->payment_row ?? '',
                $row->invoice_no ?? '',
                $row->date ?? '',
                $row->amount ?? '',
                $row->created_at ?? '',
            ]);
        })->values();
    }

    public function getSupplierLedger($contact_id, $business_id, $start_date, $end_date)
    {
        $txns = Transaction::leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->leftjoin('contact_ledgers as pr_cl', function($join) {
                $join->on('pr_cl.transaction_id', '=', 'transactions.id')
                    ->where('pr_cl.type', '=', 'purchase_return')
                    ->whereNull('pr_cl.deleted_at');
            })
            ->leftjoin('account_transactions as pr_at', function($join) {
                $join->on('pr_at.transaction_id', '=', 'transactions.id')
                    ->where('pr_at.type', '=', 'debit')
                    // ->whereNull('pr_at.transaction_payment_id')
                    ->whereNull('pr_at.deleted_at');
            })
            ->leftjoin('accounts as pr_acc', 'pr_acc.id', '=', 'pr_at.account_id')
            ->leftjoin('contact_ledgers as journal_cl', function($join) use ($contact_id) {
                $join->on('journal_cl.transaction_id', '=', 'transactions.id')
                    ->where('journal_cl.contact_id', '=', $contact_id)
                    ->whereNull('journal_cl.deleted_at');
            })
            ->where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', $this->supplier_types)
            ->whereNull('transactions.deleted_at')
            ->whereIn('transactions.status', $this->supplier_balance_statuses)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->where('transactions.contact_id', $contact_id)
            ->select([
                'transactions.id',
                'transactions.transaction_date as date',

                DB::raw('transactions.type COLLATE utf8mb4_unicode_ci as type'),

                DB::raw('transactions.ref_no COLLATE utf8mb4_unicode_ci as invoice_no'),

                DB::raw('business_locations.name COLLATE utf8mb4_unicode_ci as location_name'),

                'transactions.payment_status',

                DB::raw("CASE WHEN transactions.type = 'ledger' THEN journal_cl.amount ELSE transactions.final_total END as amount"),

                DB::raw('NULL as payment_row'),
                DB::raw('NULL as account_id'),

                'transactions.created_at as created_at',

                DB::raw('transactions.invoice_no COLLATE utf8mb4_unicode_ci as bill_no'),

                'transactions.new_deleted_parent_id',

                DB::raw("
                    COALESCE(
                        pr_cl.note,
                        pr_at.note,
                        CASE
                            WHEN pr_acc.name IS NOT NULL THEN CONCAT(
                                'Purchase Return Account: ',
                                pr_acc.name,
                                CASE
                                    WHEN pr_at.cheque_number IS NOT NULL AND pr_at.cheque_number != '' THEN CONCAT(
                                        ' | Cheque: ',
                                        pr_at.cheque_number,
                                        CASE
                                            WHEN pr_at.bank_name IS NOT NULL AND pr_at.bank_name != '' THEN CONCAT(' - ', pr_at.bank_name)
                                            ELSE ''
                                        END
                                    )
                                    ELSE ''
                                END
                            )
                            ELSE NULL
                        END,
                        transactions.additional_notes
                    ) COLLATE utf8mb4_unicode_ci as payment_details
                "),

                DB::raw('pr_at.cheque_number COLLATE utf8mb4_unicode_ci as cheque_number'),
                DB::raw('pr_at.bank_name COLLATE utf8mb4_unicode_ci as bank_name'),

                'pr_at.cheque_date',
                DB::raw("CONVERT(CASE WHEN transactions.type = 'ledger' THEN journal_cl.type ELSE 'debit' END USING utf8mb4) COLLATE utf8mb4_unicode_ci as acc_transaction_type"),
                DB::raw("CONVERT(CASE WHEN transactions.type = 'ledger' THEN COALESCE(journal_cl.note, transactions.additional_notes) ELSE NULL END USING utf8mb4) COLLATE utf8mb4_unicode_ci as note"),
            ]);

        $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
            ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('transaction_payments.deleted_at')
            ->whereNull('transaction_payments.parent_id')
            ->where(function ($query) {
                $query->whereNull('transaction_payments.transaction_id')
                    ->orWhere(function ($query) {
                        $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund', 'cheque_opening_balance']);
                    });
            })
            ->where('transaction_payments.payment_for', $contact_id)
            ->whereDate('transaction_payments.paid_on', '>=', $start_date)
            ->whereDate('transaction_payments.paid_on', '<=', $end_date)
            ->select([
                'transactions.id',
                'transaction_payments.paid_on as date',

                DB::raw('"payment" COLLATE utf8mb4_unicode_ci as type'),

                DB::raw('transaction_payments.payment_ref_no COLLATE utf8mb4_unicode_ci as invoice_no'),

                DB::raw('business_locations.name COLLATE utf8mb4_unicode_ci as location_name'),

                DB::raw('NULL as payment_status'),

                'transaction_payments.amount as amount',

                DB::raw('transaction_payments.id as payment_row'),
                DB::raw('transaction_payments.account_id as account_id'),

                'transaction_payments.created_at',

                DB::raw('transaction_payments.payment_ref_no COLLATE utf8mb4_unicode_ci as bill_no'),

                'transactions.new_deleted_parent_id',

                DB::raw('CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci as payment_details'),

                DB::raw('CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci as cheque_number'),

                DB::raw('CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci as bank_name'),

                DB::raw('NULL as cheque_date'),
                DB::raw('CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci as acc_transaction_type'),
                DB::raw('CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci as note'),
            ]);


        $txnResult = $txns->unionAll($pmts)
            /*
             * LA1061 FIX:
             * Customer Ledger must be ordered by the actual transaction/payment date.
             * Do not use created_at as the secondary sort because back-dated payments
             * entered later were being pushed to the bottom.
             */
            // IS1485: ledger must follow selected Transaction Date order; stable by entered time/id.
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        return $txnResult->get();
    }

    public function getWalkInCustomer($business_id)
    {
        $contact = Contact::where('type', 'customer')
            ->where('business_id', $business_id)
            ->where('is_default', 1)
            ->first();

        if (!empty($contact)) {
            return $contact->toArray();
        } else {
            return false;
        }
    }
    /**
     * Returns Walk In Supplier for a Business
     *
     * @param int $business_id
     *
     * @return array/false
     */
    public function getDefaultSupplier($business_id)
    {
        $contact = Contact::where('type', 'supplier')
            ->where('business_id', $business_id)
            ->where('is_default', 1)
            ->first();

        if (!empty($contact)) {
            return $contact->toArray();
        } else {
            return false;
        }
    }

    /**
     * Returns the customer group
     *
     * @param int $business_id
     * @param int $customer_id
     *
     * @return array
     */
    public function getCustomerGroup($business_id, $customer_id)
    {
        $cg = [];

        if (empty($customer_id)) {
            return $cg;
        }

        $contact = Contact::leftjoin('contact_groups as CG', 'contacts.customer_group_id', 'CG.id')
            ->where('contacts.id', $customer_id)
            ->where('CG.type', 'customer')
            ->where('contacts.business_id', $business_id)
            ->select('CG.*')
            ->first();

        return $contact;
    }

    public function getContactDue($contact_id)
    {
        $contact_payments = Contact::where('contacts.id', $contact_id)
            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->whereIn('t.type', ['sell', 'opening_balance'])
            ->where('is_customer_order', 0)
            ->select(
                DB::raw("SUM(IF(t.status = 'final' AND t.type = 'sell', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF(t.status = 'final' AND t.type = 'sell', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as total_paid"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid")
            )->first();
        $due = $contact_payments->total_invoice - $contact_payments->total_paid + $contact_payments->opening_balance - $contact_payments->opening_balance_paid;
        return $due;
    }
}
