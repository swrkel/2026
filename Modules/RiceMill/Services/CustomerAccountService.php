<?php

namespace Modules\RiceMill\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Rice Mill customer-account adapter.
 *
 * Approved Rice Mill Sales Invoices are the module-side source. Customer
 * payments, payment status and the authoritative receivable amount are read
 * from the ERP's existing transactions / transaction_payments tables whenever
 * the Finance adapter has posted the invoice there. No parallel customer
 * accounting balance is stored by Rice Mill.
 */
class CustomerAccountService
{
    private ?bool $coreReady = null;
    private array $columnCache = [];

    public function coreReady(): bool
    {
        if ($this->coreReady !== null) {
            return $this->coreReady;
        }

        return $this->coreReady = Schema::hasTable('transactions')
            && Schema::hasTable('transaction_payments');
    }

    /**
     * Base approved Rice Mill invoice query with the matching ERP sale/payment
     * balance attached when Finance synchronization has completed.
     */
    public function invoiceQuery(int $businessId, array $permittedLocationIds = []): Builder
    {
        $q = DB::table('rcm_dispatches as d')
            ->where('d.business_id', $businessId)
            ->where('d.status', 'approved');

        if (Schema::hasTable('contacts')) {
            $q->leftJoin('contacts as c', function ($join) use ($businessId) {
                $join->on('c.id', '=', 'd.customer_id')
                    ->where('c.business_id', '=', $businessId);
            });
        }

        $this->applyPermittedLocationScope($q, $permittedLocationIds);

        if ($this->coreReady()) {
            $txMap = DB::table('transactions as tx')
                ->selectRaw('MAX(tx.id) as transaction_id, tx.business_id, tx.contact_id, tx.invoice_no')
                ->where('tx.business_id', $businessId)
                ->whereIn('tx.type', ['sell', 'route_operation'])
                ->where('tx.status', 'final');
            if ($this->hasColumn('transactions', 'deleted_at')) {
                $txMap->whereNull('tx.deleted_at');
            }
            $txMap->groupBy('tx.business_id', 'tx.contact_id', 'tx.invoice_no');

            $signedPayment = $this->hasColumn('transaction_payments','is_return')
                ? 'CASE WHEN COALESCE(tp.is_return,0)=1 THEN -tp.amount ELSE tp.amount END'
                : 'tp.amount';
            $pay = DB::table('transaction_payments as tp')
                ->selectRaw('tp.transaction_id, SUM('.$signedPayment.') as paid_amount')
                ->where('tp.business_id', $businessId)
                ->whereNotIn('tp.method',['credit_sale','credit_expense'])
                ->where('tp.amount','>',0);
            if ($this->hasColumn('transaction_payments', 'deleted_at')) {
                $pay->whereNull('tp.deleted_at');
            }
            $pay->groupBy('tp.transaction_id');

            $q->leftJoinSub($txMap, 'tm', function ($join) {
                    $join->on('tm.business_id', '=', 'd.business_id')
                        ->on('tm.contact_id', '=', 'd.customer_id')
                        ->on('tm.invoice_no', '=', 'd.dispatch_no');
                })
                ->leftJoin('transactions as t', 't.id', '=', 'tm.transaction_id')
                ->leftJoinSub($pay, 'pp', 'pp.transaction_id', '=', 't.id');

            $customerName = Schema::hasTable('contacts') ? 'c.name' : "CONCAT('Customer #', d.customer_id)";
            $q->selectRaw(
                'd.id as dispatch_id, d.business_id, d.location_id, d.store_id, d.dispatch_no, d.dispatch_date, d.customer_id, d.net_total as rcm_net_total, d.created_at as dispatch_created_at, '
                . $customerName . ' as customer_name, '
                . 't.id as transaction_id, t.transaction_date as finance_transaction_date, t.final_total as finance_final_total, t.payment_status, '
                . ($this->hasColumn('transactions','pay_term_number') ? 't.pay_term_number' : 'NULL') . ' as pay_term_number, ' . ($this->hasColumn('transactions','pay_term_type') ? 't.pay_term_type' : 'NULL') . ' as pay_term_type, COALESCE(pp.paid_amount,0) as paid_amount, '
                . 'GREATEST(COALESCE(t.final_total,d.net_total)-COALESCE(pp.paid_amount,0),0) as outstanding_amount'
            );
        } else {
            $customerName = Schema::hasTable('contacts') ? 'c.name' : "CONCAT('Customer #', d.customer_id)";
            $q->selectRaw(
                'd.id as dispatch_id, d.business_id, d.location_id, d.store_id, d.dispatch_no, d.dispatch_date, d.customer_id, d.net_total as rcm_net_total, d.created_at as dispatch_created_at, '
                . $customerName . ' as customer_name, '
                . 'NULL as transaction_id, NULL as finance_transaction_date, NULL as finance_final_total, NULL as payment_status, '
                . 'NULL as pay_term_number, NULL as pay_term_type, 0 as paid_amount, d.net_total as outstanding_amount'
            );
        }

        return $q;
    }

