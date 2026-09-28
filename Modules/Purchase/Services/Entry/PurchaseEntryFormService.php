<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseNumberGenerator;

class PurchaseEntryFormService
{
    public function __construct(
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseNumberGenerator $numberGenerator,
        protected PurchaseEntrySupplierLookupService $supplierLookup,
        protected SupplierPaymentReferenceService $paymentReferences
    ) {
    }

    /** @return array<string, mixed> */
    public function formData(): array
    {
        $businessId = $this->numbers->businessId();
        $locations = $this->locations($businessId);
        $defaultLocationId = (int) ($locations->first()->id ?? 0);
        $initialSupplierPage = $this->supplierLookup->search('', 1, PurchaseEntrySupplierLookupService::DEFAULT_PAGE_SIZE);
        $paymentMethodsByLocation = $this->paymentMethodsByLocation($businessId, $locations);
        $defaultPaymentMethods = $paymentMethodsByLocation[(string) $defaultLocationId] ?? [];

        return [
            'purchase_no' => $this->numberGenerator->next('purchase_entry', $businessId),
            'transaction_date' => now()->format('Y-m-d\TH:i'),
            'invoice_date' => now()->toDateString(),
            'locations' => $locations,
            'stores' => $defaultLocationId > 0 ? $this->stores($businessId, $defaultLocationId) : collect(),
            'initial_suppliers' => $initialSupplierPage['results'],
            'initial_suppliers_more' => (bool) ($initialSupplierPage['pagination']['more'] ?? false),
            'supplier_page_size' => PurchaseEntrySupplierLookupService::DEFAULT_PAGE_SIZE,
            'taxes' => $this->taxes($businessId),
            'units' => $this->units($businessId),
            'accounts' => $this->paymentAccounts($businessId),
            'payment_method_accounts' => $this->paymentMethodAccounts($businessId),
            'order_statuses' => [
                'received' => 'Received',
                'pending' => 'Pending',
                'ordered' => 'Ordered',
            ],
            'payment_methods' => $defaultPaymentMethods,
            'payment_methods_by_location' => $paymentMethodsByLocation,
            // S710 v2: visible preview of the configured Purchase Entry payment reference.
            // The permanent reference is still generated server-side at Save.
            'payment_reference_preview' => $this->paymentReferences->preview('APEP', $businessId, now()),
            'currency_precision' => max(0, min(6, (int) (session('business.currency_precision') ?? 2))),
            'quantity_precision' => max(0, min(6, (int) (session('business.quantity_precision') ?? 3))),
            'enable_lot_number' => (bool) session('business.enable_lot_number'),
            'enable_product_expiry' => (bool) session('business.enable_product_expiry'),
            'enable_free_qty' => (bool) session('business.enable_free_qty', true),
            'enable_editing_product_from_purchase' => (bool) session('business.enable_editing_product_from_purchase', true),
            'currency_symbol' => (string) (session('currency.symbol') ?: session('business.currency_symbol') ?: ''),
            'routes' => [
                'supplier_search' => route('purchase.entries.data.suppliers'),
                'supplier_store' => route('purchase.entries.data.suppliers.store'),
                'supplier_terms' => url('/purchase/entries/data/supplier/__ID__'),
                'purchase_orders' => route('purchase.entries.data.purchase-orders'),
                'purchase_order' => url('/purchase/entries/data/purchase-orders/__ID__'),
                'stores' => route('purchase.entries.data.stores'),
                'products' => route('purchase.entries.data.products'),
                'product_store' => route('purchase.entries.data.products.store'),
                'product' => url('/purchase/entries/data/product/__ID__'),
                'unload_tanks' => route('purchase.entries.data.unload-tanks'),
                'reference_check' => route('purchase.entries.data.reference-check'),
                'store' => route('purchase.entries.store'),
                'index' => route('purchase.entries.index'),
                'show' => url('/purchase/entries/__ID__'),
                'create' => route('purchase.entries.create'),
            ],
        ];
    }


    /**
     * Payment methods used by Purchase Entry forms.
     *
     * Keeping this list in one public method allows the Add Payment screen to
     * reuse the exact same method definitions without duplicating them. The
     * Add Payment service removes `credit_purchase` because an existing due is
     * being settled by an actual payment.
     *
     * @return array<string, string>
     */
    public function paymentMethods(): array
    {
        return $this->legacyPaymentMethods();
    }

