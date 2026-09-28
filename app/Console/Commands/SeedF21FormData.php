<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\Transaction;
use Modules\MPCS\Entities\Mpcs21cFormSettings;

class SeedF21FormData extends Command
{
    /**
     * php artisan mpcs:seed-f21-data
     *   --business_id=3
     *   --month=2026-03        (seeds every day of March 2026)
     *   --date=2026-03-30      (single day, ignored when --month is set)
     *   --starting_number=1
     */
    protected $signature = 'mpcs:seed-f21-data
                            {--business_id=1 : Business ID to seed data for}
                            {--month= : Seed every day of this month (Y-m format, e.g. 2026-03)}
                            {--date= : Single date (Y-m-d). Ignored when --month is provided. Defaults to today}
                            {--starting_number=1 : Starting form number for the first day}';

    protected $description = 'Seed F21 Form data: creates mpcs_21c_form_settings records and sample transactions';

    // Resolved once, reused across all days
    private Product $product;
    private Contact $customer;
    private Contact $supplier;
    private int     $locationId;

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

        // ── Resolve product, customer, supplier once ──────────────────────────
        $this->product  = $this->ensureProduct($businessId);
        $this->customer = $this->ensureCustomer($businessId);
        $this->supplier = $this->ensureSupplier($businessId);

        // ── Build list of dates to seed ───────────────────────────────────────
        $dates = $this->resolveDates();

        $this->info("Seeding " . count($dates) . " day(s) ...");
        $this->newLine();

        foreach ($dates as $index => $date) {
            $formNumber = $startingNumber + $index;
            $this->line("── {$date} (form #{$formNumber}) ──────────────────────");
            $this->seedFormSettings($businessId, $date, $formNumber);
            $this->seedPosSales($businessId, $businessId, $date);
            $this->seedPurchase($businessId, $date);
        }

        $this->newLine();
        $this->info('Done. Open the F21 Form and filter by the seeded month/date to see the data.');

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

            $days  = [];
            $end   = $start->copy()->endOfMonth();
            $day   = $start->copy();

            while ($day->lte($end)) {
                $days[] = $day->toDateString();
                $day->addDay();
            }

