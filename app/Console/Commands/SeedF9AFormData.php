<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\Product;
use App\Transaction;
use Modules\MPCS\Entities\Mpcs9aFormSettings;

class SeedF9AFormData extends Command
{
    /**
     * php artisan mpcs:seed-f9a-data
     *   --business_id=3
     *   --month=2026-03        (seeds transactions for every day of March 2026)
     *   --date=2026-03-30      (single day; ignored when --month is set)
     *   --starting_number=728  (form starting number for the opening date)
     *   --opening_date=2026-03-01
     */
    protected $signature = 'mpcs:seed-f9a-data
                            {--business_id=1 : Business ID to seed data for}
                            {--month= : Seed every day of this month (Y-m format, e.g. 2026-03)}
                            {--date= : Single date (Y-m-d). Ignored when --month is provided. Defaults to today}
                            {--starting_number=1 : Starting form number (used in 9A settings)}
                            {--opening_date= : Opening date for the settings record (defaults to first seeded date)}';

    protected $description = 'Seed F9A Daily Cash & Sales Report: creates Mpcs9aFormSettings and cash/credit transactions per sub-category';

    private int     $locationId;
    private Contact $customer;

    /** @var array<int, array{id:int, name:string}> sub-category => product map */
    private array $subCatProducts = [];

    public function handle(): int
    {
        $businessId     = (int) $this->option('business_id');
        $startingNumber = (int) $this->option('starting_number');

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

        $this->customer = $this->ensureCustomer($businessId);
        $this->resolveSubCatProducts($businessId);

        if (empty($this->subCatProducts)) {
            $this->error("No sub-category products found for business {$businessId}.");
            return 1;
        }

        $dates       = $this->resolveDates();
        $openingDate = $this->option('opening_date') ?: $dates[0];

        $this->ensureSettings($businessId, $openingDate, $startingNumber);

        $this->info("Seeding " . count($dates) . " day(s) across " . count($this->subCatProducts) . " sub-categories ...");
        $this->newLine();

        foreach ($dates as $date) {
            $this->line("── {$date} ──────────────────────");
            foreach ($this->subCatProducts as $subCatId => $product) {
                $this->seedCashSale($businessId, $date, $subCatId, $product);
                $this->seedCreditSale($businessId, $date, $subCatId, $product);
            }
        }

        $this->newLine();
        $this->info('Done. Open F9A Form and filter by the seeded date(s) to see the data.');

        return 0;
    }

    // ── Resolve one product per sub-category ──────────────────────────────────

    private function resolveSubCatProducts(int $businessId): void
    {
        $subCategories = Category::where('business_id', $businessId)
            ->where('parent_id', '!=', 0)
            ->orderBy('name')
            ->get(['id', 'name']);

        foreach ($subCategories as $cat) {
            $product = Product::where('business_id', $businessId)
                ->where('sub_category_id', $cat->id)
                ->first();

            if (!$product) {
                $this->warn("  No product for sub-category [{$cat->name}], skipping.");
                continue;
            }

            $this->subCatProducts[$cat->id] = [
                'id'   => $product->id,
                'name' => $product->name,
                'cat'  => $cat->name,
            ];

            $this->info("Sub-cat  : [{$cat->name}] → product [{$product->name}] (ID: {$product->id})");
        }
    }

    // ── 9A Settings ───────────────────────────────────────────────────────────

    private function ensureSettings(int $businessId, string $openingDate, int $startingNumber): void
    {
        if (Mpcs9aFormSettings::where('business_id', $businessId)->exists()) {
            $this->info("9A settings already exist for business {$businessId}, skipping.");
            return;
        }

        $subCategoriesData = [];
        foreach ($this->subCatProducts as $subCatId => $product) {
            $subCategoriesData[] = [
                'id'                    => $subCatId,
                'name'                  => $product['cat'],
                'cash_previous_day'     => 0,
                'credit_previous_day'   => 0,
                'sales_previous_day'    => 0,
                'receipts_previous_day' => 0,
            ];
        }

        Mpcs9aFormSettings::create([
            'business_id'         => $businessId,
            'date'                => $openingDate,
            'starting_number'     => $startingNumber,
            'ref_pre_form_number' => $startingNumber,
            'total_sale_to_pre'   => 0,
            'pre_day_cash_sale'   => 0,
            'pre_day_card_sale'   => 0,
            'pre_day_credit_sale' => 0,
            'pre_day_cash'        => 0,
            'pre_day_cheques'     => 0,
            'pre_day_bank_manual' => json_encode([]),
            'pre_day_card_manual' => json_encode([]),
            'pre_day_total'       => 0,
            'pre_day_balance'     => 0,
            'pre_day_grand_total' => 0,
            'no_of_rows_per_page' => 10,
            'sub_categories_data' => json_encode($subCategoriesData),
        ]);

        $this->info("9A settings created (opening_date={$openingDate}, starting_number={$startingNumber}).");
    }