    /**
     * Legacy-safe method list used only when a database/location has no Payment
     * Options configuration at all. Once default_payment_accounts exists for a
     * location, the explicit Active + Purchases flags are authoritative.
     *
     * @return array<string,string>
     */
    protected function legacyPaymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'card' => 'Card',
            'advance' => 'Supplier Advance',
            'prepayment' => 'Prepayment',
            'credit_purchase' => 'Credit Purchase (Due)',
            'other' => 'Other',
        ];
    }

    /**
     * Payment methods explicitly assigned to Purchase for each location in
     * Super Admin -> Manage New -> Payment Options. Custom/new methods are read
     * directly from the stored JSON, so this module remains standalone and does
     * not depend on Superadmin code.
     *
     * Credit Purchase is retained as the Purchase module's internal due method;
     * it is not a cash-like Payment Option and is required for unpaid purchases.
     *
     * @param Collection<int,object>|null $locations
     * @return array<string,array<string,string>>
     */
    public function paymentMethodsByLocation(int $businessId, ?Collection $locations = null): array
    {
        $locations ??= $this->locations($businessId);
        $result = [];

        // IS2341: Payment Options is the only authority for selectable
        // Purchase payment methods. Never fall back to a hard-coded list: that
        // made disabled methods reappear whenever a location had empty/invalid
        // configuration or the mapping column was unavailable.
        if (! Schema::hasTable('business_locations')
            || ! Schema::hasColumn('business_locations', 'default_payment_accounts')) {
            foreach ($locations as $location) {
                $result[(string) $location->id] = ['credit_purchase' => 'Credit Purchase (Due)'];
            }

            return $result;
        }

        $rows = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->whereIn('id', $locations->pluck('id')->all())
            ->get(['id', 'default_payment_accounts'])
            ->keyBy('id');

        foreach ($locations as $location) {
            $row = $rows->get($location->id);
            $decoded = json_decode((string) ($row?->default_payment_accounts ?? ''), true);

            // IS2341: empty/invalid settings mean no selectable payment method
            // has been assigned for this location. Do NOT expose legacy methods.
            if (! is_array($decoded) || $decoded === []) {
                $result[(string) $location->id] = ['credit_purchase' => 'Credit Purchase (Due)'];
                continue;
            }

            $methods = [];
            foreach ($decoded as $method => $settings) {
                $method = trim((string) $method);
                if (! $this->isPaymentMethodKey($method) || ! is_array($settings)) {
                    continue;
                }
                if (! $this->truthy($settings['is_enabled'] ?? 0)
                    || ! $this->truthy($settings['is_purchase_enabled'] ?? 0)) {
                    continue;
                }

                $methods[$method] = $this->paymentMethodLabel($method);
            }

            // Structural Purchase due option. Keep it even when no cash-like
            // method is assigned so Received purchases can still remain due.
            $methods['credit_purchase'] = 'Credit Purchase (Due)';
            $result[(string) $location->id] = $methods;
        }

        return $result;
    }

    /** @return array<string,string> */
    public function paymentMethodsForLocation(int $businessId, int $locationId): array
    {
        $location = $this->locations($businessId)->firstWhere('id', $locationId);
        if (! $location) {
            return [];
        }

        $map = $this->paymentMethodsByLocation($businessId, collect([$location]));

        return $map[(string) $locationId] ?? [];
    }

    protected function paymentMethodLabel(string $method): string
    {
        $special = [
            'bank_transfer' => 'Bank Transfer',
            'direct_bank_deposit' => 'Direct Bank Deposit',
            'own_cards' => 'Own Cards',
            'credit_sale' => 'Credit Sale',
            'pre_payments' => 'Pre Payments',
            'credit_purchase' => 'Credit Purchase (Due)',
        ];

        return $special[$method] ?? ucwords(str_replace(['_', '-'], ' ', $method));
    }

    protected function isPaymentMethodKey(string $method): bool
    {
        if ($method === '' || strlen($method) > 100 || preg_match('/[\x00-\x1F\x7F]/', $method)) {
            return false;
        }

        $technical = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $method), '_'));

        return ! in_array($technical, ['location_id', 'business_id', 'id', '_token', '_method'], true);
    }

    public function locations(int $businessId): Collection
    {
        if (! Schema::hasTable('business_locations')) {
            return collect();
        }

        $query = DB::table('business_locations')->where('business_id', $businessId);
        if (Schema::hasColumn('business_locations', 'is_active')) {
            $query->where('is_active', 1);
        }
        if (Schema::hasColumn('business_locations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    public function stores(int $businessId, int $locationId): Collection
    {
        if (! Schema::hasTable('stores')) {
            return collect();
        }

        $query = DB::table('stores')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId);

        if (Schema::hasColumn('stores', 'status')) {
            $query->where('status', 1);
        }
        if (Schema::hasColumn('stores', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query
            ->when(Schema::hasColumn('stores', 'is_main'), fn ($q) => $q->orderByDesc('is_main'))
            ->orderBy('name')
            ->get(['id', 'name', 'location_id']);
    }

    public function taxes(int $businessId): Collection
    {
        if (! Schema::hasTable('tax_rates')) {
            return collect();
        }

        $query = DB::table('tax_rates')->where('business_id', $businessId);
        if (Schema::hasColumn('tax_rates', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('name')->get(['id', 'name', 'amount']);
    }

    public function units(int $businessId): Collection
    {
        if (! Schema::hasTable('units')) {
            return collect();
        }

        $query = DB::table('units')->where('business_id', $businessId);
        if (Schema::hasColumn('units', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('actual_name')->get([
            'id', 'actual_name', 'short_name', 'allow_decimal', 'base_unit_id', 'base_unit_multiplier',
        ]);
    }

    public function paymentAccounts(int $businessId): Collection
    {
        if (! Schema::hasTable('accounts')) {
            return collect();
        }

        $query = DB::table('accounts')->where('accounts.business_id', $businessId);
        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where('accounts.is_closed', 0);
        }
        if (Schema::hasColumn('accounts', 'disabled')) {
            $query->where('accounts.disabled', 0);
        }
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('accounts.deleted_at');
        }

        $select = ['accounts.id', 'accounts.name'];
        if (Schema::hasColumn('accounts', 'asset_type')) {
            $select[] = 'accounts.asset_type as group_id';
        } else {
            $select[] = DB::raw('NULL as group_id');
        }
        if (Schema::hasColumn('accounts', 'location_id')) {
            $select[] = 'accounts.location_id';
        } else {
            $select[] = DB::raw("'all' as location_id");
        }

        if (Schema::hasTable('account_groups') && Schema::hasColumn('accounts', 'asset_type')) {
            $query->leftJoin('account_groups', 'account_groups.id', '=', 'accounts.asset_type');
            $select[] = DB::raw("COALESCE(account_groups.name, '') as group_name");
        } else {
            $select[] = DB::raw("'' as group_name");
        }

        return $query->orderBy('accounts.name')->get($select);
    }

    /**
     * Return the account/account-group links configured for each payment method
     * at every business location. The existing system stores the `account` value
     * as either an account id or an account-group id depending on the deployment,
     * so the browser and save service deliberately support both forms.
     *
     * @return array<string, array<string, array<int, int>>>
     */
    public function paymentMethodAccounts(int $businessId): array
    {
        if (! Schema::hasTable('business_locations')) {
            return [];
        }

        /*
         * IS2097: the mapping column is optional. Where it is absent there is
         * simply nothing configured to read, but the Accounts Payable fallback
         * further down must still run - otherwise Credit Purchase has no
         * account to offer on exactly the deployments least likely to have one
         * configured.
         */
        $hasMappingColumn = Schema::hasColumn('business_locations', 'default_payment_accounts');

        $columns = $hasMappingColumn ? ['id', 'default_payment_accounts'] : ['id'];

        $locations = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->get($columns);

        $result = [];
        foreach ($locations as $location) {
            if (! $hasMappingColumn) {
                continue;
            }

            $decoded = json_decode((string) ($location->default_payment_accounts ?? ''), true);
            if (! is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $method => $settings) {
                if (! is_array($settings)) {
                    continue;
                }
                // IS2341: both flags are mandatory. Missing flags are OFF.
                if (! $this->truthy($settings['is_enabled'] ?? 0)
                    || ! $this->truthy($settings['is_purchase_enabled'] ?? 0)) {
                    continue;
                }

                $ids = $this->linkedIds(
                    $settings['account']
                        ?? $settings['accounts']
                        ?? $settings['account_ids']
                        ?? null
                );
                if ($ids === []) {
                    continue;
                }

                $methodKey = $this->normaliseMethodKey((string) $method);
                if (in_array($methodKey, ['credit_purchase_due', 'credit', 'pay_later'], true)) {
                    $methodKey = 'credit_purchase';
                }
                $existing = $result[(string) $location->id][$methodKey] ?? [];
                $result[(string) $location->id][$methodKey] = array_values(array_unique(array_merge($existing, $ids)));
            }
        }

        /*
         |----------------------------------------------------------------------
         | IS2097: Credit Purchase must always offer Accounts Payable.
         |----------------------------------------------------------------------
         | This map is built purely from business_locations.default_payment_accounts,
         | which an administrator fills in per location for CASH-LIKE methods -
         | which bank account a transfer lands in, which cash account a cash
         | payment uses, and so on.
         |
         | Credit Purchase is not one of those. Nothing is paid: the amount
         | becomes a supplier due, and PurchaseEntryAccountingService posts it to
         | Accounts Payable, found BY NAME, with no reference to this map at all.
         |
         | So there was never anything to configure, nobody configured it, and
         | the dropdown came up empty - while the ledger posted the due to
         | Accounts Payable regardless. The screen and the posting disagreed.
         |
         | Accounts Payable is now resolved for every location using the SAME
         | name list PurchaseEntryAccountingService uses, so what the user picks
         | matches where the money actually goes.
         |
         | An explicit mapping still wins. If an administrator has linked an
         | account to credit_purchase for a location, that location is left
         | exactly as configured.
         */
        $payableIds = $this->payableAccountIds($businessId);

        if ($payableIds !== []) {
            foreach ($locations as $location) {
                $locationKey = (string) $location->id;

                if (! empty($result[$locationKey]['credit_purchase'])) {
                    continue;   // administrator has configured this location
                }

                $forLocation = $this->payableIdsForLocation($payableIds, $locationKey);

                if ($forLocation !== []) {
                    $result[$locationKey]['credit_purchase'] = $forLocation;
                }
            }
        }

        return $result;
    }

    /**
     * IS2097: Accounts Payable candidates, as [account_id => location scope].
     *
     * The name list is deliberately identical to the one in
     * PurchaseEntryAccountingService::handle(), so the account offered on screen
     * is the account the due is posted to. If that list is ever changed, change
     * it in both places.
     *
     * @return array<int, string>
     */
    protected function payableAccountIds(int $businessId): array
    {
        if (! Schema::hasTable('accounts')) {
            return [];
        }

        $names = [
            'accounts payable', 'account payable', 'trade creditors',
            'supplier payable', 'supplier payables',
        ];

        $query = DB::table('accounts')->where('business_id', $businessId);

        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where('is_closed', 0);
        }
        if (Schema::hasColumn('accounts', 'disabled')) {
            $query->where('disabled', 0);
        }
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $columns = ['id', 'name'];
        if (Schema::hasColumn('accounts', 'location_id')) {
            $columns[] = 'location_id';
        }

        $found = [];

        foreach ($query->get($columns) as $row) {
            $name = strtolower(preg_replace('/\s+/', ' ', trim((string) $row->name)) ?? '');

            if (! in_array($name, $names, true)) {
                continue;
            }

            $found[(int) $row->id] = (string) ($row->location_id ?? 'all');
        }

        return $found;
    }

    /**
     * IS2097: narrow the payable candidates to one location.
     *
     * An account scoped to this location is preferred. Failing that, an account
     * with no location scope ('all') applies everywhere. Accounts belonging to
     * a different location are never offered.
     *
     * @param  array<int, string>  $payableIds
     * @return array<int, int>
     */
    protected function payableIdsForLocation(array $payableIds, string $locationKey): array
    {
        $exact = [];
        $global = [];

        foreach ($payableIds as $accountId => $scope) {
            if ($scope === $locationKey) {
                $exact[] = (int) $accountId;
                continue;
            }

            if ($scope === '' || strtolower($scope) === 'all') {
                $global[] = (int) $accountId;
            }
        }

        return $exact !== [] ? $exact : $global;
    }

    /** @return array<int, int> */
    protected function linkedIds(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];
        $ids = [];
        array_walk_recursive($values, static function ($item) use (&$ids): void {
            if (is_numeric($item) && (int) $item > 0) {
                $ids[] = (int) $item;
            }
        });

        return array_values(array_unique($ids));
    }

    protected function normaliseMethodKey(string $method): string
    {
        $method = strtolower(trim($method));
        $method = preg_replace('/[^a-z0-9]+/', '_', $method) ?: '';

        return trim($method, '_');
    }

    protected function truthy(mixed $value): bool
    {
        return in_array($value, [1, '1', true, 'true', 'yes', 'on'], true);
    }

}
