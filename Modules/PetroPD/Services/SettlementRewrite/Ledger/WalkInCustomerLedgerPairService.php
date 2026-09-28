<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Ledger;

use App\Contact;
use App\ContactLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalkInCustomerLedgerPairService
{
    /**
     * Current/future settlement posting path.
     * Kept intentionally compatible with the previous working fix: for Walk-In
     * cash/card payments, create the Debit row first and the Credit row second.
     */
    public function postForPaymentRow(object $payment, array $context = []): bool
    {
        $businessId = (int) ($context['business_id'] ?? $payment->business_id ?? 0);
        $contactId = (int) ($payment->customer_id ?? $payment->contact_id ?? $payment->payment_for ?? 0);
        $amount = (float) ($payment->payment_amount ?? $payment->amount ?? $payment->paid_amount ?? 0);

        if ($businessId <= 0 || $contactId <= 0 || $amount <= 0) {
            return false;
        }

        if (! $this->isWalkInCustomer($businessId, $contactId)) {
            return false;
        }

        $settlementNo = (string) ($context['settlement_no'] ?? $payment->settlement_no ?? $payment->settlement_reference ?? '');
        $operationDate = $context['operation_date']
            ?? $context['transaction_date']
            ?? $payment->operation_date
            ?? $payment->date_and_time
            ?? $payment->paid_on
            ?? $payment->created_at
            ?? now()->toDateTimeString();
        $createdBy = (int) ($context['created_by'] ?? $payment->created_by ?? auth()->id() ?? 0);
        $paymentId = (int) ($payment->id ?? 0);
        $method = $this->normalisePaymentMethod($payment, $context);
        $ref = $settlementNo !== '' ? $settlementNo : ('SHIFT-' . ($payment->shift_number ?? $payment->shift_id ?? ''));
        $note = $this->walkInLedgerDescription($ref);
        $subType = $method === 'card' ? 'settlement_card_payment' : 'settlement_cash_payment';

        DB::transaction(function () use ($businessId, $contactId, $amount, $operationDate, $createdBy, $paymentId, $note, $subType) {
            $this->ensureLedgerSide($businessId, $contactId, $amount, 'debit', $subType, $operationDate, $createdBy, $paymentId, $note);
            $this->ensureLedgerSide($businessId, $contactId, $amount, 'credit', $subType, $operationDate, $createdBy, $paymentId, $note);
        });

        return true;
    }

    /**
     * V2 historical repair.
     *
     * The previous command processed 0 rows because old tenant data may be in
     * settlement_cash_payments / settlement_card_payments or in existing ledger rows,
     * and the artisan command was often run without tenant initialization.
     * This method is broad enough for old records, but still safe because it only
     * touches Walk-In Customer rows and only creates missing opposite rows.
     */
    public function backfill(?int $businessId = null, ?string $settlementNo = null, bool $dryRun = false): array
    {
        $result = [
            'connection' => DB::connection()->getName(),
            'database' => DB::connection()->getDatabaseName(),
            'source_rows_processed' => 0,
            'source_rows_posted_or_verified' => 0,
            'historical_ledger_rows_scanned' => 0,
            'historical_opposite_rows_created' => 0,
            'walkin_contacts_found' => 0,
            'dry_run' => $dryRun,
        ];

        $walkInContactIds = $this->walkInContactIds($businessId);
        $result['walkin_contacts_found'] = count($walkInContactIds);

        if (empty($walkInContactIds)) {
            return $result;
        }

        // 1) Repair existing old ledger entries first. This preserves current correct
        // records and only adds the missing opposite row for old one-sided entries.
        $repair = $this->repairExistingWalkInLedgerRows($walkInContactIds, $businessId, $dryRun);
        $result['historical_ledger_rows_scanned'] += $repair['scanned'];
        $result['historical_opposite_rows_created'] += $repair['created'];

        // 2) Backfill from historical payment source tables that existed before the
        // current Debit/Credit pair fix. This is idempotent.
        foreach ($this->sourcePaymentRows($walkInContactIds, $businessId, $settlementNo) as $row) {
            $result['source_rows_processed']++;
            if (! $dryRun && $this->postForPaymentRow($row, [
                'business_id' => (int) ($row->business_id ?? $businessId ?? 0),
                'settlement_no' => (string) ($row->settlement_no ?? ''),
                'operation_date' => $row->operation_date ?? $row->transaction_date ?? $row->date_and_time ?? $row->created_at ?? null,
                'created_by' => (int) ($row->created_by ?? 0),
                'payment_method' => $row->payment_type ?? $row->method ?? null,
            ])) {
                $result['source_rows_posted_or_verified']++;
            } elseif ($dryRun) {
                $result['source_rows_posted_or_verified']++;
            }
        }

        return $result;
    }

    private function sourcePaymentRows(array $walkInContactIds, ?int $businessId = null, ?string $settlementNo = null): \Generator
    {
        // A. Current/latest source table.
        if (Schema::hasTable('pump_operator_payments')) {
            $query = DB::table('pump_operator_payments')
                ->whereIn('customer_id', $walkInContactIds)
                ->where(function ($q) {
                    $q->whereIn(DB::raw('LOWER(REPLACE(REPLACE(IFNULL(payment_type, ""), "-", "_"), " ", "_"))'), [
                        'cash', 'card', 'cards'
                    ])->orWhereNull('payment_type');
                });

            if (Schema::hasColumn('pump_operator_payments', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            if ($businessId && Schema::hasColumn('pump_operator_payments', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($settlementNo && Schema::hasColumn('pump_operator_payments', 'settlement_no')) {
                $query->where('settlement_no', $settlementNo);
            }

            foreach ($query->orderBy('id')->cursor() as $row) {
                if ((float) ($row->payment_amount ?? $row->amount ?? 0) <= 0) {
                    continue;
                }
                yield (object) array_merge((array) $row, [
                    'contact_id' => $row->customer_id ?? null,
                    'amount' => $row->payment_amount ?? $row->amount ?? 0,
                    'payment_type' => $row->payment_type ?? 'cash',
                ]);
            }
        }

        // B. Saved settlement cash rows from older finalization paths.
        if (Schema::hasTable('settlement_cash_payments')) {
            $query = DB::table('settlement_cash_payments')
                ->whereIn('customer_id', $walkInContactIds);

            if (Schema::hasColumn('settlement_cash_payments', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            if ($businessId && Schema::hasColumn('settlement_cash_payments', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($settlementNo && Schema::hasColumn('settlement_cash_payments', 'settlement_no')) {
                $query->where('settlement_no', $settlementNo);
            }

            foreach ($query->orderBy('id')->cursor() as $row) {
                if ((float) ($row->amount ?? 0) <= 0) {
                    continue;
                }
                yield (object) array_merge((array) $row, [
                    'id' => 0, // settlement table id is not a transaction payment id
                    'contact_id' => $row->customer_id ?? null,
                    'payment_amount' => $row->amount ?? 0,
                    'payment_type' => 'cash',
                    'date_and_time' => $row->operation_date ?? $row->transaction_date ?? $row->created_at ?? null,
                ]);
            }
        }

        // C. Saved settlement card rows from older finalization paths.
        if (Schema::hasTable('settlement_card_payments')) {
            $query = DB::table('settlement_card_payments')
                ->whereIn('customer_id', $walkInContactIds);

            if (Schema::hasColumn('settlement_card_payments', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            if ($businessId && Schema::hasColumn('settlement_card_payments', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($settlementNo && Schema::hasColumn('settlement_card_payments', 'settlement_no')) {
                $query->where('settlement_no', $settlementNo);
            }

            foreach ($query->orderBy('id')->cursor() as $row) {
                if ((float) ($row->amount ?? 0) <= 0) {
                    continue;
                }
                yield (object) array_merge((array) $row, [
                    'id' => 0,
                    'contact_id' => $row->customer_id ?? null,
                    'payment_amount' => $row->amount ?? 0,
                    'payment_type' => 'card',
                    'date_and_time' => $row->operation_date ?? $row->transaction_date ?? $row->created_at ?? null,
                ]);
            }
        }
    }

    private function repairExistingWalkInLedgerRows(array $walkInContactIds, ?int $businessId = null, bool $dryRun = false): array
    {
        $query = ContactLedger::query()
            ->whereIn('contact_id', $walkInContactIds)
            ->whereIn('type', ['debit', 'credit'])
            ->where('amount', '>', 0)
            ->where(function ($q) {
                $q->whereIn('sub_type', $this->petroPdWalkInLedgerSubTypes())
                    ->orWhere('note', 'like', '%Petro PD%')
                    ->orWhere('note', 'like', '%PD Settlement%')
                    ->orWhere('note', 'like', '%PDST%')
                    ->orWhere('note', 'like', '%Walk-In%')
                    ->orWhere('note', 'like', '%Walk In%');
            });

        if (Schema::hasColumn((new ContactLedger)->getTable(), 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $scanned = 0;
        $created = 0;

        $query->orderBy('id')->chunk(200, function ($rows) use (&$scanned, &$created, $dryRun) {
            foreach ($rows as $row) {
                $scanned++;
                $businessId = (int) ($row->business_id ?? 0);
                $contactId = (int) ($row->contact_id ?? 0);
                $amount = (float) ($row->amount ?? 0);

                if ($businessId <= 0 || $contactId <= 0 || $amount <= 0) {
                    continue;
                }

                $oppositeType = $row->type === 'debit' ? 'credit' : 'debit';

                $displayDescription = $this->walkInLedgerDescription($this->extractReferenceFromLedgerRow($row));

                if (! $dryRun && trim((string) ($row->note ?? '')) !== $displayDescription) {
                    $row->note = $displayDescription;
                    $row->save();
                }

                if ($this->hasOppositeLedgerRow($row, $oppositeType)) {
                    continue;
                }

                if (! $dryRun) {
                    ContactLedger::createContactLedger([
                        'business_id' => $businessId,
                        'contact_id' => $contactId,
                        'amount' => $amount,
                        'type' => $oppositeType,
                        'sub_type' => $row->sub_type ?: ($oppositeType === 'credit' ? 'pd_walkin_payment' : 'pd_walkin_sale'),
                        'operation_date' => $row->operation_date ?? $row->created_at ?? now(),
                        'transaction_date' => $row->transaction_date ?? $row->operation_date ?? $row->created_at ?? now(),
                        'created_by' => (int) ($row->created_by ?? auth()->id() ?? 1),
                        'transaction_id' => $row->transaction_id ?? null,
                        'transaction_payment_id' => $row->transaction_payment_id ?? null,
                        'account_id' => $row->account_id ?? null,
                        'note' => $this->walkInLedgerDescription($this->extractReferenceFromLedgerRow($row)),
                        'slip_no' => $row->slip_no ?? null,
                    ], 'Petro PD Walk-In Customer Historical Ledger Auto Balance');
                }

                $created++;
            }
        });

        return ['scanned' => $scanned, 'created' => $created];
    }

    private function hasOppositeLedgerRow(ContactLedger $row, string $oppositeType): bool
    {
        $query = ContactLedger::where('business_id', $row->business_id)
            ->where('contact_id', $row->contact_id)
            ->where('type', $oppositeType)
            ->where('amount', $row->amount)
            ->where(function ($q) use ($row) {
                if (!empty($row->transaction_id)) {
                    $q->where('transaction_id', $row->transaction_id);
                } else {
                    $q->whereNull('transaction_id');
                }
            })
            ->where(function ($q) use ($row) {
                if (!empty($row->transaction_payment_id)) {
                    $q->where('transaction_payment_id', $row->transaction_payment_id);
                } else {
                    $q->whereNull('transaction_payment_id');
                }
            });

        if (Schema::hasColumn((new ContactLedger)->getTable(), 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    private function walkInContactIds(?int $businessId = null): array
    {
        $query = Contact::query()
            ->where(function ($q) {
                $q->where('is_default', 1)
                    ->orWhere('name', 'like', '%Walk%')
                    ->orWhere('contact_id', 'like', '%walk%')
                    ->orWhere('contact_id', 'like', '%CO-0001%')
                    ->orWhere('contact_id', 'like', '%co-0001%');
            });

        if ($businessId && Schema::hasColumn((new Contact)->getTable(), 'business_id')) {
            $query->where('business_id', $businessId);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    private function petroPdWalkInLedgerSubTypes(): array
    {
        return [
            'sell',
            'payment',
            'cash_payment',
            'card_payment',
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

    private function ensureLedgerSide(int $businessId, int $contactId, float $amount, string $type, string $subType, $operationDate, int $createdBy, int $paymentId, string $note): void
    {
        $exists = ContactLedger::where('business_id', $businessId)
            ->where('contact_id', $contactId)
            ->where('amount', $amount)
            ->where('type', $type)
            ->where('sub_type', $subType)
            ->where(function ($q) use ($paymentId) {
                if ($paymentId > 0) {
                    $q->where('transaction_payment_id', $paymentId);
                } else {
                    $q->whereNull('transaction_payment_id');
                }
            })
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
            'transaction_date' => $operationDate,
            'created_by' => $createdBy ?: 1,
            'transaction_id' => null,
            'transaction_payment_id' => $paymentId ?: null,
            'note' => $note,
        ], 'Petro PD Walk-In Customer Ledger');
    }


    private function walkInLedgerDescription(?string $settlementNo = null, ?string $invoiceNo = null, ?string $billNo = null): string
    {
        $ref = trim((string) ($settlementNo ?: $invoiceNo ?: $billNo ?: ''));

        if ($ref === '') {
            $ref = 'N/A';
        }

        return 'Invoice No / Bill no / settlement No: ' . $ref;
    }

    private function extractReferenceFromLedgerRow(ContactLedger $row): string
    {
        $candidates = [
            $row->settlement_no ?? null,
            $row->invoice_no ?? null,
            $row->bill_no ?? null,
            $row->ref_no ?? null,
            $row->note ?? null,
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            if (preg_match('/(PDST[0-9A-Za-z\-\/]+)/i', $candidate, $m)) {
                return strtoupper($m[1]);
            }
            if (preg_match('/(SETTLEMENT[\s\-\#:]*[0-9A-Za-z\-\/]+)/i', $candidate, $m)) {
                return trim($m[1]);
            }
            if (preg_match('/(INV[0-9A-Za-z\-\/]+)/i', $candidate, $m)) {
                return strtoupper($m[1]);
            }
        }

        return '';
    }

    private function normalisePaymentMethod(object $payment, array $context = []): string
    {
        $raw = strtolower(str_replace(['-', ' '], '_', (string) (
            $context['payment_method']
            ?? $context['method']
            ?? $payment->payment_type
            ?? $payment->method
            ?? $payment->payment_method
            ?? ''
        )));

        if (str_contains($raw, 'card')) {
            return 'card';
        }

        return 'cash';
    }

    private function isWalkInCustomer(int $businessId, int $contactId): bool
    {
        $contact = Contact::where('business_id', $businessId)->where('id', $contactId)->first();

        if (! $contact) {
            $contact = Contact::where('id', $contactId)->first();
        }

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
}
