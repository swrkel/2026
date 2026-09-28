<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Product;
use Modules\MPCS\Entities\Mpcs20FormSettings;

class SeedF20FormData extends Command
{
    /**
     * php artisan mpcs:seed-f20-data
     *   --business_id=3
     *   --month=2026-03        (seeds every day of March 2026)
     *   --date=2026-03-30      (single day, ignored when --month is set)
     *   --starting_number=1
     */
    protected $signature = 'mpcs:seed-f20-data
                            {--business_id=1 : Business ID to seed data for}
                            {--month= : Seed every day of this month (Y-m format, e.g. 2026-03)}
                            {--date= : Single date (Y-m-d). Ignored when --month is provided. Defaults to today}
                            {--starting_number=1 : Starting settlement number for the first day}';

    protected $description = 'Seed F20 Form data: creates mpcs_20_form_settings record, settlements, meter_sales and credit_sale_payments';

    // Resolved once, reused across all days
    private Product $product;
    private Contact $customer;
    private int     $locationId;
    private int     $pumpId;
    private int     $pumpOperatorId;

    public function handle(): int
    {
        $businessId     = (int) $this->option('business_id');
        $startingNumber = (int) $this->option('starting_number');

        // ── Validate business & location ─────────────────────────────────────
        $business = Business::find($businessId);
        if (!$business) {
            $this->error("Business with ID {$businessId} not found.");
            return 1;
        }

        $location = BusinessLocation::where('business_id', $businessId)->first();
        if (!$location) {
            $this->error("No business location found for business ID {$businessId}.");
            return 1;
        }

        $this->locationId = $location->id;
        $this->info("Business : {$business->name}");
        $this->info("Location : {$location->name} (ID: {$location->id})");

        // ── Resolve product, customer, pump, pump operator once ───────────────
        $this->product        = $this->ensureProduct($businessId);
        $this->customer       = $this->ensureCustomer($businessId);
        $this->pumpId         = $this->ensurePump($businessId);
        $this->pumpOperatorId = $this->ensurePumpOperator($businessId);

        // ── Ensure F20 form settings exist ────────────────────────────────────
        $this->ensureFormSettings($businessId);

        // ── Build list of dates to seed ───────────────────────────────────────
        $dates = $this->resolveDates();

        $this->info("Seeding " . count($dates) . " day(s) ...");
        $this->newLine();

        foreach ($dates as $index => $date) {
            $settlementNo = 'F20-' . $businessId . '-' . str_replace('-', '', $date) . '-' . ($startingNumber + $index);
            $this->line("── {$date} (settlement {$settlementNo}) ──────────────────────");
            $settlementId = $this->seedSettlement($businessId, $date, $settlementNo);
            $this->seedMeterSales($businessId, $settlementId, $settlementNo, $date);
            $this->seedCreditSales($businessId, $settlementNo, $date);
        }

        $this->newLine();
        $this->info('Done. Open the F20 Form and filter by the seeded month/date to see the data.');

        return 0;
    }

    // ── Date resolution ───────────────────────────────────────────────────────

    private function resolveDates(): array
    {
        $month = $this->option('month');

        if ($month) {
            try {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            } catch (\Exception $e) {
                $this->error("Invalid --month format. Use Y-m, e.g. 2026-03");
                exit(1);
            }

            $days = [];
            $end  = $start->copy()->endOfMonth();
            $day  = $start->copy();

            while ($day->lte($end)) {
                $days[] = $day->toDateString();
                $day->addDay();
            }

            return $days;
        }

        $date = $this->option('date') ?: Carbon::today()->toDateString();
        return [$date];
    }

    // ── Form settings ─────────────────────────────────────────────────────────

    private function ensureFormSettings(int $businessId): void
    {
        if (Mpcs20FormSettings::where('business_id', $businessId)->exists()) {
            $this->info("F20 settings already exist for business {$businessId}, skipping.");
            return;
        }

        // Grab sub-category IDs to configure
        $categoryIds = DB::table('categories')
            ->where('business_id', $businessId)
            ->where('parent_id', '!=', 0)
            ->limit(5)
            ->pluck('id')
            ->implode(',');

        Mpcs20FormSettings::create([
            'business_id'     => $businessId,
            'opening_date'    => Carbon::today()->toDateString(),
            'starting_number' => 1,
            'total_sale'      => 0,
            'cash_sale'       => 1,
            'credit_sale'     => 100,
            'category'        => $categoryIds ?: '1',
            'created_by'      => 1,
        ]);

        $this->info("  F20 settings created (categories: {$categoryIds}).");
    }

    // ── Settlement ────────────────────────────────────────────────────────────

