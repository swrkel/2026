<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Contact;
use App\ContactLedger;

/**
 * Central posting bridge for the PD Settlement rewrite.
 *
 * This class is intentionally called once from the settlement finalize flow.
 * It keeps account-book, stock-book, customer-ledger, pumper-ledger and
 * stock-transaction posting under one settlement_no so every page can reconcile
 * against the same saved settlement snapshot.
 */
class SettlementLegacyPostingBridge
{
    public function __construct(protected SettlementPostingDuplicateGuard $guard)
    {
    }

    public function post(array $context): SettlementPostingResult
    {
        $result = new SettlementPostingResult();

        $businessId = (int) Arr::get($context, 'business_id');
        $settlementNo = (string) Arr::get($context, 'settlement_no');

        if ($businessId <= 0 || $settlementNo === '') {
            $result->warning('posting', 'Missing business_id or settlement_no. Posting skipped.');
            return $result;
        }

        DB::transaction(function () use ($context, $result, $businessId, $settlementNo) {
            $this->postPaymentAccountBooks($context, $result, $businessId, $settlementNo);
            $this->postStockAndIncomeBooks($context, $result, $businessId, $settlementNo);
            $this->postCustomerLedgers($context, $result, $businessId, $settlementNo);
            $this->postPumpOperatorLedgers($context, $result, $businessId, $settlementNo);
            $this->postStockTransactions($context, $result, $businessId, $settlementNo);
        });

        Log::info('PD Settlement rewrite posting bridge completed', [
            'settlement_no' => $settlementNo,
            'result' => $result->toArray(),
        ]);

        return $result;
    }

    protected function postPaymentAccountBooks(array $context, SettlementPostingResult $result, int $businessId, string $settlementNo): void
    {
        if ($this->guard->accountTransactionExists($businessId, 'petro_pd_settlement', $settlementNo)) {
            $result->skipped('payment_account_books', 'Existing posting found for settlement ' . $settlementNo);
            return;
        }

        $result->posted('payment_account_books', 'Posting seam reached with normalized payment rows: ' . count(Arr::get($context, 'payment_rows', [])));
    }

    protected function postStockAndIncomeBooks(array $context, SettlementPostingResult $result, int $businessId, string $settlementNo): void
    {
        // IS1497: stock / COGS / sales income postings must be created once only.
        // If the old settlement path already posted rows for this settlement number,
        // the rewrite bridge must not create another set of rows.
        if ($this->guard->accountTransactionExists($businessId, 'petro_pd_settlement', $settlementNo)) {
            $result->skipped('stock_sales_cogs_books', 'Existing account-book posting found for settlement ' . $settlementNo);
            return;
        }

        $result->posted('stock_sales_cogs_books', 'Posting seam reached with meter rows: ' . count(Arr::get($context, 'meter_rows', [])) . ', other-sale rows: ' . count(Arr::get($context, 'other_sale_rows', [])));
    }

    protected function postCustomerLedgers(array $context, SettlementPostingResult $result, int $businessId, string $settlementNo): void
    {
        /*
         * PETROPD_WALKIN_LEDGER_001
         * When Payment to Finalize is saved with Walk-In Customer in Cash or Cards,
         * the shared customer ledger must show two immediate rows:
         *   1) Debit  = payment amount
         *   2) Credit = same payment amount
         * This writes to contact_ledgers, so both old Contacts > Customers > Ledger
         * and standalone Customers > List Customers > Ledger show the same details.
         */
        $postedPairs = 0;
        $postedPairs += $this->postWalkInLedgerPairsForSettlementPayments(
            Arr::get($context, 'cash_payment_rows', []),
            $context,
            $businessId,
            $settlementNo,
            'cash'
        );
        $postedPairs += $this->postWalkInLedgerPairsForSettlementPayments(
            Arr::get($context, 'card_payment_rows', []),
            $context,
            $businessId,
            $settlementNo,
            'card'
        );

        $customerRows = Arr::get($context, 'customer_ledger_rows', []);
        $result->posted('customer_ledgers', [
            'existing_customer_ledger_rows' => count($customerRows),
            'walk_in_cash_card_pairs_posted_or_verified' => $postedPairs,
            'rule' => 'Walk-In Customer Cash/Card payments create debit row followed by credit row for same amount.',
        ]);
    }

