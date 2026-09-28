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

class SeedF14BFormData extends Command
{
    /**
     * php artisan mpcs:seed-f14b-data
     *   --business_id=3
     *   --month=2026-03        (seeds credit sales for every day of the month)
     *   --date=2026-03-30      (single day; ignored when --month is set)
     *   --starting_number=1    (settlement_no starting value)
     */
    protected $signature = 'mpcs:seed-f14b-data
                            {--business_id=1 : Business ID to seed data for}
                            {--month= : Seed every day of this month (Y-m format, e.g. 2026-03)}
                            {--date= : Single date (Y-m-d). Ignored when --month is provided. Defaults to today}
                            {--starting_number=1 : Starting settlement_no for the first day}';

    protected $description = 'Seed F14B Form data: creates credit sale transactions and settlement_credit_sale_payments records so the F14B page is populated';

    private int     $locationId;
    private Contact $customer;
    private Contact $supplier;
    private array   $products = [];

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

        // ── Resolve contacts & products once ─────────────────────────────────
        $this->customer  = $this->ensureCustomer($businessId);
        $this->supplier  = $this->ensureSupplier($businessId);
        $this->resolveProducts($businessId);

        if (empty($this->products)) {
            $this->error("No products found for business {$businessId}.");
            return 1;
        }

        $this->info("Products : " . count($this->products) . " product(s) will be used per day.");

        // ── Build list of dates ───────────────────────────────────────────────
        $dates = $this->resolveDates();
        $this->info("Seeding " . count($dates) . " day(s) ...");
        $this->newLine();

        foreach ($dates as $index => $date) {
            $settlementNo = $startingNumber + $index;
            $this->line("── {$date} (settlement #{$settlementNo}) ──────────────────────");
            $this->seedDay($businessId, $date, $settlementNo);
        }

        $this->newLine();
        $this->info('Done. Open F14B and filter by the seeded month/date to see the data.');

        return 0;
    }

    // ── Core seeder ───────────────────────────────────────────────────────────

    private function seedDay(int $businessId, string $date, int $settlementNo): void
    {
        // Seed 2 credit sale transactions per day, each with a settlement payment record
        $rows = [
            ['suffix' => 'A', 'qty' => 20.00,  'price' => 150.00],
            ['suffix' => 'B', 'qty' => 35.50,  'price' => 150.00],
        ];

        foreach ($rows as $i => $row) {
            $product   = $this->products[$i % count($this->products)];
            $invoiceNo = "F14B-{$businessId}-" . str_replace('-', '', $date) . "-{$row['suffix']}";
            $orderNo   = "ORD-{$businessId}-" . str_replace('-', '', $date) . "-{$row['suffix']}";
            $amount    = $row['qty'] * $row['price'];

            // Skip if already seeded
            if (Transaction::where('invoice_no', $invoiceNo)->where('business_id', $businessId)->exists()) {
                $this->warn("  Transaction {$invoiceNo} already exists, skipping.");
                continue;
            }

            // ── 1. Create the credit sale transaction ─────────────────────────
            $transaction = Transaction::create([
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
                'payment_status'   => 'due',   // credit sale — not yet paid
                'is_credit_sale'   => 1,
                'created_by'       => 1,
            ]);

            // ── 2. Sell line ──────────────────────────────────────────────────
            $variationId = DB::table('variations')->where('product_id', $product['id'])->value('id') ?? 1;

            DB::table('transaction_sell_lines')->insert([
                'transaction_id'       => $transaction->id,
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

            // ── 3. settlement_credit_sale_payments record ─────────────────────
            $scspId = DB::table('settlement_credit_sale_payments')->insertGetId([
                'settlement_no'      => (string) ($settlementNo * 10 + $i),
                'business_id'        => $businessId,
                'customer_id'        => $this->customer->id,
                'product_id'         => $product['id'],
                'order_number'       => $orderNo,
                'order_date'         => $date,
                'customer_reference' => 'REF-' . $invoiceNo,
                'price'              => $row['price'],
                'discount'           => 0,
                'total_discount'     => 0,
                'sub_total'          => $amount,
                'qty'                => $row['qty'],
                'amount'             => $amount,
                'outstanding'        => $amount,
                'credit_limit'       => 0,
                'note'               => null,
                'transaction_id'     => $transaction->id,
                'bill_number'        => $invoiceNo,
                'is_from_pumper'     => 0,
                'is_committed'       => 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // ── 4. Link credit_sale_id back to the transaction ────────────────
            Transaction::where('id', $transaction->id)->update(['credit_sale_id' => $scspId]);

            $this->info("  Credit sale {$invoiceNo} | qty={$row['qty']} | amount={$amount} | scsp_id={$scspId}");
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveProducts(int $businessId): void
    {
        $rows = Product::where('business_id', $businessId)
            ->whereNotNull('sub_category_id')
            ->limit(4)
            ->get(['id', 'name', 'sku']);

        if ($rows->isEmpty()) {
            $rows = Product::where('business_id', $businessId)->limit(4)->get(['id', 'name', 'sku']);
        }

        foreach ($rows as $p) {
            $this->products[] = [
                'id'   => $p->id,
                'name' => $p->name,
                'sku'  => $p->sku ?? ('SKU-' . $p->id),
            ];
        }

        if (!empty($this->products)) {
            $this->info("Products : " . implode(', ', array_column($this->products, 'name')));
        }
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