    // ── Cash sale per sub-category ────────────────────────────────────────────

    private function seedCashSale(int $businessId, string $date, int $subCatId, array $product): void
    {
        $rows = [
            ['suffix' => 'CA', 'qty' => 20.00, 'price' => 150.00],
            ['suffix' => 'CB', 'qty' => 35.50, 'price' => 150.00],
        ];

        foreach ($rows as $row) {
            $invoiceNo = "F9A-CASH-{$businessId}-{$subCatId}-" . str_replace('-', '', $date) . "-{$row['suffix']}";
            $amount    = $row['qty'] * $row['price'];

            if (Transaction::where('invoice_no', $invoiceNo)->where('business_id', $businessId)->exists()) {
                $this->warn("  Cash {$invoiceNo} already exists, skipping.");
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
                'final_total'      => $amount,
                'total_before_tax' => $amount,
                'payment_status'   => 'paid',
                'is_credit_sale'   => 0,
                'created_by'       => 1,
            ]);

            $variationId = DB::table('variations')->where('product_id', $product['id'])->value('id') ?? 1;

            DB::table('transaction_sell_lines')->insert([
                'transaction_id'       => $tx->id,
                'product_id'           => $product['id'],
                'variation_id'         => $variationId,
                'quantity'             => $row['qty'],
                'unit_price'           => $row['price'],
                'unit_price_inc_tax'   => $row['price'],
                'item_tax'             => 0,
                'tax_id'               => null,
                'line_discount_type'   => 'fixed',
                'line_discount_amount' => 0,
                'line_total'           => $amount,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::table('transaction_payments')->insert([
                'transaction_id' => $tx->id,
                'amount'         => $amount,
                'method'         => 'cash',
                'is_return'      => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $this->info("  [{$product['cat']}] Cash {$invoiceNo} | qty={$row['qty']} | total={$amount}");
        }
    }

    // ── Credit sale per sub-category ──────────────────────────────────────────

    private function seedCreditSale(int $businessId, string $date, int $subCatId, array $product): void
    {
        $invoiceNo = "F9A-CRED-{$businessId}-{$subCatId}-" . str_replace('-', '', $date);
        $qty       = 10.00;
        $price     = 150.00;
        $amount    = $qty * $price;

        if (Transaction::where('invoice_no', $invoiceNo)->where('business_id', $businessId)->exists()) {
            $this->warn("  Credit {$invoiceNo} already exists, skipping.");
            return;
        }

        $tx = Transaction::create([
            'business_id'      => $businessId,
            'location_id'      => $this->locationId,
            'type'             => 'sell',
            'status'           => 'final',
            'contact_id'       => $this->customer->id,
            'transaction_date' => $date . ' 10:00:00',
            'invoice_no'       => $invoiceNo,
            'ref_no'           => $invoiceNo,
            'final_total'      => $amount,
            'total_before_tax' => $amount,
            'payment_status'   => 'due',
            'is_credit_sale'   => 1,
            'created_by'       => 1,
        ]);

        $variationId = DB::table('variations')->where('product_id', $product['id'])->value('id') ?? 1;

        DB::table('transaction_sell_lines')->insert([
            'transaction_id'       => $tx->id,
            'product_id'           => $product['id'],
            'variation_id'         => $variationId,
            'quantity'             => $qty,
            'unit_price'           => $price,
            'unit_price_inc_tax'   => $price,
            'item_tax'             => 0,
            'tax_id'               => null,
            'line_discount_type'   => 'fixed',
            'line_discount_amount' => 0,
            'line_total'           => $amount,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('transaction_payments')->insert([
            'transaction_id' => $tx->id,
            'amount'         => $amount,
            'method'         => 'credit_sale',
            'is_return'      => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->info("  [{$product['cat']}] Credit {$invoiceNo} | qty={$qty} | total={$amount}");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
}