    public function onlyOutstanding(Builder $query): void
    {
        if ($this->coreReady()) {
            $query->whereRaw('(t.id IS NULL OR GREATEST(COALESCE(t.final_total,d.net_total)-COALESCE(pp.paid_amount,0),0) > 0.00005)');
        } else {
            $query->where('d.net_total', '>', 0.00005);
        }
    }

    public function applyInvoiceFilters(Builder $query, ?int $customerId, ?int $locationId, ?int $storeId): void
    {
        if ($customerId) {
            $query->where('d.customer_id', $customerId);
        }
        if ($locationId) {
            $query->where('d.location_id', $locationId);
        }
        if ($storeId) {
            $query->where('d.store_id', $storeId);
        }
    }

    public function dueDate(object $row): Carbon
    {
        $base = !empty($row->finance_transaction_date)
            ? Carbon::parse($row->finance_transaction_date)
            : Carbon::parse((string) $row->dispatch_date);

        $number = (int) ($row->pay_term_number ?? 0);
        $type = strtolower((string) ($row->pay_term_type ?? ''));
        if ($number > 0 && $type === 'days') {
            return $base->copy()->addDays($number)->startOfDay();
        }
        if ($number > 0 && $type === 'months') {
            return $base->copy()->addMonthsNoOverflow($number)->startOfDay();
        }

        return $base->copy()->startOfDay();
    }

    public function agingBucket(object $row, Carbon $asOf): string
    {
        $days = $this->daysOutstanding($row, $asOf);
        if ($days <= 0) return 'current';
        if ($days <= 30) return '1_30';
        if ($days <= 60) return '31_60';
        if ($days <= 90) return '61_90';
        if ($days <= 120) return '91_120';
        return 'over_120';
    }

    public function daysOutstanding(object $row, Carbon $asOf): int
    {
        $due = $this->dueDate($row);
        if ($asOf->lt($due)) {
            return -$asOf->diffInDays($due);
        }
        return $due->diffInDays($asOf);
    }