    private function seedSettlement(int $businessId, string $date, string $settlementNo): int
    {
        $existing = DB::table('settlements')
            ->where('settlement_no', $settlementNo)
            ->where('business_id', $businessId)
            ->value('id');

        if ($existing) {
            $this->warn("  Settlement {$settlementNo} already exists (ID: {$existing}), skipping.");
            return $existing;
        }

        $id = DB::table('settlements')->insertGetId([
            'settlement_no'      => $settlementNo,
            'business_id'        => $businessId,
            'transaction_date'   => $date,
            'finish_date'        => $date,
            'location_id'        => $this->locationId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift'         => 'morning',
            'total_amount'       => '8250.00',
            'status'             => 1,
            'is_edit'            => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->info("  Settlement {$settlementNo} created (ID: {$id}).");
        return $id;
    }

    // ── Meter sales ───────────────────────────────────────────────────────────

    private function seedMeterSales(int $businessId, int $settlementId, string $settlementNo, string $date): void
    {
        $rows = [
            ['starting' => 1000.00, 'closing' => 1055.50, 'price' => 150.00, 'qty' => 55.50],
            ['starting' => 2000.00, 'closing' => 2000.00, 'price' => 150.00, 'qty' => 0.00],
        ];

        foreach ($rows as $i => $row) {
            $exists = DB::table('meter_sales')
                ->where('settlement_no', $settlementId)
                ->where('business_id', $businessId)
                ->where('starting_meter', $row['starting'])
                ->exists();

            if ($exists) {
                $this->warn("  Meter sale row {$i} already exists, skipping.");
                continue;
            }

            DB::table('meter_sales')->insert([
                'settlement_no'   => $settlementId,
                'business_id'     => $businessId,
                'product_id'      => $this->product->id,
                'pump_id'         => $this->pumpId,
                'starting_meter'  => $row['starting'],
                'closing_meter'   => $row['closing'],
                'price'           => $row['price'],
                'qty'             => $row['qty'],
                'discount'        => null,
                'discount_type'   => null,
                'discount_amount' => 0,
                'sub_total'       => $row['qty'] * $row['price'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $this->info("  Meter sale: qty={$row['qty']} | sub_total=" . ($row['qty'] * $row['price']));
        }
    }

    // ── Credit sales ──────────────────────────────────────────────────────────

    private function seedCreditSales(int $businessId, string $settlementNo, string $date): void
    {
        $orderNo = 'F20-CR-' . $businessId . '-' . str_replace('-', '', $date);

        $exists = DB::table('settlement_credit_sale_payments')
            ->where('settlement_no', $settlementNo)
            ->where('business_id', $businessId)
            ->where('order_number', $orderNo)
            ->exists();

        if ($exists) {
            $this->warn("  Credit sale {$orderNo} already exists, skipping.");
            return;
        }

        DB::table('settlement_credit_sale_payments')->insert([
            'settlement_no'    => $settlementNo,
            'business_id'      => $businessId,
            'customer_id'      => $this->customer->id,
            'product_id'       => $this->product->id,
            'order_number'     => $orderNo,
            'order_date'       => $date,
            'customer_reference' => null,
            'price'            => 150.00,
            'discount'         => 0,
            'total_discount'   => 0,
            'sub_total'        => 1500.00,
            'qty'              => 10.00,
            'amount'           => 1500.00,
            'outstanding'      => 0,
            'credit_limit'     => 5000.00,
            'note'             => null,
            'is_from_pumper'   => 0,
            'pump_operator_id' => $this->pumpOperatorId,
            'is_committed'     => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->info("  Credit sale {$orderNo} | qty=10 | amount=1500");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function ensureProduct(int $businessId): Product
    {
        $product = Product::where('business_id', $businessId)->whereNotNull('sub_category_id')->first();

        if ($product) {
            $this->info("Product  : {$product->name} (ID: {$product->id})");
            return $product;
        }

        $product = Product::create([
            'business_id'  => $businessId,
            'name'         => 'Petrol 95',
            'type'         => 'single',
            'sku'          => 'PETROL-95-' . $businessId,
            'unit_id'      => DB::table('units')->where('business_id', $businessId)->value('id') ?? 1,
            'enable_stock' => 1,
            'created_by'   => 1,
        ]);

        $this->info("Product  : created {$product->name} (ID: {$product->id})");
        return $product;
    }

    private function ensureCustomer(int $businessId): Contact
    {
        $c = Contact::where('business_id', $businessId)->where('type', 'customer')->first();

        if ($c) {
            $this->info("Customer : {$c->name} (ID: {$c->id})");
            return $c;
        }

        $c = Contact::create([
            'business_id'            => $businessId,
            'type'                   => 'customer',
            'name'                   => 'Walk-in Customer',
            'supplier_business_name' => 'Walk-in Customer',
            'created_by'             => 1,
        ]);

        $this->info("Customer : created {$c->name} (ID: {$c->id})");
        return $c;
    }

    private function ensurePump(int $businessId): int
    {
        $pumpId = DB::table('pumps')
            ->where('business_id', $businessId)
            ->value('id');

        if ($pumpId) {
            $this->info("Pump     : ID {$pumpId}");
            return $pumpId;
        }

        $productId = $this->product->id;
        $pumpId = DB::table('pumps')->insertGetId([
            'business_id'       => $businessId,
            'product_id'        => $productId,
            'fuel_tank_id'      => DB::table('fuel_tanks')->where('business_id', $businessId)->value('id') ?? 1,
            'location_id'       => $this->locationId,
            'pump_name'         => 'Pump 1',
            'pump_no'           => '1',
            'fuel_type'         => 'petrol',
            'installation_date' => now()->toDateString(),
            'transaction_date'  => now()->toDateString(),
            'qty'               => '0',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->info("Pump     : created ID {$pumpId}");
        return $pumpId;
    }

    private function ensurePumpOperator(int $businessId): int
    {
        $opId = DB::table('pump_operators')
            ->where('business_id', $businessId)
            ->value('id');

        if ($opId) {
            $this->info("Operator : ID {$opId}");
            return $opId;
        }

        $opId = DB::table('pump_operators')->insertGetId([
            'business_id'     => $businessId,
            'location_id'     => $this->locationId,
            'pump_id'         => $this->pumpId,
            'assigned_pump_id'=> $this->pumpId,
            'name'            => 'Seeded Operator',
            'cnic'            => '',
            'address'         => '',
            'dob'             => '1990-01-01',
            'mobile'          => '',
            'commission_type' => 'none',
            'commission_ap'   => 0,
            'active'          => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->info("Operator : created ID {$opId}");
        return $opId;
    }
}
