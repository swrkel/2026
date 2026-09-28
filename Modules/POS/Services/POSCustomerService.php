<?php

namespace Modules\POS\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class POSCustomerService extends POSBaseService
{
    public function __construct(private POSCustomersModuleBridgeService $customersBridge) {}
    public function list(Request $request)
    {
        $result = $this->customersBridge->search($request, 200);
        return collect($result['data'] ?? []);
    }

    public function stats(): array
    {
        $rows = collect($this->customersBridge->search(request(), 1000)['data'] ?? []);
        return [
            'total' => $rows->count(),
            'credit' => $rows->where('credit_limit', '>', 0)->count(),
            'active' => $rows->count(),
            'balance' => (float) $rows->sum('balance'),
        ];
    }

    public function create(array $data): int
    {
        if (!$this->tableExists('pos_customers')) throw new \RuntimeException('Missing pos_customers table. Please run S349 SQL.');
        $id = DB::table('pos_customers')->insertGetId($this->onlyExistingColumns('pos_customers', [
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'customer_code' => $data['customer_code'] ?: $this->nextCode(),
            'name' => $data['name'],
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'nic_no' => $data['nic_no'] ?? null,
            'address' => $data['address'] ?? null,
            'customer_type' => $data['customer_type'] ?? 'walk_in',
            'credit_limit' => (float)($data['credit_limit'] ?? 0),
            'opening_balance' => (float)($data['opening_balance'] ?? 0),
            'balance_amount' => (float)($data['opening_balance'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'note' => $data['note'] ?? null,
            'created_by' => $this->userId(),
            'created_at' => now(), 'updated_at' => now(),
        ]));
        if ((float)($data['opening_balance'] ?? 0) > 0) {
            $this->ledger($id, 'opening_balance', 'debit', (float)$data['opening_balance'], 'Opening Balance', null, 'Opening balance');
        }
        return $id;
    }

    public function update(int $id, array $data): void
    {
        if (!$this->tableExists('pos_customers')) return;
        DB::table('pos_customers')->where('business_id', $this->businessId())->where('id',$id)->update($this->onlyExistingColumns('pos_customers', [
            'customer_code' => $data['customer_code'] ?: $this->nextCode($id),
            'name' => $data['name'],
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'nic_no' => $data['nic_no'] ?? null,
            'address' => $data['address'] ?? null,
            'customer_type' => $data['customer_type'] ?? 'walk_in',
            'credit_limit' => (float)($data['credit_limit'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'note' => $data['note'] ?? null,
            'updated_at' => now(),
        ]));
    }

    public function find(int $id)
    {
        if (!$this->tableExists('pos_customers')) return null;
        return DB::table('pos_customers')->where('business_id', $this->businessId())->where('id',$id)->first();
    }

    public function ledgerRows(int $customerId)
    {
        if (!$this->tableExists('pos_customer_ledgers')) return collect();
        return DB::table('pos_customer_ledgers')->where('business_id', $this->businessId())->where('customer_id',$customerId)->orderBy('transaction_date')->orderBy('id')->paginate(50);
    }

    public function statement(int $customerId): array
    {
        $customer = $this->find($customerId);
        $rows = $this->ledgerRows($customerId);
        return compact('customer','rows');
    }

    public function ledger(int $customerId, string $type, string $entry, float $amount, ?string $ref=null, ?int $saleId=null, ?string $note=null): void
    {
        if (!$this->tableExists('pos_customer_ledgers')) return;
        $customer = $this->find($customerId);
        if (!$customer) return;
        $debit = $entry === 'debit' ? $amount : 0;
        $credit = $entry === 'credit' ? $amount : 0;
        $current = (float)($customer->balance_amount ?? 0);
        $balance = $current + $debit - $credit;
        DB::table('pos_customer_ledgers')->insert($this->onlyExistingColumns('pos_customer_ledgers', [
            'business_id' => $this->businessId(), 'customer_id' => $customerId, 'sale_id' => $saleId,
            'transaction_date' => now(), 'entry_type' => $type, 'debit' => $debit, 'credit' => $credit,
            'balance' => $balance, 'reference_no' => $ref, 'note' => $note, 'created_by' => $this->userId(),
            'created_at' => now(), 'updated_at' => now(),
        ]));
        DB::table('pos_customers')->where('id',$customerId)->update(['balance_amount'=>$balance,'updated_at'=>now()]);
    }

    public function receivePayment(int $customerId, float $amount, ?string $reference, ?string $note): void
    {
        if ($amount <= 0) throw new \InvalidArgumentException('Amount must be greater than zero.');
        $this->ledger($customerId, 'payment_received', 'credit', $amount, $reference, null, $note ?: 'Customer payment received');
    }

    private function nextCode(?int $ignoreId = null): string
    {
        $next = 1;
        if ($this->tableExists('pos_customers')) {
            $next = ((int) DB::table('pos_customers')->where('business_id',$this->businessId())->max('id')) + 1;
        }
        return 'PCUS-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }
}