    /**
     * Rice Mill statement/ledger: approved Rice Mill invoice debits plus every
     * ERP payment allocated to those Finance-synchronized invoices.
     */
    public function ledgerData(
        int $businessId,
        int $customerId,
        array $permittedLocationIds,
        ?int $locationId,
        ?int $storeId,
        ?string $from,
        ?string $to
    ): array {
        $invoiceQuery = $this->invoiceQuery($businessId, $permittedLocationIds);
        $this->applyInvoiceFilters($invoiceQuery, $customerId, $locationId, $storeId);
        $invoices = $invoiceQuery->orderBy('d.dispatch_date')->orderBy('d.id')->get();

        $transactionIds = $invoices->pluck('transaction_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $payments = collect();
        if ($this->coreReady() && $transactionIds->isNotEmpty()) {
            $payments = DB::table('transaction_payments as tp')
                ->where('tp.business_id', $businessId)
                ->whereIn('tp.transaction_id', $transactionIds->all())
                ->whereNotIn('tp.method',['credit_sale','credit_expense'])
                ->where('tp.amount','>',0);
            if ($this->hasColumn('transaction_payments', 'deleted_at')) {
                $payments->whereNull('tp.deleted_at');
            }
            $paymentSelect = ['tp.id','tp.transaction_id','tp.amount','tp.method','tp.paid_on','tp.payment_ref_no','tp.note','tp.created_at'];
            if ($this->hasColumn('transaction_payments','is_return')) $paymentSelect[]='tp.is_return';
            $payments = $payments->orderBy('tp.paid_on')->orderBy('tp.id')->get($paymentSelect);
        }

        $invoiceByTransaction = $invoices->filter(fn ($row) => !empty($row->transaction_id))
            ->keyBy(fn ($row) => (int) $row->transaction_id);

        $events = collect();
        foreach ($invoices as $invoice) {
            $date = $this->dispatchDateTime($invoice);
            $amount = (float) ($invoice->finance_final_total ?? $invoice->rcm_net_total ?? 0);
            $events->push((object) [
                'date' => $date,
                'kind' => 'invoice',
                'reference' => (string) $invoice->dispatch_no,
                'invoice_no' => (string) $invoice->dispatch_no,
                'debit' => $amount,
                'credit' => 0.0,
                'method' => null,
                'note' => empty($invoice->transaction_id) ? 'Finance synchronization pending' : null,
                'sync_status' => empty($invoice->transaction_id) ? 'pending' : 'synced',
                'sort_id' => (int) $invoice->dispatch_id,
            ]);
        }

        foreach ($payments as $payment) {
            $invoice = $invoiceByTransaction->get((int) $payment->transaction_id);
            if (!$invoice) continue;
            $date = $payment->paid_on ? Carbon::parse($payment->paid_on) : Carbon::parse($payment->created_at);
            $isReturn = !empty($payment->is_return);
            $events->push((object) [
                'date' => $date,
                'kind' => $isReturn ? 'payment_return' : 'payment',
                'reference' => (string) ($payment->payment_ref_no ?: ('Payment #'.$payment->id)),
                'invoice_no' => (string) $invoice->dispatch_no,
                'debit' => $isReturn ? (float)$payment->amount : 0.0,
                'credit' => $isReturn ? 0.0 : (float) $payment->amount,
                'method' => (string) $payment->method,
                'note' => $payment->note,
                'sync_status' => 'synced',
                'sort_id' => 1000000000 + (int) $payment->id,
            ]);
        }

        $events = $events->sort(function ($a, $b) {
            $cmp = $a->date->getTimestamp() <=> $b->date->getTimestamp();
            if ($cmp !== 0) return $cmp;
            if ($a->kind !== $b->kind) return $a->kind === 'invoice' ? -1 : 1;
            return $a->sort_id <=> $b->sort_id;
        })->values();

        $fromAt = $from ? Carbon::parse($from)->startOfDay() : null;
        $toAt = $to ? Carbon::parse($to)->endOfDay() : null;
        $opening = 0.0;
        $visible = collect();
        foreach ($events as $event) {
            if ($fromAt && $event->date->lt($fromAt)) {
                $opening += (float) $event->debit - (float) $event->credit;
                continue;
            }
            if ($toAt && $event->date->gt($toAt)) {
                continue;
            }
            $visible->push($event);
        }

        $balance = $opening;
        foreach ($visible as $event) {
            $balance += (float) $event->debit - (float) $event->credit;
            $event->balance = $balance;
        }

        return [
            'rows' => $visible,
            'opening_balance' => $opening,
            'period_debits' => (float) $visible->sum('debit'),
            'period_credits' => (float) $visible->sum('credit'),
            'closing_balance' => $balance,
            'invoice_count' => $invoices->count(),
            'sync_pending_count' => $invoices->whereNull('transaction_id')->count(),
        ];
    }

    public function paymentHistoryQuery(int $businessId, array $permittedLocationIds): Builder
    {
        if (!$this->coreReady()) {
            return DB::table('rcm_dispatches as d')
                ->whereRaw('1=0')
                ->selectRaw("d.id as dispatch_id, d.id as payment_id, NULL as paid_on, NULL as payment_ref_no, d.customer_id, d.dispatch_no, NULL as method, 0 as amount, 0 as is_return, NULL as note, NULL as created_by, NULL as username, d.location_id, d.store_id, CONCAT('Customer #',d.customer_id) as customer_name");
        }

        $txMap = DB::table('transactions as tx')
            ->selectRaw('MAX(tx.id) as transaction_id, tx.business_id, tx.contact_id, tx.invoice_no')
            ->where('tx.business_id', $businessId)
            ->whereIn('tx.type', ['sell','route_operation'])
            ->where('tx.status', 'final');
        if ($this->hasColumn('transactions', 'deleted_at')) $txMap->whereNull('tx.deleted_at');
        $txMap->groupBy('tx.business_id','tx.contact_id','tx.invoice_no');

        $q = DB::table('rcm_dispatches as d')
            ->joinSub($txMap, 'tm', function ($join) {
                $join->on('tm.business_id','=','d.business_id')
                    ->on('tm.contact_id','=','d.customer_id')
                    ->on('tm.invoice_no','=','d.dispatch_no');
            })
            ->join('transaction_payments as tp','tp.transaction_id','=','tm.transaction_id')
            ->where('d.business_id',$businessId)
            ->where('d.status','approved')
            ->where('tp.business_id',$businessId)
            ->whereNotIn('tp.method',['credit_sale','credit_expense'])
            ->where('tp.amount','>',0);
        if ($this->hasColumn('transaction_payments', 'deleted_at')) $q->whereNull('tp.deleted_at');
        $this->applyPermittedLocationScope($q, $permittedLocationIds);

        if (Schema::hasTable('contacts')) {
            $q->leftJoin('contacts as c', function ($join) use ($businessId) {
                $join->on('c.id','=','d.customer_id')->where('c.business_id','=',$businessId);
            });
        }
        if (Schema::hasTable('users')) {
            $q->leftJoin('users as u','u.id','=','tp.created_by');
        }

        $customerName = Schema::hasTable('contacts') ? 'c.name' : "CONCAT('Customer #',d.customer_id)";
        $username = Schema::hasTable('users') ? "COALESCE(u.username, CONCAT('User #',tp.created_by))" : "CONCAT('User #',tp.created_by)";
        $isReturnSelect = $this->hasColumn('transaction_payments','is_return') ? 'tp.is_return' : '0 as is_return';
        return $q->selectRaw(
            'd.id as dispatch_id, tp.id as payment_id, tp.paid_on, tp.payment_ref_no, d.customer_id, d.dispatch_no, tp.method, tp.amount, '.$isReturnSelect.', tp.note, tp.created_by, '
            . $username . ' as username, d.location_id, d.store_id, ' . $customerName . ' as customer_name'
        );
    }

    /** @return array<int,string> */
    public function paymentAccounts(int $businessId): array
    {
        if (!Schema::hasTable('accounts')) return [];
        $q = DB::table('accounts')->where('business_id',$businessId);
        if ($this->hasColumn('accounts','is_closed')) $q->where('is_closed',0);
        if ($this->hasColumn('accounts','deleted_at')) $q->whereNull('deleted_at');
        return $q->orderBy('name')->pluck('name','id')->mapWithKeys(fn ($name,$id) => [(int)$id => (string)$name])->all();
    }

    public function paymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'card' => 'Card',
            'cheque' => 'Cheque',
            'bank_transfer' => 'Bank Transfer',
            'direct_bank_deposit' => 'Direct Bank Deposit',
        ];
    }

    /**
     * Post explicitly-entered allocations against the ERP sale transactions.
     * Core TransactionUtil creates the payment/account/contact-ledger entries;
     * Rice Mill stores no duplicate payment balance.
     */
    public function postAllocatedPayment(int $businessId, int $userId, int $customerId, array $input, array $permittedLocationIds): array
    {
        if (!$this->coreReady() || !class_exists(\App\Transaction::class) || !class_exists(\App\TransactionPayment::class) || !class_exists(\App\Utils\TransactionUtil::class)) {
            throw ValidationException::withMessages(['payment' => 'Customer payment integration is not available in this tenant.']);
        }

        $allocations = collect((array)($input['allocations'] ?? []))
            ->map(fn ($value,$key) => ['dispatch_id'=>(int)$key,'amount'=>(float)$value])
            ->filter(fn ($row) => $row['dispatch_id'] > 0 && $row['amount'] > 0.00005)
            ->values();
        if ($allocations->isEmpty()) {
            throw ValidationException::withMessages(['allocations' => 'Select at least one outstanding Sales Invoice and enter an amount to allocate.']);
        }

        $dispatchIds = $allocations->pluck('dispatch_id')->all();
        $invoiceQuery = $this->invoiceQuery($businessId, $permittedLocationIds);
        $this->onlyOutstanding($invoiceQuery);
        $invoiceQuery->where('d.customer_id',$customerId)->whereIn('d.id',$dispatchIds);
        $rows = $invoiceQuery->get()->keyBy(fn ($row) => (int)$row->dispatch_id);
        if ($rows->count() !== count($dispatchIds)) {
            throw ValidationException::withMessages(['allocations' => 'One or more selected Rice Mill invoices are not available to this customer/business/location.']);
        }

        $method = (string)$input['method'];
        $accountId = !empty($input['account_id']) ? (int)$input['account_id'] : null;
        if (in_array($method,['cheque','bank_transfer','direct_bank_deposit'],true) && !$accountId) {
            throw ValidationException::withMessages(['account_id' => 'Please select the payment account for this payment method.']);
        }
        if ($accountId) {
            $accountExists = DB::table('accounts')->where('business_id',$businessId)->where('id',$accountId)->exists();
            if (!$accountExists) throw ValidationException::withMessages(['account_id'=>'The selected payment account does not belong to this business.']);
        }

        $clock = now();
        $paidAt = Carbon::parse((string)$input['paid_on'])->setTime($clock->hour, $clock->minute, $clock->second);
        $groupRef = 'RCM-CP-' . now()->format('YmdHis') . '-' . $userId . '-' . strtoupper(bin2hex(random_bytes(2)));
        $note = trim('Rice Mill Payment '.$groupRef . (!empty($input['note']) ? ' | '.trim((string)$input['note']) : ''));
        $util = app(\App\Utils\TransactionUtil::class);
        $created = [];

        DB::transaction(function () use ($allocations,$rows,$businessId,$userId,$customerId,$input,$method,$accountId,$paidAt,$groupRef,$note,$util,&$created) {
            foreach ($allocations as $allocation) {
                $row = $rows->get($allocation['dispatch_id']);
                if (!$row || empty($row->transaction_id)) {
                    throw ValidationException::withMessages(['allocations' => 'Sales Invoice '.($row->dispatch_no ?? '#'.$allocation['dispatch_id']).' is waiting for Finance synchronization and cannot receive a payment yet.']);
                }

                /** @var \App\Transaction $transaction */
                $transaction = \App\Transaction::where('business_id',$businessId)
                    ->where('contact_id',$customerId)
                    ->lockForUpdate()
                    ->findOrFail((int)$row->transaction_id);
                if ($transaction->status !== 'final') {
                    throw ValidationException::withMessages(['allocations'=>'Sales Invoice '.$row->dispatch_no.' is not a final Finance transaction.']);
                }

                $paidBefore = (float)$util->getTotalPaid($transaction->id);
                $due = max(0, (float)$transaction->final_total - $paidBefore);
                $amount = round((float)$allocation['amount'], 6);
                if ($amount > $due + 0.00005) {
                    throw ValidationException::withMessages(['allocations'=>'Allocation for '.$row->dispatch_no.' exceeds its current outstanding balance.']);
                }

                $maxBefore = (int) \App\TransactionPayment::where('transaction_id',$transaction->id)->max('id');
                $payment = [
                    'amount' => $amount,
                    'method' => $method,
                    'account_id' => $accountId,
                    'note' => $note,
                    'card_transaction_number' => $input['card_transaction_number'] ?? null,
                    'cheque_number' => $input['cheque_number'] ?? null,
                    'cheque_date' => $input['cheque_date'] ?? null,
                    'bank_name' => $input['bank_name'] ?? null,
                    'post_dated_cheque' => 0,
                    'update_post_dated_cheque' => 0,
                ];

                // Do not pass paid_on here: host installations use different
                // display date formats in uf_date(). We set the normalized ISO
                // date immediately after the core utility creates the payment,
                // then synchronize account/contact ledger dates below.
                $util->createOrUpdatePaymentLines($transaction, [$payment], $businessId, $userId, false);

                $tp = \App\TransactionPayment::where('transaction_id',$transaction->id)
                    ->where('id','>',$maxBefore)
                    ->orderByDesc('id')
                    ->first();
                if (!$tp) {
                    throw new \RuntimeException('The ERP payment record could not be created for '.$row->dispatch_no.'.');
                }

                $tp->paid_on = $paidAt;
                $tp->paid_in_type = 'customer_bulk';
                $tp->note = $note;
                $tp->save();
                if (method_exists($util,'syncPaymentAccountAndLedgerEntries')) {
                    $util->syncPaymentAccountAndLedgerEntries($tp);
                }
                $util->updatePaymentStatus($transaction->id, $transaction->final_total);

                $created[] = [
                    'payment_id'=>(int)$tp->id,
                    'payment_ref_no'=>(string)$tp->payment_ref_no,
                    'invoice_no'=>(string)$row->dispatch_no,
                    'amount'=>$amount,
                ];
            }
        });

        return [
            'group_ref'=>$groupRef,
            'total'=>(float)collect($created)->sum('amount'),
            'payments'=>$created,
        ];
    }

    private function applyPermittedLocationScope(Builder $query, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval',$ids), fn ($id) => $id > 0)));
        $query->where(function ($where) use ($ids) {
            $where->whereNull('d.location_id');
            if ($ids) $where->orWhereIn('d.location_id',$ids);
        });
    }

    private function dispatchDateTime(object $invoice): Carbon
    {
        $date = Carbon::parse((string)$invoice->dispatch_date)->format('Y-m-d');
        $time = !empty($invoice->dispatch_created_at) ? Carbon::parse($invoice->dispatch_created_at)->format('H:i:s') : '00:00:00';
        return Carbon::parse($date.' '.$time);
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (!isset($this->columnCache[$table])) {
            $this->columnCache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }
        return in_array($column,$this->columnCache[$table],true);
    }
}