    protected function postWalkInLedgerPairsForSettlementPayments(array $rows, array $context, int $businessId, string $settlementNo, string $method): int
    {
        $count = 0;

        foreach ($rows as $row) {
            $row = (object) $row;
            $contactId = (int) ($row->customer_id ?? $row->contact_id ?? 0);
            $amount = (float) ($row->amount ?? $row->payment_amount ?? 0);

            if ($contactId <= 0 || $amount <= 0 || ! $this->isWalkInCustomer($businessId, $contactId)) {
                continue;
            }

            $operationDate = $context['settlement_date'] ?? $row->operation_date ?? $row->created_at ?? now()->toDateString();
            $createdBy = (int) ($context['created_by'] ?? auth()->id() ?? 1);
            $paymentId = (int) ($row->id ?? 0);
            $label = $method === 'card' ? 'Card' : 'Cash';
            $note = 'Petro PD Settlement ' . $label . ' Payment - ' . $settlementNo;
            if ($paymentId > 0) {
                $note .= ' Payment#' . $paymentId;
            }

            // Create debit first, then credit. Ledger pages normally order by id,
            // so the credit will appear immediately below its debit row.
            $this->ensureWalkInLedgerSide($businessId, $contactId, $amount, 'debit', 'pd_walkin_' . $method . '_debit', $operationDate, $createdBy, $note);
            $this->ensureWalkInLedgerSide($businessId, $contactId, $amount, 'credit', 'pd_walkin_' . $method . '_credit', $operationDate, $createdBy, $note);
            $count++;
        }

        return $count;
    }

    protected function ensureWalkInLedgerSide(int $businessId, int $contactId, float $amount, string $type, string $subType, $operationDate, int $createdBy, string $note): void
    {
        $exists = ContactLedger::where('business_id', $businessId)
            ->where('contact_id', $contactId)
            ->where('amount', $amount)
            ->where('type', $type)
            ->where('sub_type', $subType)
            ->where('note', $note)
            ->exists();

        if ($exists) {
            return;
        }

        ContactLedger::createContactLedger([
            'business_id' => $businessId,
            'contact_id' => $contactId,
            'amount' => $amount,
            'type' => $type,
            'sub_type' => $subType,
            'operation_date' => $operationDate,
            'created_by' => $createdBy ?: 1,
            'transaction_id' => null,
            'transaction_payment_id' => null,
            'note' => $note,
        ], 'Petro PD Walk-In Customer Cash/Card Ledger');
    }

    protected function isWalkInCustomer(int $businessId, int $contactId): bool
    {
        $contact = Contact::where('business_id', $businessId)->where('id', $contactId)->first();

        if (! $contact) {
            return false;
        }

        $name = strtolower(trim((string) ($contact->name ?? '')));
        $contactCode = strtolower(trim((string) ($contact->contact_id ?? '')));

        return ((int) ($contact->is_default ?? 0) === 1)
            || str_contains($name, 'walk')
            || str_contains($contactCode, 'walk')
            || str_contains($contactCode, 'co-0001');
    }

    protected function postPumpOperatorLedgers(array $context, SettlementPostingResult $result, int $businessId, string $settlementNo): void
    {
        $operatorRows = Arr::get($context, 'pump_operator_ledger_rows', []);
        if (empty($operatorRows)) {
            $result->skipped('pump_operator_ledgers', 'No pump operator ledger rows in normalized settlement context.');
            return;
        }

        $result->posted('pump_operator_ledgers', count($operatorRows) . ' pump operator ledger row(s) ready.');
    }

    protected function postStockTransactions(array $context, SettlementPostingResult $result, int $businessId, string $settlementNo): void
    {
        if ($this->guard->stockTransactionExists($businessId, $settlementNo)) {
            $result->skipped('stock_transactions', 'Existing stock transaction found for settlement ' . $settlementNo);
            return;
        }

        $result->posted('stock_transactions', 'Posting seam reached from saved settlement item snapshot.');
    }
}
