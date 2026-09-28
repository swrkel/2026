<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business;
use App\BusinessLocation;
use App\Product;
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\MpcsFormSetting;

class SeedF17FormData extends Command
{
    /**
     * php artisan mpcs:seed-f17-data
     *   --business_id=3
     *   --month=2026-03        (seeds every day of March 2026)
     *   --date=2026-03-30      (single day, ignored when --month is set)
     *   --starting_number=1
     */
    protected $signature = 'mpcs:seed-f17-data
                            {--business_id=1 : Business ID to seed data for}
                            {--month= : Seed every day of this month (Y-m format, e.g. 2026-03)}
                            {--date= : Single date (Y-m-d). Ignored when --month is provided. Defaults to today}
                            {--starting_number=1 : Starting form number (used when no MpcsFormSetting exists)}';

    protected $description = 'Seed F17 Form data: creates form_f17_headers and form_f17_details records';

    private Product $product;
    private int     $locationId;

    public function handle(): int
    {
        $businessId     = (int) $this->option('business_id');
        $startingNumber = (int) $this->option('starting_number');

        // ── Validate business & location ──────────────────────────────────────
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

        // ── Resolve product ───────────────────────────────────────────────────
        $this->product = $this->ensureProduct($businessId);

        // ── Ensure MpcsFormSetting has F17_form_sn ────────────────────────────
        $this->ensureFormSetting($businessId, $startingNumber);

        // ── Build list of dates ───────────────────────────────────────────────
        $dates = $this->resolveDates();

        $this->info("Seeding " . count($dates) . " day(s) ...");
        $this->newLine();

        foreach ($dates as $date) {
            $this->line("── {$date} ──────────────────────");
            $this->seedForm($businessId, $date);
        }

        $this->newLine();
        $this->info('Done. Open the F17 Form and filter by the seeded date(s) to see the data.');

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

    // ── Form setting ──────────────────────────────────────────────────────────

    private function ensureFormSetting(int $businessId, int $startingNumber): void
    {
        $setting = MpcsFormSetting::where('business_id', $businessId)->first();

        if ($setting) {
            if (empty($setting->F17_form_sn)) {
                $setting->F17_form_sn = $startingNumber;
                $setting->save();
                $this->info("MpcsFormSetting updated: F17_form_sn = {$startingNumber}.");
            } else {
                $this->info("MpcsFormSetting exists: F17_form_sn = {$setting->F17_form_sn}.");
            }
            return;
        }

        MpcsFormSetting::create([
            'business_id'  => $businessId,
            'F17_form_sn'  => $startingNumber,
            'created_by'   => 1,
        ]);

        $this->info("MpcsFormSetting created: F17_form_sn = {$startingNumber}.");
    }

    // ── Header + details ──────────────────────────────────────────────────────

    private function seedForm(int $businessId, string $date): void
    {
        // Compute form_no exactly as the controller does: F17_form_sn + count
        $settings = MpcsFormSetting::where('business_id', $businessId)->first();
        $count    = FormF17Header::where('business_id', $businessId)->count();
        $formNo   = !empty($settings) ? ($settings->F17_form_sn + $count) : (1 + $count);

        // Skip if a header already exists for this date with this form_no
        if (FormF17Header::where('business_id', $businessId)
            ->where('date', $date)
            ->where('form_no', $formNo)
            ->exists()
        ) {
            $this->warn("  Header form_no={$formNo} on {$date} already exists, skipping.");
            return;
        }

        $header = FormF17Header::create([
            'business_id'            => $businessId,
            'date'                   => $date,
            'form_no'                => $formNo,
            'location_id'            => $this->locationId,
            'store_id'               => null,
            'category_id'            => $this->product->category_id ?? null,
            'sub_category_id'        => $this->product->sub_category_id ?? null,
            'unit_id'                => null,
            'brand_id'               => null,
            'total_price_change_loss'=> 0,
            'total_price_change_gain'=> 0,
            'page_no'                => null,
            'user'                   => 1,
        ]);

        // Two detail rows: one increase, one decrease
        $rows = [
            [
                'select_mode'          => 'increase',
                'unit_price'           => 140.00,
                'new_price'            => 150.00,
                'unit_price_difference'=> 10.00,
                'current_stock'        => 500.000,
                'price_changed_loss'   => 0,
                'price_changed_gain'   => 5000.00,
            ],
            [
                'select_mode'          => 'decrease',
                'unit_price'           => 150.00,
                'new_price'            => 145.00,
                'unit_price_difference'=> 5.00,
                'current_stock'        => 300.000,
                'price_changed_loss'   => 1500.00,
                'price_changed_gain'   => 0,
            ],
        ];

        $totalLoss = 0;
        $totalGain = 0;

        foreach ($rows as $row) {
            FormF17Detail::create([
                'header_id'             => $header->id,
                'product_id'            => $this->product->id,
                'sku'                   => $this->product->sku,
                'product'               => $this->product->name,
                'current_stock'         => $row['current_stock'],
                'unit_price'            => $row['unit_price'],
                'select_mode'           => $row['select_mode'],
                'new_price'             => $row['new_price'],
                'unit_price_difference' => $row['unit_price_difference'],
                'price_changed_loss'    => $row['price_changed_loss'],
                'price_changed_gain'    => $row['price_changed_gain'],
                'page_no'               => null,
            ]);

            $totalLoss += $row['price_changed_loss'];
            $totalGain += $row['price_changed_gain'];
        }

        // Update header totals
        $header->total_price_change_loss = $totalLoss;
        $header->total_price_change_gain = $totalGain;
        $header->save();

        $this->info("  Header form_no={$formNo} created (ID: {$header->id}) | loss={$totalLoss} | gain={$totalGain}");
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
