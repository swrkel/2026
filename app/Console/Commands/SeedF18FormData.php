<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business;
use App\BusinessLocation;
use App\Product;
use Modules\MPCS\Entities\FormF18Header;
use Modules\MPCS\Entities\FormF18Detail;
use Modules\MPCS\Entities\FormF18PrefixNumber;

class SeedF18FormData extends Command
{
    /**
     * php artisan mpcs:seed-f18-data
     *   --business_id=3
     *   --month=2026-03        (seeds every day of March 2026)
     *   --date=2026-03-30      (single day, ignored when --month is set)
     *   --starting_number=1
     */
    protected $signature = 'mpcs:seed-f18-data
                            {--business_id=1 : Business ID to seed data for}
                            {--month= : Seed every day of this month (Y-m format, e.g. 2026-03)}
                            {--date= : Single date (Y-m-d). Ignored when --month is provided. Defaults to today}
                            {--starting_number=1 : Starting form number}';

    protected $description = 'Seed F18 Form data: creates prefix/number settings, headers and detail rows';

    private int $fromLocationId;
    private int $toLocationId;   // synthetic: prefix_id * 1000 + 0
    private int $prefixId;
    private Product $product;

    public function handle(): int
    {
        $businessId     = (int) $this->option('business_id');
        $startingNumber = (int) $this->option('starting_number');

        // ── Validate business ─────────────────────────────────────────────────
        $business = Business::find($businessId);
        if (!$business) {
            $this->error("Business with ID {$businessId} not found.");
            return 1;
        }

        $locations = BusinessLocation::where('business_id', $businessId)->get();
        if ($locations->isEmpty()) {
            $this->error("No business locations found for business ID {$businessId}.");
            return 1;
        }

        $this->fromLocationId = $locations->first()->id;
        // Use a second location if available, otherwise reuse the first
        $this->toLocationId   = $locations->count() > 1 ? $locations->get(1)->id : $locations->first()->id;

        $this->info("Business      : {$business->name}");
        $this->info("From location : {$locations->first()->name} (ID: {$this->fromLocationId})");

        // ── Resolve product ───────────────────────────────────────────────────
        $this->product = $this->ensureProduct($businessId);

        // ── Ensure prefix/number settings ─────────────────────────────────────
        $this->prefixId = $this->ensurePrefixNumbers($businessId, $startingNumber);

        // The synthetic to_location_id used by the controller: prefix_id * 1000 + index
        $syntheticToLocationId = $this->prefixId * 1000 + 0;

        // ── Build list of dates ───────────────────────────────────────────────
        $dates = $this->resolveDates();

        $this->info("Seeding " . count($dates) . " day(s) ...");
        $this->newLine();

        foreach ($dates as $date) {
            $this->line("── {$date} ──────────────────────");
            $this->seedForm($businessId, $date, $syntheticToLocationId);
        }

        $this->newLine();
        $this->info('Done. Open the F18 Form and filter by the seeded date(s) to see the data.');

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

    // ── Prefix & Numbers ──────────────────────────────────────────────────────

    private function ensurePrefixNumbers(int $businessId, int $startingNumber): int
    {
        $existing = FormF18PrefixNumber::where('business_id', $businessId)->first();

        if ($existing) {
            $this->info("Prefix settings already exist (ID: {$existing->id}, prefix: '{$existing->prefix}'), reusing.");
            return $existing->id;
        }

        $record = FormF18PrefixNumber::create([
            'business_id'           => $businessId,
            'opening_date'          => Carbon::today()->toDateString(),
            'prefix'                => 'F18',
            'starting_number'       => $startingNumber,
            'transferred_locations' => ['Seeded Location'],
            'created_by'            => 1,
        ]);

        $this->info("Prefix settings created (ID: {$record->id}, prefix: 'F18', starting: {$startingNumber}).");
        return $record->id;
    }

    // ── Form header + details ─────────────────────────────────────────────────

    private function seedForm(int $businessId, string $date, int $syntheticToLocationId): void
    {
        // Compute the next form number the same way the controller does
        $prefixRecord = FormF18PrefixNumber::find($this->prefixId);
        $openingDate  = $prefixRecord->opening_date->toDateString();

        $existingCount = FormF18Header::where('business_id', $businessId)
            ->whereRaw('(CASE WHEN to_location_id < 1000 THEN to_location_id ELSE FLOOR(to_location_id / 1000) END) = ?', [$this->prefixId])
            ->whereDate('form_date', '>=', $openingDate)
            ->count();

        $nextNumber = $prefixRecord->starting_number + $existingCount;
        $formNo     = $prefixRecord->prefix
            ? ($prefixRecord->prefix . ' ' . $nextNumber)
            : (string) $nextNumber;

        // Skip if a header with this form_no already exists on this date
        if (FormF18Header::where('business_id', $businessId)
            ->where('form_no', $formNo)
            ->whereDate('form_date', $date)
            ->exists()
        ) {
            $this->warn("  Header {$formNo} on {$date} already exists, skipping.");
            return;
        }

        $header = FormF18Header::create([
            'business_id'      => $businessId,
            'form_no'          => $formNo,
            'from_location_id' => $this->fromLocationId,
            'to_location_id'   => $syntheticToLocationId,
            'form_date'        => $date,
            'created_by'       => 1,
        ]);

        // Seed two product rows per header
        $rows = [
            ['qty' => 55.50,  'purchase' => 120.00, 'sale' => 150.00],
            ['qty' => 100.00, 'purchase' => 120.00, 'sale' => 150.00],
        ];

        foreach ($rows as $row) {
            FormF18Detail::create([
                'header_id'                    => $header->id,
                'business_id'                  => $businessId,
                'product_id'                   => $this->product->id,
                'from_location_id'             => $this->fromLocationId,
                'to_location_id'               => $syntheticToLocationId,
                'qty'                          => $row['qty'],
                'issued_purchase_unit_price'   => $row['purchase'],
                'issued_purchase_total'        => $row['purchase'] * $row['qty'],
                'issued_sale_unit_price'       => $row['sale'],
                'issued_sale_total'            => $row['sale'] * $row['qty'],
                'received_purchase_unit_price' => $row['purchase'],
                'received_purchase_total'      => $row['purchase'] * $row['qty'],
                'received_sale_unit_price'     => $row['sale'],
                'received_sale_total'          => $row['sale'] * $row['qty'],
            ]);
        }

        $this->info("  Header {$formNo} created (ID: {$header->id}) with " . count($rows) . " detail rows.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function ensureProduct(int $businessId): Product
    {
        $product = Product::where('business_id', $businessId)->first();

        if ($product) {
            $this->info("Product : {$product->name} (ID: {$product->id})");
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

        $this->info("Product : created {$product->name} (ID: {$product->id})");
        return $product;
    }
}
