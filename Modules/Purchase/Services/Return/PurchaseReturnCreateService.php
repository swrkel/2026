<?php

namespace Modules\Purchase\Services\Return;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\StoreStockIntegrityService;
use Modules\Purchase\Services\Entry\PurchaseEntryFormService;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseReturnNumberGenerator;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseReturnCreateService
{
    public function __construct(
        protected PurchaseEntryFormService $form,
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseReturnNumberGenerator $numberGenerator,
        protected PurchaseSchemaUtil $schema,
        protected StoreStockIntegrityService $storeStock
    ) {
    }

    /** @return array<string, mixed> */
    public function formData(): array
    {
        $businessId = $this->numbers->businessId();
        $locations = $this->form->locations($businessId);
        $defaultLocationId = (int) ($locations->first()->id ?? 0);

        return [
            'transaction_date' => now()->format('Y-m-d\TH:i'),
            'return_no' => $this->numberGenerator->next($businessId),
            'locations' => $locations,
            'stores' => $defaultLocationId > 0 ? $this->form->stores($businessId, $defaultLocationId) : collect(),
            'suppliers' => $this->suppliers($businessId),
            'accounts' => $this->form->paymentAccounts($businessId),
            'currency_precision' => max(0, min(6, (int) (session('business.currency_precision') ?? 2))),
            'quantity_precision' => max(0, min(6, (int) (session('business.quantity_precision') ?? 3))),
            'currency_symbol' => (string) (session('currency.symbol') ?: session('business.currency_symbol') ?: ''),
            'routes' => [
                'purchase_search' => route('purchase.returns.data.purchases'),
                'purchase' => url('/purchase/returns/data/purchase/__ID__'),
                'stores' => route('purchase.entries.data.stores'),
                'store' => route('purchase.returns.store'),
                'index' => route('purchase.returns.index'),
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function purchases(string $term = '', ?int $supplierId = null, ?int $locationId = null): array
    {
        if (! Schema::hasTable('transactions')) {
            return [];
        }

        $query = DB::table('transactions as t')
            ->where('t.business_id', $this->numbers->businessId())
            ->where('t.type', 'purchase')
            ->whereIn('t.status', ['received', 'final']);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }
        if ($supplierId) {
            $query->where('t.contact_id', $supplierId);
        }
        if ($locationId) {
            $query->where('t.location_id', $locationId);
        }
        if (Schema::hasTable('contacts')) {
            $query->leftJoin('contacts as c', 'c.id', '=', 't.contact_id');
        }
        if (Schema::hasTable('business_locations')) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id');
        }

        $term = trim($term);
        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('t.invoice_no', 'like', $like)
                    ->orWhere('t.ref_no', 'like', $like);
                if (Schema::hasColumn('transactions', 'purchase_entry_no')) {
                    $inner->orWhere('t.purchase_entry_no', 'like', $like);
                }
                if (Schema::hasTable('contacts')) {
                    $inner->orWhere('c.name', 'like', $like)
                        ->orWhere('c.supplier_business_name', 'like', $like)
                        ->orWhere('c.contact_id', 'like', $like);
                }
            });
        }

        $select = [
            't.id', 't.invoice_no', 't.ref_no', 't.transaction_date', 't.final_total',
            't.contact_id', 't.location_id',
        ];
        if (Schema::hasColumn('transactions', 'store_id')) {
            $select[] = 't.store_id';
        }
        $select[] = Schema::hasTable('contacts')
            ? DB::raw("COALESCE(NULLIF(c.supplier_business_name, ''), c.name, '') as supplier_name")
            : DB::raw("'' as supplier_name");
        $select[] = Schema::hasTable('business_locations') ? 'bl.name as location_name' : DB::raw("'' as location_name");

        return $query
            ->orderByDesc('t.transaction_date')
            ->orderByDesc('t.id')
            ->limit(50)
            ->get($select)
            ->map(function ($row): array {
                $number = trim((string) ($row->invoice_no ?: $row->ref_no ?: ('PUR-' . $row->id)));
                $date = $row->transaction_date ? \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y') : '';

                return [
                    'id' => (int) $row->id,
                    'text' => $number . ' — ' . ($row->supplier_name ?: 'Supplier') . ($date ? ' — ' . $date : ''),
                    'number' => $number,
                    'supplier_name' => (string) ($row->supplier_name ?? ''),
                    'location_name' => (string) ($row->location_name ?? ''),
                    'final_total' => (float) ($row->final_total ?? 0),
                    'contact_id' => (int) $row->contact_id,
                    'location_id' => (int) $row->location_id,
                    'store_id' => (int) ($row->store_id ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function purchaseDetails(int $purchaseId): ?array
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasTable('purchase_lines')) {
            return null;
        }

        $transaction = DB::table('transactions as t')
            ->where('t.business_id', $this->numbers->businessId())
            ->where('t.type', 'purchase')
            ->whereIn('t.status', ['received', 'final'])
            ->where('t.id', $purchaseId)
            ->first();
        if (! $transaction) {
            return null;
        }

        $lineQuery = DB::table('purchase_lines as pl')
            ->leftJoin('products as p', 'p.id', '=', 'pl.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id')
            ->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
            ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
            ->where('pl.transaction_id', $purchaseId);

        $returnedExpr = Schema::hasColumn('purchase_lines', 'quantity_returned')
            ? 'COALESCE(pl.quantity_returned, 0)'
            : '0';
        $bonusExpr = Schema::hasColumn('purchase_lines', 'bonus_qty') ? 'COALESCE(pl.bonus_qty, 0)' : '0';

        $lines = $lineQuery
            ->orderBy('pl.id')
            ->get([
                'pl.id as purchase_line_id', 'pl.product_id', 'pl.variation_id', 'pl.quantity',
                'pl.purchase_price', 'pl.purchase_price_inc_tax', 'pl.pp_without_discount',
                'pl.item_tax', 'pl.tax_id', 'pl.sub_unit_id', 'pl.lot_number', 'pl.exp_date',
                DB::raw($returnedExpr . ' as quantity_returned'),
                DB::raw($bonusExpr . ' as bonus_qty'),
                'p.name as product_name', 'p.enable_stock', 'p.unit_id',
                'v.sub_sku', 'v.product_variation_id', 'v.name as variation_name',
                'pv.name as variation_group', 'u.short_name as unit_name', 'u.allow_decimal',
            ])
            ->map(function ($line): array {
                $purchased = (float) ($line->quantity ?? 0) + (float) ($line->bonus_qty ?? 0);
                $returned = (float) ($line->quantity_returned ?? 0);
                $available = max(0, $purchased - $returned);
                $variation = trim(implode(' — ', array_filter([
                    (string) ($line->variation_group ?? ''),
                    (string) ($line->variation_name ?? ''),
                ])));

                return [
                    'purchase_line_id' => (int) $line->purchase_line_id,
                    'product_id' => (int) $line->product_id,
                    'variation_id' => (int) $line->variation_id,
                    'product_variation_id' => (int) ($line->product_variation_id ?? 0),
                    'product_name' => (string) ($line->product_name ?? 'Product'),
                    'variation_name' => $variation,
                    'sku' => (string) ($line->sub_sku ?? ''),
                    'unit_name' => (string) ($line->unit_name ?? ''),
                    'allow_decimal' => (bool) ($line->allow_decimal ?? true),
                    'enable_stock' => (bool) ($line->enable_stock ?? true),
                    'purchased_quantity' => $purchased,
                    'already_returned' => $returned,
                    'available_quantity' => $available,
                    'purchase_price' => (float) ($line->purchase_price ?? 0),
                    'purchase_price_inc_tax' => (float) ($line->purchase_price_inc_tax ?? 0),
                    'pp_without_discount' => (float) ($line->pp_without_discount ?? 0),
                    'item_tax' => (float) ($line->item_tax ?? 0),
                    'tax_id' => $line->tax_id ? (int) $line->tax_id : null,
                    'sub_unit_id' => $line->sub_unit_id ? (int) $line->sub_unit_id : null,
                    'lot_number' => (string) ($line->lot_number ?? ''),
                    'exp_date' => $line->exp_date,
                ];
            })
            ->filter(fn (array $line): bool => $line['available_quantity'] > 0.000001)
            ->values()
            ->all();

        $supplier = $this->supplier((int) $transaction->contact_id);
        $locationName = Schema::hasTable('business_locations')
            ? (string) DB::table('business_locations')->where('id', $transaction->location_id)->value('name')
            : '';
        $storeName = '';
        if (Schema::hasTable('stores') && ! empty($transaction->store_id)) {
            $storeName = (string) DB::table('stores')->where('id', $transaction->store_id)->value('name');
        }

        return [
            'id' => (int) $transaction->id,
            'number' => (string) ($transaction->invoice_no ?: $transaction->ref_no ?: ('PUR-' . $transaction->id)),
            'supplier_id' => (int) $transaction->contact_id,
            'supplier_name' => (string) ($supplier['name'] ?? ''),
            'location_id' => (int) $transaction->location_id,
            'location_name' => $locationName,
            'store_id' => (int) ($transaction->store_id ?? 0),
            'store_name' => $storeName,
            'transaction_date' => $transaction->transaction_date,
            'final_total' => (float) ($transaction->final_total ?? 0),
            'lines' => $lines,
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function store(array $data): array
    {
        $businessId = $this->numbers->businessId();
        $userId = $this->numbers->userId();
        if ($businessId <= 0 || $userId <= 0) {
            throw new \RuntimeException('The business session is not available. Please sign in again.');
        }

        return DB::transaction(function () use ($data, $businessId, $userId): array {
            $purchase = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('type', 'purchase')
                ->where('id', (int) $data['purchase_id'])
                ->lockForUpdate()
                ->first();
            if (! $purchase) {
                throw new \InvalidArgumentException('The selected purchase was not found for this business.');
            }
            if ((int) $purchase->contact_id !== (int) $data['contact_id'] || (int) $purchase->location_id !== (int) $data['location_id']) {
                throw new \InvalidArgumentException('The supplier or location does not match the selected purchase.');
            }

            $submitted = collect((array) $data['lines'])
                ->map(fn ($line): array => [
                    'purchase_line_id' => (int) ($line['purchase_line_id'] ?? 0),
                    'quantity' => max(0, $this->numbers->number($line['quantity'] ?? 0)),
                ])
                ->filter(fn (array $line): bool => $line['purchase_line_id'] > 0 && $line['quantity'] > 0.000001)
                ->values();
            if ($submitted->isEmpty()) {
                throw new \InvalidArgumentException('Enter a return quantity for at least one product.');
            }

            $lineIds = $submitted->pluck('purchase_line_id')->all();
            $originalLines = DB::table('purchase_lines')
                ->where('transaction_id', $purchase->id)
                ->whereIn('id', $lineIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($originalLines->count() !== count($lineIds)) {
                throw new \InvalidArgumentException('One or more selected return lines do not belong to the selected purchase.');
            }

            $prepared = [];
            $subtotal = 0.0;
            foreach ($submitted as $submittedLine) {
                $line = $originalLines->get($submittedLine['purchase_line_id']);
                $bonus = Schema::hasColumn('purchase_lines', 'bonus_qty') ? (float) ($line->bonus_qty ?? 0) : 0.0;
                $alreadyReturned = Schema::hasColumn('purchase_lines', 'quantity_returned') ? (float) ($line->quantity_returned ?? 0) : 0.0;
                $available = max(0, (float) $line->quantity + $bonus - $alreadyReturned);
                $quantity = $submittedLine['quantity'];
                if ($quantity > $available + 0.000001) {
                    throw new \InvalidArgumentException('Return quantity exceeds the available quantity for purchase line #' . $line->id . '.');
                }

                $unitPrice = (float) ($line->purchase_price_inc_tax ?? $line->purchase_price ?? 0);
                $lineTotal = round($quantity * $unitPrice, 6);
                $prepared[] = [
                    'original' => $line,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
                $subtotal += $lineTotal;
            }

            $taxAmount = max(0, $this->numbers->number($data['tax_amount'] ?? 0));
            $finalTotal = round($subtotal + $taxAmount, 6);
            if ($finalTotal <= 0) {
                throw new \InvalidArgumentException('The purchase return total must be greater than zero.');
            }

            $refNo = trim((string) ($data['ref_no'] ?? ''));
            if ($refNo === '') {
                $refNo = $this->numberGenerator->next($businessId);
            }
            $transactionDate = $this->numbers->dateTime($data['transaction_date'])->format('Y-m-d H:i:s');
            $storeId = (int) ($purchase->store_id ?? 0);

            $transactionPayload = $this->schema->filter('transactions', [
                'business_id' => $businessId,
                'location_id' => (int) $purchase->location_id,
                'store_id' => $storeId ?: null,
                'type' => 'purchase_return',
                'status' => 'final',
                'payment_status' => 'paid',
                'contact_id' => (int) $purchase->contact_id,
                'ref_no' => $refNo,
                'invoice_no' => $refNo,
                'transaction_date' => $transactionDate,
                'total_before_tax' => round($subtotal, 6),
                'tax_amount' => $taxAmount,
                'final_total' => $finalTotal,
                'return_parent_id' => (int) $purchase->id,
                'additional_notes' => trim((string) ($data['additional_notes'] ?? '')) ?: null,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $returnId = (int) DB::table('transactions')->insertGetId($transactionPayload);

            foreach ($prepared as $preparedLine) {
                $line = $preparedLine['original'];
                $qty = (float) $preparedLine['quantity'];
                DB::table('purchase_lines')->insert($this->schema->filter('purchase_lines', [
                    'transaction_id' => $returnId,
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'quantity' => Schema::hasColumn('purchase_lines', 'quantity_returned') ? 0 : $qty,
                    'quantity_returned' => $qty,
                    'purchase_price' => $line->purchase_price,
                    'pp_without_discount' => $line->pp_without_discount,
                    'purchase_price_inc_tax' => $line->purchase_price_inc_tax,
                    'item_tax' => $line->item_tax,
                    'tax_id' => $line->tax_id,
                    'sub_unit_id' => $line->sub_unit_id,
                    'lot_number' => $line->lot_number,
                    'exp_date' => $line->exp_date,
                    'parent_purchase_line_id' => $line->id,
                    'purchase_line_id' => $line->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));

                if (Schema::hasColumn('purchase_lines', 'quantity_returned')) {
                    DB::table('purchase_lines')->where('id', $line->id)->update([
                        'quantity_returned' => (float) ($line->quantity_returned ?? 0) + $qty,
                        'updated_at' => now(),
                    ]);
                }

                $this->decreaseStock(
                    (int) $line->product_id,
                    (int) $line->variation_id,
                    (int) $purchase->location_id,
                    $storeId,
                    $qty
                );
            }

            $this->postAccounting(
                $returnId,
                $businessId,
                (int) $purchase->location_id,
                $userId,
                $transactionDate,
                $refNo,
                $finalTotal,
                ! empty($data['return_account_id']) ? (int) $data['return_account_id'] : null
            );

            Log::info('Standalone Purchase return saved', [
                'transaction_id' => $returnId,
                'purchase_id' => $purchase->id,
                'business_id' => $businessId,
                'ref_no' => $refNo,
                'final_total' => $finalTotal,
            ]);

            return [
                'transaction_id' => $returnId,
                'ref_no' => $refNo,
                'final_total' => $finalTotal,
            ];
        }, 3);
    }

    /** @return array<string, mixed>|null */
    protected function supplier(int $id): ?array
    {
        if (! Schema::hasTable('contacts')) {
            return null;
        }
        $row = DB::table('contacts')->where('business_id', $this->numbers->businessId())->where('id', $id)->first();
        if (! $row) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'name' => trim((string) ($row->supplier_business_name ?: $row->name)),
        ];
    }

    protected function suppliers(int $businessId)
    {
        if (! Schema::hasTable('contacts')) {
            return collect();
        }
        $query = DB::table('contacts')->where('business_id', $businessId)->whereIn('type', ['supplier', 'both']);
        if (Schema::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderByRaw("COALESCE(NULLIF(supplier_business_name, ''), name)")
            ->get(['id', 'name', 'supplier_business_name', 'contact_id']);
    }

    protected function decreaseStock(int $productId, int $variationId, int $locationId, int $storeId, float $quantity): void
    {
        $enableStock = Schema::hasTable('products')
            ? (bool) DB::table('products')->where('id', $productId)->value('enable_stock')
            : true;
        if (! $enableStock || $quantity <= 0) {
            return;
        }

        if (Schema::hasTable('variation_location_details')) {
            $locationRows = DB::table('variation_location_details')
                ->where('variation_id', $variationId)
                ->where('location_id', $locationId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $locationAvailable = (float) $locationRows->sum(fn ($row): float => (float) ($row->qty_available ?? 0));
            if ($locationRows->isEmpty() || $locationAvailable + 0.000001 < $quantity) {
                throw new \InvalidArgumentException('Insufficient stock is available at the selected location for one of the returned products.');
            }
            DB::table('variation_location_details')->where('id', $locationRows->first()->id)->update($this->schema->filter('variation_location_details', [
                'qty_available' => $locationAvailable - $quantity,
                'updated_at' => now(),
            ]));
            if ($locationRows->count() > 1) {
                DB::table('variation_location_details')->whereIn('id', $locationRows->slice(1)->pluck('id')->all())->update(
                    $this->schema->filter('variation_location_details', ['qty_available' => 0, 'updated_at' => now()])
                );
            }
        }

        if ($storeId > 0 && Schema::hasTable('variation_store_details')) {
            $this->storeStock->adjustStoreStock(
                $locationId,
                $productId,
                $variationId,
                -$quantity,
                $storeId,
                null,
                false,
                $this->numbers->businessId()
            );
        }
    }

    protected function postAccounting(
        int $returnId,
        int $businessId,
        int $locationId,
        int $userId,
        string $date,
        string $reference,
        float $amount,
        ?int $returnAccountId
    ): void {
        if (! Schema::hasTable('account_transactions') || $amount <= 0) {
            return;
        }

        $debitAccount = $returnAccountId ?: $this->findAccount($businessId, $locationId, [
            'accounts payable', 'account payable', 'trade creditors', 'supplier payable', 'purchase return',
        ]);
        $stockAccount = $this->findAccount($businessId, $locationId, [
            'finished goods account', 'stock account', 'inventory account', 'raw material account',
        ]);

        if ($debitAccount) {
            $this->accountEntry($debitAccount, $businessId, 'debit', $amount, $reference, $date, $userId, $returnId, 'Purchase return / supplier credit');
        }
        if ($stockAccount) {
            $this->accountEntry($stockAccount, $businessId, 'credit', $amount, $reference, $date, $userId, $returnId, 'Stock returned to supplier');
        }
    }

    /** @param array<int, string> $names */
    protected function findAccount(int $businessId, int $locationId, array $names): ?int
    {
        if (! Schema::hasTable('accounts')) {
            return null;
        }
        $query = DB::table('accounts')->where('business_id', $businessId);
        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where('is_closed', 0);
        }
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        $rows = $query->get(array_filter(['id', 'name', Schema::hasColumn('accounts', 'location_id') ? 'location_id' : null]));
        $needles = array_map(fn (string $name): string => strtolower($name), $names);
        foreach ($rows as $row) {
            $scope = (string) ($row->location_id ?? 'all');
            if ($scope !== '' && strtolower($scope) !== 'all' && $scope !== (string) $locationId) {
                continue;
            }
            $name = strtolower(trim((string) $row->name));
            foreach ($needles as $needle) {
                if ($name === $needle || str_contains($name, $needle)) {
                    return (int) $row->id;
                }
            }
        }

        return null;
    }

    protected function accountEntry(
        int $accountId,
        int $businessId,
        string $type,
        float $amount,
        string $reference,
        string $date,
        int $userId,
        int $returnId,
        string $note
    ): void {
        DB::table('account_transactions')->insert($this->schema->filter('account_transactions', [
            'account_id' => $accountId,
            'business_id' => $businessId,
            'type' => $type,
            'txnType' => 'purchase_return',
            'sub_type' => 'ledger_show',
            'amount' => $amount,
            'reff_no' => $reference,
            'operation_date' => $date,
            'created_by' => $userId,
            'transaction_id' => $returnId,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}
