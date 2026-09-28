<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business;
use App\BusinessLocation;
use App\Product;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\FormF22Detail;
use Modules\MPCS\Entities\FormF22PumpMeter;

class SeedF22StockTakingData extends Command
{
    /**
     * php artisan mpcs:seed-f22-data
     *   --business_id=3
     *   --month=2026-03        (seeds one stock-taking record per day for the whole month)
     *   --date=2026-03-30      (single day; ignored when --month is set)
     *   --starting_number=1    (form_no for the first day; increments per day)
     */
    protected $signature = 'mpcs:seed-f22-data
                            {--business_id=1 : Business ID to seed data for}
                            {--month= : Seed every day of this month (Y-m format, e.g. 2026-03)}
                            {--date= : Single date (Y-m-d). Ignored when --month is provided. Defaults to today}
                            {--starting_number=1 : Starting form_no for the first day}';

    protected $description = 'Seed F22 Stock Taking data: creates form_f22_headers and form_f22_details records so the F22 Stock Taking page is populated';

    private int   $locationId;
    private array $products = [];   // [['id'=>, 'name'=>, 'sku'=>, 'purchase_price'=>, 'sale_price'=>]]

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

        // ── Resolve products once ─────────────────────────────────────────────
        $this->resolveProducts($businessId);

        if (empty($this->products)) {
            $this->error("No products found for business {$businessId}. Please add products first.");
            return 1;
        }

        $this->info("Products : " . count($this->products) . " product(s) will be used per day.");

        // ── Build list of dates ───────────────────────────────────────────────
        $dates = $this->resolveDates();
        $this->info("Seeding " . count($dates) . " day(s) ...");
        $this->newLine();

        foreach ($dates as $index => $date) {
            $formNo = $startingNumber + $index;
            $this->line("── {$date} (form #{$formNo}) ──────────────────────");
            $this->seedDay($businessId, $date, $formNo);
        }

        $this->newLine();
        $this->info('Done. Open F22 Stock Taking and check the list tab to see the data.');

        return 0;
    }

    // ── Core seeder ───────────────────────────────────────────────────────────

    private function seedDay(int $businessId, string $date, int $formNo): void
    {
        // Skip if a header already exists for this business + date
        $exists = DB::table('form_f22_headers')
            ->where('business_id', $businessId)
            ->whereDate('form_date', $date)
            ->exists();

        if ($exists) {
            $this->warn("  Header already exists for {$date}, skipping.");
            return;
        }

        // ── Create header ─────────────────────────────────────────────────────
        $header = FormF22Header::create([
            'form_no'          => $formNo,
            'business_id'      => $businessId,
            'location_id'      => $this->locationId,
            'manager_name'     => 'Seeded Manager',
            'approved_by'      => null,
            'is_approved'      => 0,
            'form_date'        => $date,
            'purchase_price1'  => 150.00,
            'purchase_price2'  => 0.00,
            'purchase_price3'  => 0.00,
            'sales_price1'     => 180.00,
            'sales_price2'     => 0.00,
            'sales_price3'     => 0.00,
            'status'           => 1,
            'created_by'       => 1,
        ]);

        $this->info("  Header created (ID: {$header->id}, form_no: {$formNo}).");

        // ── Create detail rows (one per product) ──────────────────────────────
        foreach ($this->products as $prod) {
            $currentStock  = rand(100, 500);          // simulated system stock
            $stockCount    = $currentStock + rand(-20, 20); // physical count (slight variance)
            $difference    = $stockCount - $currentStock;
            $purchaseTotal = $stockCount * $prod['purchase_price'];
            $saleTotal     = $stockCount * $prod['sale_price'];

            FormF22Detail::create([
                'header_id'            => $header->id,
                'business_id'          => $businessId,
                'form_no'              => $formNo,
                'location_id'          => $this->locationId,
                'product_code'         => $prod['sku'],
                'product'              => $prod['name'],
                'book_no'              => null,
                'current_stock'        => $currentStock,
                'stock_count'          => $stockCount,
                'unit_purchase_price'  => $prod['purchase_price'],
                'unit_sale_price'      => $prod['sale_price'],
                'purchase_price_total' => $purchaseTotal,
                'sales_price_total'    => $saleTotal,
                'difference_qty'       => $difference,
                'difference_value'     => $difference * $prod['purchase_price'],
                'debit'                => null,
                'status'               => 1,
                'created_by'           => 1,
            ]);

            $this->info("  Detail: {$prod['name']} | current={$currentStock} | count={$stockCount} | diff={$difference}");
        }

        // ── Create pump meter readings (if pumps exist) ───────────────────────
        $pumps = DB::table('pumps')
            ->join('products', 'pumps.product_id', '=', 'products.id')
            ->where('products.business_id', $businessId)
            ->select('pumps.id as pump_id', 'pumps.pump_name', 'products.name as product_name')
            ->get();

        foreach ($pumps as $pump) {
            FormF22PumpMeter::create([
                'header_id'    => $header->id,
                'pump_id'      => $pump->pump_id,
                'pump_name'    => $pump->pump_name,
                'product_name' => $pump->product_name,
                'meter_reading'=> rand(10000, 99999) + (rand(0, 999) / 1000),
            ]);
        }

        if ($pumps->count() > 0) {
            $this->info("  Pump meters: {$pumps->count()} reading(s) created.");
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveProducts(int $businessId): void
    {
        $rows = Product::where('business_id', $businessId)
            ->whereNotNull('sub_category_id')
            ->limit(5)
            ->get(['id', 'name', 'sku']);

        if ($rows->isEmpty()) {
            // Fall back to any product
            $rows = Product::where('business_id', $businessId)
                ->limit(5)
                ->get(['id', 'name', 'sku']);
        }

        foreach ($rows as $p) {
            // Try to get purchase/sale price from variations
            $variation = DB::table('variations')->where('product_id', $p->id)->first();

            $this->products[] = [
                'id'             => $p->id,
                'name'           => $p->name,
                'sku'            => $p->sku ?? ('SKU-' . $p->id),
                'purchase_price' => $variation ? (float)($variation->default_purchase_price ?? 150) : 150.00,
                'sale_price'     => $variation ? (float)($variation->sell_price_inc_tax ?? 180)     : 180.00,
            ];
        }
    }

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
}
