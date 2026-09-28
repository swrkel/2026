<?php

namespace Modules\POS\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bridge from POS to the standalone Customers module.
 *
 * Every schema check and every query uses the same active tenant connection.
 * Optional customer columns are never hard-coded into the SELECT statement.
 */
class POSCustomersModuleBridgeService extends POSBaseService
{
    public function isAvailable(): bool
    {
        return $this->tableExists('contacts');
    }

    public function search(Request $request, int $limit = 30): array
    {
        if (! $this->isAvailable()) {
            return ['data' => [], 'message' => 'Customers table is not available in the active tenant database.'];
        }

        $columns = array_flip($this->columns('contacts'));

        if (! isset($columns['id']) || ! isset($columns['name'])) {
            return ['data' => [], 'message' => 'Customers table is missing the required id or name column.'];
        }

        $select = ['id', 'name'];
        foreach (['contact_id', 'mobile', 'email', 'credit_limit', 'customer_passcode'] as $column) {
            if (isset($columns[$column])) {
                $select[] = $column;
            }
        }

        $query = $this->connection()->table('contacts')->select($select);

        if (isset($columns['business_id']) && $this->businessId()) {
            $query->where('business_id', $this->businessId());
        }

        if (isset($columns['type'])) {
            $query->whereIn('type', ['customer', 'both']);
        }

        if (isset($columns['active'])) {
            $query->where('active', 1);
        } elseif (isset($columns['is_active'])) {
            $query->where('is_active', 1);
        }

        if (isset($columns['deleted_at'])) {
            $query->whereNull('deleted_at');
        }

        $term = trim((string) $request->input('q'));
        if ($term !== '') {
            $searchable = array_values(array_filter(
                ['name', 'mobile', 'contact_id', 'email', 'customer_passcode'],
                static fn (string $column): bool => isset($columns[$column])
            ));

            if ($searchable !== []) {
                $query->where(function ($nested) use ($term, $searchable) {
                    $like = '%' . $term . '%';
                    foreach ($searchable as $index => $column) {
                        $index === 0
                            ? $nested->where($column, 'like', $like)
                            : $nested->orWhere($column, 'like', $like);
                    }
                });
            }
        }

        $rows = $query
            ->orderBy('name')
            ->limit(max(1, min($limit, 500)))
            ->get()
            ->map(function ($row): array {
                return [
                    'id' => (int) $row->id,
                    'contact_id' => $row->contact_id ?? ('CUS-' . $row->id),
                    'name' => $row->name ?? 'Customer',
                    'mobile' => $row->mobile ?? '',
                    'email' => $row->email ?? '',
                    'credit_limit' => (float) ($row->credit_limit ?? 0),
                    'customer_passcode' => $row->customer_passcode ?? null,
                    'balance' => $this->balance((int) $row->id),
                ];
            })
            ->values();

        return ['data' => $rows];
    }

    public function find(int $customerId): ?object
    {
        if (! $this->isAvailable() || $customerId <= 0 || ! $this->hasColumn('contacts', 'id')) {
            return null;
        }

        $query = $this->connection()->table('contacts')->where('id', $customerId);

        if ($this->hasColumn('contacts', 'business_id') && $this->businessId()) {
            $query->where('business_id', $this->businessId());
        }

        if ($this->hasColumn('contacts', 'type')) {
            $query->whereIn('type', ['customer', 'both']);
        }

        if ($this->hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
    }

    public function displayName(?int $customerId, ?string $fallback = null): string
    {
        if (! $customerId) {
            return $fallback ?: 'Walk-in Customer';
        }

        $customer = $this->find($customerId);

        return $customer
            ? (string) (($customer->name ?? null) ?: $fallback ?: 'Customer')
            : ($fallback ?: 'Customer');
    }

    public function balance(int $customerId): float
    {
        $opening = 0.0;

        if ($this->tableExists('contacts') && $this->hasColumn('contacts', 'opening_balance')) {
            $opening = (float) ($this->connection()
                ->table('contacts')
                ->where('id', $customerId)
                ->value('opening_balance') ?? 0);
        }

        return $opening + $this->ledgerBalance($customerId);
    }

    public function postCreditSale(int $customerId, int $saleId, string $referenceNo, float $amount, ?string $note = null): void
    {
        if ($customerId <= 0 || $amount <= 0 || ! $this->tableExists('contact_ledgers')) {
            return;
        }

        $this->insertLedgerRow($customerId, $saleId, $referenceNo, $amount, 'debit', $note ?: 'POS credit sale');
    }

    public function postPaymentReceived(int $customerId, string $referenceNo, float $amount, ?string $note = null): void
    {
        if ($customerId <= 0 || $amount <= 0 || ! $this->tableExists('contact_ledgers')) {
            return;
        }

        $this->insertLedgerRow($customerId, null, $referenceNo, $amount, 'credit', $note ?: 'POS customer payment');
    }

    private function ledgerBalance(int $customerId): float
    {
        if (! $this->tableExists('contact_ledgers') || ! $this->hasColumn('contact_ledgers', 'contact_id')) {
            return 0.0;
        }

        $query = $this->connection()->table('contact_ledgers')->where('contact_id', $customerId);

        if ($this->hasColumn('contact_ledgers', 'business_id') && $this->businessId()) {
            $query->where('business_id', $this->businessId());
        }

        if ($this->hasColumn('contact_ledgers', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $balance = 0.0;
        foreach ($query->get() as $row) {
            $amount = (float) ($row->amount ?? (($row->debit ?? 0) - ($row->credit ?? 0)));
            $type = (string) ($row->type ?? $row->acc_transaction_type ?? $row->entry_type ?? 'debit');
            $balance += $type === 'credit' ? -abs($amount) : abs($amount);
        }

        return $balance;
    }

    private function insertLedgerRow(int $customerId, ?int $saleId, string $referenceNo, float $amount, string $type, string $note): void
    {
        $row = $this->onlyExistingColumns('contact_ledgers', [
            'business_id' => $this->businessId(),
            'contact_id' => $customerId,
            'transaction_date' => now(),
            'date' => now()->toDateString(),
            'type' => $type,
            'acc_transaction_type' => $type,
            'entry_type' => $type,
            'amount' => $amount,
            'debit' => $type === 'debit' ? $amount : 0,
            'credit' => $type === 'credit' ? $amount : 0,
            'balance' => $this->balance($customerId) + ($type === 'credit' ? -$amount : $amount),
            'reference_no' => $referenceNo,
            'ref_no' => $referenceNo,
            'transaction_id' => $saleId,
            'pos_sale_id' => $saleId,
            'note' => $note,
            'description' => $note,
            'created_by' => $this->userId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($row !== []) {
            $this->connection()->table('contact_ledgers')->insert($row);
        }
    }
}