            return $days;
        }

        // Single date fallback
        $date = $this->option('date') ?: Carbon::today()->toDateString();
        return [$date];
    }

    // ── Form settings ─────────────────────────────────────────────────────────

    private function seedFormSettings(int $businessId, string $date, int $startingNumber): void
    {
        if (Mpcs21cFormSettings::where('business_id', $businessId)->where('date', $date)->exists()) {
            $this->warn("  settings already exist for {$date}, skipping.");
            return;
        }

        $subCategories = DB::table('categories')
            ->where('business_id', $businessId)
            ->where('parent_id', '!=', 0)
            ->limit(3)
            ->pluck('id');

        $categoriesData = [];
        foreach ($subCategories as $catId) {
            $categoriesData[$catId] = [
                'previous_day'  => ['qty' => 100, 'val' => 5000],
                'opening_stock' => ['qty' => 200, 'val' => 10000],
                'total_issues'  => ['qty' => 50,  'val' => 2500],
                'today'         => ['qty' => null, 'val' => null],
            ];
        }

        Mpcs21cFormSettings::create([
            'business_id'                       => $businessId,
            'date'                              => $date,
            'time'                              => '08:00:00',
            'starting_number'                   => $startingNumber,
            'ref_pre_form_number'               => $startingNumber,
            'rec_sec_prev_day_amt'              => 5000,
            'rec_sec_opn_stock_amt'             => 10000,
            'issue_section_previous_day_amount' => 2500,
            'manager_name'                      => 'Seeded Manager',
            'categories'                        => json_encode($categoriesData),
        ]);

        $this->info("  settings created (form #{$startingNumber}).");
    }

    // ── POS sales ─────────────────────────────────────────────────────────────

    private function seedPosSales(int $businessId, int $dayIndex, string $date): void
    {
        $dateSlug = str_replace('-', '', $date); // e.g. 20260301

        $rows = [
            ['suffix' => 'A', 'qty' => 20.00,  'price' => 150.00, 'total' => 3000.00],
            ['suffix' => 'B', 'qty' => 35.50,  'price' => 150.00, 'total' => 5325.00],
        ];

        foreach ($rows as $row) {
            $invoiceNo = "F21-{$businessId}-{$dateSlug}-{$row['suffix']}";

            if (Transaction::where('invoice_no', $invoiceNo)->where('business_id', $businessId)->exists()) {
                $this->warn("  POS {$invoiceNo} already exists, skipping.");
                continue;
            }

            $tx = Transaction::create([
                'business_id'      => $businessId,
                'location_id'      => $this->locationId,
                'type'             => 'sell',
                'status'           => 'final',
                'contact_id'       => $this->customer->id,
                'transaction_date' => $date . ' 09:00:00',
                'invoice_no'       => $invoiceNo,
                'ref_no'           => $invoiceNo,
                'final_total'      => $row['total'],
                'total_before_tax' => $row['total'],
                'payment_status'   => 'paid',
                'created_by'       => 1,
            ]);

            DB::table('transaction_sell_lines')->insert([
                'transaction_id'       => $tx->id,
                'product_id'           => $this->product->id,
                'variation_id'         => DB::table('variations')->where('product_id', $this->product->id)->value('id') ?? 1,
                'quantity'             => $row['qty'],
                'unit_price'           => $row['price'],
                'unit_price_inc_tax'   => $row['price'],
                'item_tax'             => 0,
                'tax_id'               => null,
                'line_discount_type'   => 'fixed',
                'line_discount_amount' => 0,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::table('transaction_payments')->insert([
                'transaction_id' => $tx->id,
                'amount'         => $row['total'],
                'method'         => 'cash',
                'is_return'      => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $this->info("  POS sale {$invoiceNo} | qty={$row['qty']} | total={$row['total']}");
        }
    }

    // ── Purchase ──────────────────────────────────────────────────────────────

    private function seedPurchase(int $businessId, string $date): void
    {
        $dateSlug  = str_replace('-', '', $date);
        $invoiceNo = "F21-PUR-{$businessId}-{$dateSlug}";

        if (Transaction::where('invoice_no', $invoiceNo)->where('business_id', $businessId)->exists()) {
            $this->warn("  Purchase {$invoiceNo} already exists, skipping.");
            return;
        }

        $tx = Transaction::create([
            'business_id'      => $businessId,
            'location_id'      => $this->locationId,
            'type'             => 'purchase',
            'status'           => 'received',
            'contact_id'       => $this->supplier->id,
            'transaction_date' => $date . ' 07:00:00',
            'invoice_no'       => $invoiceNo,
            'ref_no'           => $invoiceNo,
            'final_total'      => 75000.00,
            'total_before_tax' => 75000.00,
            'payment_status'   => 'paid',
            'created_by'       => 1,
        ]);

        DB::table('purchase_lines')->insert([
            'transaction_id'         => $tx->id,
            'product_id'             => $this->product->id,
            'variation_id'           => DB::table('variations')->where('product_id', $this->product->id)->value('id') ?? 1,
            'quantity'               => 500.00,
            'purchase_price'         => 150.00,
            'purchase_price_inc_tax' => 150.00,
            'item_tax'               => 0,
            'tax_id'                 => null,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        DB::table('transaction_payments')->insert([
            'transaction_id' => $tx->id,
            'amount'         => 75000.00,
            'method'         => 'cash',
            'is_return'      => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->info("  Purchase {$invoiceNo} | qty=500 | total=75000");
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

    private function ensureSupplier(int $businessId): Contact
    {
        $s = Contact::where('business_id', $businessId)->whereIn('type', ['supplier', 'both'])->first();

        if ($s) {
            $this->info("Supplier : {$s->name} (ID: {$s->id})");
            return $s;
        }

        $s = Contact::create([
            'business_id'            => $businessId,
            'type'                   => 'supplier',
            'name'                   => 'Fuel Supplier',
            'supplier_business_name' => 'Fuel Supplier Co.',
            'created_by'             => 1,
        ]);

        $this->info("Supplier : created {$s->name} (ID: {$s->id})");
        return $s;
    }
}
