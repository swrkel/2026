<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CityLedgerService
{
    public function dashboard(array $filters = []): array
    {
        $accounts = $this->tableRows('hm_city_ledger_accounts');
        $invoices = $this->tableRows('hm_city_ledger_invoices');
        $receipts = $this->tableRows('hm_city_ledger_receipts');
        $adjustments = $this->tableRows('hm_city_ledger_adjustments');

        $totalCreditLimit = array_sum(array_map(fn($r) => (float)($r->credit_limit ?? 0), $accounts));
        $totalBalance = array_sum(array_map(fn($r) => (float)($r->current_balance ?? 0), $accounts));
        $openInvoices = array_filter($invoices, fn($r) => !in_array(($r->status ?? ''), ['paid','cancelled'], true));
        $overdueInvoices = array_filter($openInvoices, fn($r) => !empty($r->due_date) && $r->due_date < date('Y-m-d'));

        return [
            'accounts' => $accounts,
            'invoices' => $invoices,
            'receipts' => $receipts,
            'adjustments' => $adjustments,
            'total_credit_limit' => $totalCreditLimit,
            'total_balance' => $totalBalance,
            'available_credit' => max(0, $totalCreditLimit - $totalBalance),
            'open_invoice_count' => count($openInvoices),
            'overdue_invoice_count' => count($overdueInvoices),
            'notes' => [
                'City Ledger supports company, travel agent and house account billing without duplicating the Customers module.',
                'All postings are scoped by tenant database, business_id and business_location_id where available.',
                'Receipts and adjustments update the account balance immediately for testing visibility.',
            ],
        ];
    }

    public function saveAccount(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_city_ledger_accounts')) return;
        DB::table('hm_city_ledger_accounts')->updateOrInsert([
            'business_id' => $this->businessId(),
            'account_code' => strtoupper($data['account_code']),
        ], [
            'business_location_id' => $this->locationId(),
            'account_name' => $data['account_name'],
            'account_type' => $data['account_type'] ?? 'corporate',
            'contact_person' => $data['contact_person'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'credit_limit' => (float)($data['credit_limit'] ?? 0),
            'current_balance' => (float)($data['current_balance'] ?? 0),
            'credit_days' => (int)($data['credit_days'] ?? 30),
            'status' => $data['status'] ?? 'active',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createInvoice(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_city_ledger_invoices') || !Schema::hasTable('hm_city_ledger_accounts')) return;
        $account = DB::table('hm_city_ledger_accounts')->where('id', $data['ledger_account_id'])->where('business_id', $this->businessId())->first();
        if (!$account) return;
        $amount = (float)($data['invoice_amount'] ?? 0);
        DB::transaction(function () use ($data, $userId, $account, $amount) {
            DB::table('hm_city_ledger_invoices')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'ledger_account_id' => $account->id,
                'invoice_no' => $this->nextNumber('hm_city_ledger_invoices', 'invoice_no', 'CLINV'),
                'folio_no' => $data['folio_no'] ?? null,
                'guest_name' => $data['guest_name'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? date('Y-m-d'),
                'due_date' => $data['due_date'] ?? date('Y-m-d', strtotime('+'.(int)($account->credit_days ?? 30).' days')),
                'invoice_amount' => $amount,
                'paid_amount' => 0,
                'balance_amount' => $amount,
                'status' => 'open',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('hm_city_ledger_accounts')->where('id', $account->id)->update([
                'current_balance' => (float)($account->current_balance ?? 0) + $amount,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    public function recordReceipt(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_city_ledger_receipts') || !Schema::hasTable('hm_city_ledger_invoices')) return;
        $invoice = DB::table('hm_city_ledger_invoices')->where('id', $data['invoice_id'])->where('business_id', $this->businessId())->first();
        if (!$invoice) return;
        $amount = min((float)$data['receipt_amount'], (float)$invoice->balance_amount);
        DB::transaction(function () use ($data, $userId, $invoice, $amount) {
            DB::table('hm_city_ledger_receipts')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'ledger_account_id' => $invoice->ledger_account_id,
                'invoice_id' => $invoice->id,
                'receipt_no' => $this->nextNumber('hm_city_ledger_receipts', 'receipt_no', 'CLREC'),
                'receipt_date' => $data['receipt_date'] ?? date('Y-m-d'),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_no' => $data['reference_no'] ?? null,
                'receipt_amount' => $amount,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paid = (float)$invoice->paid_amount + $amount;
            $balance = max(0, (float)$invoice->balance_amount - $amount);
            DB::table('hm_city_ledger_invoices')->where('id', $invoice->id)->update([
                'paid_amount' => $paid,
                'balance_amount' => $balance,
                'status' => $balance <= 0 ? 'paid' : 'partial',
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            DB::table('hm_city_ledger_accounts')->where('id', $invoice->ledger_account_id)->decrement('current_balance', $amount, ['updated_by' => $userId, 'updated_at' => now()]);
        });
    }

    public function adjustment(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_city_ledger_adjustments') || !Schema::hasTable('hm_city_ledger_accounts')) return;
        $account = DB::table('hm_city_ledger_accounts')->where('id', $data['ledger_account_id'])->where('business_id', $this->businessId())->first();
        if (!$account) return;
        $amount = (float)$data['adjustment_amount'];
        $type = $data['adjustment_type'] ?? 'debit';
        DB::transaction(function () use ($data, $userId, $account, $amount, $type) {
            DB::table('hm_city_ledger_adjustments')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'ledger_account_id' => $account->id,
                'adjustment_no' => $this->nextNumber('hm_city_ledger_adjustments', 'adjustment_no', 'CLADJ'),
                'adjustment_date' => $data['adjustment_date'] ?? date('Y-m-d'),
                'adjustment_type' => $type,
                'adjustment_amount' => $amount,
                'reason' => $data['reason'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $newBalance = (float)$account->current_balance + ($type === 'debit' ? $amount : -$amount);
            DB::table('hm_city_ledger_accounts')->where('id', $account->id)->update([
                'current_balance' => max(0, $newBalance),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    protected function tableRows(string $table): array
    {
        if (!Schema::hasTable($table)) return [];
        try {
            return DB::table($table)->where('business_id', $this->businessId())->orderByDesc('id')->limit(200)->get()->toArray();
        } catch (Throwable $e) { return []; }
    }

    protected function nextNumber(string $table, string $column, string $prefix): string
    {
        $prefix = $prefix . '-' . date('ymd') . '-';
        $last = Schema::hasTable($table) ? DB::table($table)->where('business_id', $this->businessId())->where($column, 'like', $prefix.'%')->orderByDesc('id')->value($column) : null;
        $next = $last ? ((int)substr($last, -4)) + 1 : 1;
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? session('business_id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
