<?php

namespace Tests\Feature\Petro;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Shared base for Petro day-1 characterization tests.
 *
 * Resolves real reference IDs from the dev DB at runtime (business, pump_operator,
 * contact, product, user). Avoids hard-coded IDs that drift across environments
 * and avoids FK violations from inventing IDs.
 *
 * Every test must use the DatabaseTransactions trait so the seed rows are rolled
 * back after each test. Never use RefreshDatabase against vimi12_mathe — it would
 * wipe the dev data.
 */
abstract class PetroTestCase extends TestCase
{
    protected array $connectionsToTransact = [null, 'system'];

    protected int $businessId;
    protected int $pumpOperatorId;
    protected int $contactId;
    protected int $productId;
    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureMinimalPetroFixture();

        // Pick a business that has at least one pump operator (business_id=2 in dev DB).
        $operator = DB::table('pump_operators')
            ->whereNotNull('business_id')
            ->orderBy('id')
            ->first();
        if (! $operator) {
            $this->markTestSkipped('No pump_operators in DB — cannot run Petro tests.');
        }
        $this->businessId     = (int) $operator->business_id;
        $this->pumpOperatorId = (int) $operator->id;

        $contact = DB::table('contacts')->where('business_id', $this->businessId)->orderBy('id')->first();
        if (! $contact) {
            $this->markTestSkipped("No contacts for business {$this->businessId}.");
        }
        $this->contactId = (int) $contact->id;

        $product = DB::table('products')->where('business_id', $this->businessId)->orderBy('id')->first();
        if (! $product) {
            $this->markTestSkipped("No products for business {$this->businessId}.");
        }
        $this->productId = (int) $product->id;

        $user = DB::table('users')->orderBy('id')->first();
        if (! $user) {
            $this->markTestSkipped('No users in DB.');
        }
        $this->userId = (int) $user->id;

        $this->withSession([
            'business.id' => $this->businessId,
            'user.business_id' => $this->businessId,
            'user.id' => $this->userId,
            'currency' => $this->currencySessionData(),
        ]);

        $this->allowPetroPdModuleForTests();
    }

    protected function ensureMinimalPetroFixture(): void
    {
        foreach (['users', 'business', 'business_locations', 'contacts', 'products', 'pumps', 'pump_operators'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        if (DB::table('pump_operators')->whereNotNull('business_id')->exists()) {
            return;
        }

        $suffix = uniqid('petro-test-');
        $userId = DB::table('users')->insertGetId([
            'first_name' => 'Petro',
            'last_name' => 'Tester',
            'username' => $suffix,
            'email' => $suffix . '@example.test',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currencyId = $this->ensureTestCurrency();

        $businessId = DB::table('business')->insertGetId([
            'name' => 'Petro Test Business',
            'currency_id' => $currencyId,
            'owner_id' => $userId,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $userId)->update(['business_id' => $businessId]);

        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $businessId,
            'name' => 'Petro Test Location',
            'country' => 'Test',
            'state' => 'Test',
            'city' => 'Test',
            'zip_code' => '00000',
            'invoice_scheme_id' => 1,
            'invoice_layout_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contactId = DB::table('contacts')->insertGetId([
            'business_id' => $businessId,
            'type' => 'customer',
            'name' => 'Petro Test Customer',
            'created_by' => $userId,
            'notification_contacts' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Petro Test Fuel',
            'business_id' => $businessId,
            'type' => 'single',
            'unit_id' => 1,
            'sku' => $suffix . '-fuel',
            'tax_type' => 'inclusive',
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pumpId = DB::table('pumps')->insertGetId([
            'business_id' => $businessId,
            'pump_name' => 'Petro Test Pump',
            'location_id' => $locationId,
            'fuel_type' => 'Fuel',
            'installation_date' => now()->toDateString(),
            'pump_no' => 'PT-1',
            'image_link' => '',
            'product_id' => $productId,
            'fuel_tank_id' => 1,
            'qty' => '0',
            'transaction_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pump_operators')->insert([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'pump_id' => $pumpId,
            'name' => 'Petro Test Operator',
            'cnic' => '',
            'address' => '',
            'dob' => '2000-01-01',
            'mobile' => '',
            'assigned_pump_id' => $pumpId,
            'status' => 1,
            'commission_type' => 'none',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        unset($contactId);
    }

    protected function ensureTestCurrency(): int
    {
        if (! Schema::hasTable('currencies')) {
            return 1;
        }

        $currencyId = DB::table('currencies')->orderBy('id')->value('id');
        if ($currencyId) {
            return (int) $currencyId;
        }

        return DB::table('currencies')->insertGetId([
            'country' => 'Test',
            'currency' => 'Test Rupee',
            'code' => 'TST',
            'symbol' => 'Rs',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function currencySessionData(): array
    {
        if (! Schema::hasTable('currencies')) {
            return [
                'id' => 1,
                'code' => 'TST',
                'symbol' => 'Rs',
                'thousand_separator' => ',',
                'decimal_separator' => '.',
            ];
        }

        $businessCurrencyId = DB::table('business')
            ->where('id', $this->businessId)
            ->value('currency_id');

        $currency = DB::table('currencies')
            ->where('id', $businessCurrencyId)
            ->first() ?: DB::table('currencies')->orderBy('id')->first();

        return [
            'id' => (int) ($currency->id ?? 1),
            'code' => $currency->code ?? 'TST',
            'symbol' => $currency->symbol ?? 'Rs',
            'thousand_separator' => $currency->thousand_separator ?? ',',
            'decimal_separator' => $currency->decimal_separator ?? '.',
        ];
    }

    protected function allowPetroPdModuleForTests(): void
    {
        Gate::define('petro_pd.access', fn () => true);

        if (! Schema::connection('system')->hasTable('subscriptions')) {
            return;
        }

        DB::connection('system')->table('subscriptions')->insert([
            'business_id' => $this->businessId,
            'package_id' => 1,
            'start_date' => now()->subDay()->toDateString(),
            'trial_end_date' => null,
            'end_date' => now()->addYear()->toDateString(),
            'package_price' => 0,
            'package_details' => json_encode([
                'petro_pd_module' => 1,
            ]),
            'created_id' => $this->userId,
            'paid_via' => null,
            'payment_transaction_id' => null,
            'status' => 'approved',
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Insert a pump_operator_payments row with sensible defaults; return its id.
     * Overrides via $attrs for the specific scenario.
     */
    protected function seedPumpOperatorPayment(array $attrs = []): int
    {
        return DB::table('pump_operator_payments')->insertGetId(array_merge([
            'business_id'      => $this->businessId,
            'date_and_time'    => now(),
            'pump_operator_id' => $this->pumpOperatorId,
            'payment_type'     => 'credit',
            'payment_amount'   => '12345.00',
            'created_by'       => $this->userId,
            'settlement_no'    => 'TST-' . uniqid(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ], $attrs));
    }

    /**
     * Build a settlement_credit_sale_payments insert array with sensible defaults.
     */
    protected function buildCreditSalePaymentData(array $overrides = []): array
    {
        return array_merge([
            'business_id'        => $this->businessId,
            'customer_id'        => $this->contactId,
            'product_id'         => $this->productId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'order_number'       => 'TST-' . uniqid(),
            'order_date'         => now()->toDateString(),
            'price'              => 100,
            'discount'           => 0,
            'qty'                => 1,
            'amount'             => 100,
            'sub_total'          => 100,
            'total_discount'     => 0,
            'is_from_pumper'     => 1,
            'collection_form_no' => '999',
            'bill_number'        => 'BILL-' . uniqid(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ], $overrides);
    }
}
