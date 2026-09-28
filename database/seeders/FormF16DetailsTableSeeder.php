<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormF16DetailsTableSeeder extends Seeder
{
    /**
     * Seed form_f16_details so F16A and F21 show data.
     * Uses existing purchases or creates one minimal purchase, then inserts F16A rows with today's date.
     */
    public function run()
    {
        $today = Carbon::today()->format('Y-m-d H:i:s');

        // 1) Get received purchases that don't already have an F16A record
        $purchases = DB::table('transactions')
            ->leftJoin('form_f16_details', 'transactions.id', '=', 'form_f16_details.transaction_id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->whereNull('form_f16_details.transaction_id')
            ->select(
                'transactions.id as transaction_id',
                'transactions.invoice_no',
                'transactions.ref_no',
                DB::raw('COALESCE(contacts.name, "Supplier") as supplier_name')
            )
            ->orderBy('transactions.id', 'desc')
            ->limit(10)
            ->get();

        // 2) If none, use ANY purchase (any status) and ensure it's received so it shows in F16A/F21
        if ($purchases->isEmpty()) {
            $any = DB::table('transactions')
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->where('transactions.type', 'purchase')
                ->select(
                    'transactions.id as transaction_id',
                    'transactions.invoice_no',
                    'transactions.ref_no',
                    DB::raw('COALESCE(contacts.name, "Supplier") as supplier_name')
                )
                ->orderBy('transactions.id', 'desc')
                ->limit(10)
                ->get();

            if ($any->isEmpty()) {
                $this->createMinimalPurchaseAndF16A($today);
                return;
            }

            foreach ($any as $row) {
                DB::table('transactions')->where('id', $row->transaction_id)->update(['status' => 'received']);
            }
            $purchases = $any;
        }

        $formNo = (int) DB::table('form_f16_details')->max('form_no') + 1;
        if ($formNo < 1) {
            $formNo = 1;
        }

        foreach ($purchases as $row) {
            $invoiceNo = $row->invoice_no ?? $row->ref_no ?? 'INV-' . $row->transaction_id;
            $supplier = strlen($row->supplier_name ?? '') > 50
                ? substr($row->supplier_name, 0, 47) . '...'
                : ($row->supplier_name ?? 'Supplier');

            DB::table('form_f16_details')->insert([
                'transaction_id'    => $row->transaction_id,
                'form_no'          => $formNo,
                'invoice_no'       => $invoiceNo,
                'supplier'         => $supplier,
                'this_form_total'   => '0',
                'last_form_total'   => '0',
                'grand_total'       => '0',
                'book_no'          => '',
                'book_stock'       => '',
                'this_book'        => '0',
                'prev_book'        => '0',
                'grand_book'       => '0',
                'created_at'       => $today,
                'updated_at'       => $today,
            ]);

            $formNo++;
        }

        $this->command->info('FormF16DetailsTableSeeder: Inserted ' . $purchases->count() . ' F16A record(s) with today\'s date. Data should appear in F16A and F21.');
    }

    /**
     * Create one minimal purchase transaction + purchase_line + form_f16_details when no purchases exist.
     */
    protected function createMinimalPurchaseAndF16A(string $today): void
    {
        $businessId = DB::table('transactions')->value('business_id');
        if (!$businessId) {
            $businessId = $this->tableExists('business') ? DB::table('business')->value('id') : null;
        }
        $locationId = $businessId ? DB::table('business_locations')->where('business_id', $businessId)->value('id') : null;
        $contactId = $businessId ? DB::table('contacts')->where('business_id', $businessId)->value('id') : null;
        $userId = DB::table('users')->value('id');
        $product = $businessId ? DB::table('products')->where('business_id', $businessId)->first() : null;
        if (!$businessId || !$locationId || !$contactId || !$userId || !$product) {
            $this->command->warn('FormF16DetailsTableSeeder: No business/location/contact/user/product found. Cannot create purchase.');
            return;
        }

        $variationId = DB::table('variations')->where('product_id', $product->id)->value('id') ?? $product->id;
        $invoiceNo = 'PO-SEED-' . date('YmdHis');
        $finalTotal = 100.00;

        $transactionId = DB::table('transactions')->insertGetId([
            'business_id'       => $businessId,
            'location_id'       => $locationId,
            'contact_id'        => $contactId,
            'type'              => 'purchase',
            'status'            => 'received',
            'payment_status'    => 'paid',
            'transaction_date'  => $today,
            'invoice_no'        => $invoiceNo,
            'ref_no'            => $invoiceNo,
            'final_total'       => $finalTotal,
            'total_before_tax'  => $finalTotal,
            'tax_amount'        => 0,
            'discount_amount'   => 0,
            'created_by'        => $userId,
            'created_at'        => $today,
            'updated_at'        => $today,
        ]);

        DB::table('purchase_lines')->insert([
            'transaction_id'         => $transactionId,
            'product_id'             => $product->id,
            'variation_id'            => $variationId,
            'quantity'               => 1,
            'pp_without_discount'    => $finalTotal,
            'discount_percent'       => 0,
            'purchase_price'         => $finalTotal,
            'purchase_price_inc_tax'  => $finalTotal,
            'item_tax'               => 0,
            'secondary_unit_quantity'=> 0,
            'created_at'             => $today,
            'updated_at'             => $today,
        ]);

        $formNo = (int) DB::table('form_f16_details')->max('form_no') + 1;
        if ($formNo < 1) {
            $formNo = 1;
        }

        $supplier = DB::table('contacts')->where('id', $contactId)->value('name') ?? 'Supplier';
        if (strlen($supplier) > 50) {
            $supplier = substr($supplier, 0, 47) . '...';
        }

        DB::table('form_f16_details')->insert([
            'transaction_id'    => $transactionId,
            'form_no'           => $formNo,
            'invoice_no'        => $invoiceNo,
            'supplier'          => $supplier,
            'this_form_total'   => '0',
            'last_form_total'   => '0',
            'grand_total'       => '0',
            'book_no'           => '',
            'book_stock'        => '',
            'this_book'         => '0',
            'prev_book'         => '0',
            'grand_book'        => '0',
            'created_at'        => $today,
            'updated_at'        => $today,
        ]);

        $this->command->info('FormF16DetailsTableSeeder: Created 1 purchase and 1 F16A record. Data should appear in F16A and F21.');
    }

    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
