<?php

namespace Tests\Feature\Vat;

use Tests\TestCase;
use Modules\Vat\Http\Controllers\VatInvoiceToTransactionController;
use Modules\Vat\Entities\VatInvoice2;
use Modules\Vat\Entities\VatInvoiceToTransactionSetting;
use App\ContactLedger;
use App\AccountTransaction;
use App\Utils\ContactUtil;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VatInvoiceToTransactionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        $request = request();
        $request->setLaravelSession(app('session')->driver());
        session(['business.id' => 1]);
        
        // Ensure dummy setting is active
        VatInvoiceToTransactionSetting::create([
            'business_id' => 1,
            'auto_update' => 1,
            'status' => 'Active',
            'created_by' => 1,
            'note' => 'Test Setting'
        ]);

        // Create standard accounts for posting
        \App\Account::create([
            'name' => 'Accounts Receivable',
            'business_id' => 1,
            'account_number' => 'AR-001',
            'created_by' => 1
        ]);

        \App\Account::create([
            'name' => 'Finished Goods Account',
            'business_id' => 1,
            'account_number' => 'FG-001',
            'created_by' => 1
        ]);

        \App\Account::create([
            'name' => 'Cost of Goods Sold',
            'business_id' => 1,
            'account_number' => 'COGS-001',
            'created_by' => 1
        ]);

        \App\Account::create([
            'name' => 'Sales Income',
            'business_id' => 1,
            'account_number' => 'SI-001',
            'created_by' => 1
        ]);
    }

    /** @test */
    public function it_successfully_posts_to_customer_ledger_with_vat_invoice_sub_type()
    {
        $invoice = VatInvoice2::create([
            'business_id' => 1,
            'customer_id' => 1,
            'customer_bill_no' => 'VAT-TEST-999',
            'total_amount' => 5000,
            'date' => '2026-05-18',
            'created_by' => 1
        ]);

        // Trigger auto-posting
        VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($invoice);

        // Verify customer ledger entry exists with 'sub_type' => 'sell' and is 'debit'
        $ledgerEntry = ContactLedger::where('contact_id', 1)
            ->where('sub_type', 'sell')
            ->where('note', 'VAT Invoice No VAT-TEST-999')
            ->first();

        $this->assertNotNull($ledgerEntry, 'ContactLedger entry with sub_type sell should be created.');
        $this->assertEquals(5000, $ledgerEntry->amount);
        $this->assertEquals('debit', $ledgerEntry->type);
        $this->assertEquals('VAT Invoice No VAT-TEST-999', $ledgerEntry->note);
    }

    /** @test */
    public function it_cleans_up_previous_postings_on_edit_to_prevent_duplicates()
    {
        $invoice = VatInvoice2::create([
            'business_id' => 1,
            'customer_id' => 1,
            'customer_bill_no' => 'VAT-TEST-DUP',
            'total_amount' => 5000,
            'date' => '2026-05-18',
            'created_by' => 1
        ]);

        // Post initially
        VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($invoice);
        $this->assertEquals(1, ContactLedger::where('note', 'VAT Invoice No VAT-TEST-DUP')->count());

        // Update amount and trigger posting again (simulating edit/update)
        $invoice->total_amount = 7500;
        $invoice->save();

        VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($invoice);

        // Assert no duplicate entries exist, only the updated one
        $this->assertEquals(1, ContactLedger::where('note', 'VAT Invoice No VAT-TEST-DUP')->count());
        $this->assertEquals(7500, ContactLedger::where('note', 'VAT Invoice No VAT-TEST-DUP')->first()->amount);
    }

    /** @test */
    public function it_deletes_postings_completely_when_invoice_is_deleted()
    {
        $invoice = VatInvoice2::create([
            'business_id' => 1,
            'customer_id' => 1,
            'customer_bill_no' => 'VAT-TEST-DEL',
            'total_amount' => 3000,
            'date' => '2026-05-18',
            'created_by' => 1
        ]);

        VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($invoice);
        $this->assertEquals(1, ContactLedger::where('note', 'VAT Invoice No VAT-TEST-DEL')->count());

        // Simulate invoice deletion
        VatInvoiceToTransactionController::deleteVatInvoiceTransactions(1, 'VAT-TEST-DEL');

        // Assert posting is deleted completely
        $this->assertEquals(0, ContactLedger::where('note', 'VAT Invoice No VAT-TEST-DEL')->count());
    }

    /** @test */
    public function it_retrieves_vat_invoice_postings_in_customer_ledger_query()
    {
        $invoice = VatInvoice2::create([
            'business_id' => 1,
            'customer_id' => 1,
            'customer_bill_no' => 'VAT-TEST-LEDG',
            'total_amount' => 4500,
            'date' => '2026-05-18',
            'created_by' => 1
        ]);

        VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($invoice);

        // Fetch ledger using ContactUtil
        $contactUtil = app(ContactUtil::class);
        $ledgerData = $contactUtil->getCustomerLedger(1, 1, '2026-01-01', '2026-12-31');

        $transactions = $ledgerData;
        $hasVatInvoice = false;

        foreach ($transactions as $txn) {
            if ($txn->note === 'VAT Invoice No VAT-TEST-LEDG') {
                $hasVatInvoice = true;
                $this->assertEquals('debit', $txn->acc_transaction_type);
                $this->assertEquals(4500, $txn->amount);
            }
        }

        $this->assertTrue($hasVatInvoice, 'Customer Ledger transactions must include the auto-posted VAT Invoice.');
    }

    /** @test */
    public function it_can_instantiate_vat_invoice_2_controller()
    {
        $controller = app(\Modules\Vat\Http\Controllers\VatInvoice2Controller::class);
        $this->assertNotNull($controller);
    }

    /** @test */
    public function it_posts_finished_goods_cogs_and_sales_income_with_correct_formulas_and_descriptions()
    {
        // 1. Create custom Accounts for Category-linked COGS and Sales Income
        $customCogsAccount = \App\Account::create([
            'name' => 'Category COGS Account',
            'business_id' => 1,
            'account_number' => 'CAT-COGS-001',
            'created_by' => 1
        ]);

        $customSalesIncomeAccount = \App\Account::create([
            'name' => 'Category Sales Income Account',
            'business_id' => 1,
            'account_number' => 'CAT-SI-001',
            'created_by' => 1
        ]);

        // 2. Create Category linked to these Accounts
        $category = \App\Category::create([
            'name' => 'Test VAT Category',
            'business_id' => 1,
            'cogs_account_id' => $customCogsAccount->id,
            'sales_income_account_id' => $customSalesIncomeAccount->id,
            'created_by' => 1
        ]);

        // 3. Create Product linked to Category with Manage Stock enabled
        $product = \App\Product::create([
            'name' => 'Test Manage Stock Product',
            'business_id' => 1,
            'category_id' => $category->id,
            'type' => 'single',
            'unit_id' => 1,
            'enable_stock' => 1,
            'is_service' => 0,
            'sku' => 'VAT-PROD-123'
        ]);

        // 4. Create Variation for the Product with purchase price (dpp_inc_tax) = 150
        $variation = \App\Variation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
            'sub_sku' => 'VAT-PROD-123-VAR',
            'default_purchase_price' => 150,
            'dpp_inc_tax' => 150,
            'profit_percent' => 0,
            'default_sell_price' => 250,
            'sell_price_inc_tax' => 250
        ]);

        $customer = \App\Contact::create([
            'business_id' => 1,
            'type' => 'customer',
            'name' => 'Test Customer',
            'mobile' => '123456789'
        ]);

        // 5. Create VAT Invoice and Detail
        $invoice = VatInvoice2::create([
            'business_id' => 1,
            'customer_id' => $customer->id,
            'customer_bill_no' => 'VAT-TEST-PRODUCT',
            'total_amount' => 500,
            'date' => '2026-05-18',
            'created_by' => 1
        ]);

        \Modules\Vat\Entities\VatInvoiceDetail2::create([
            'issue_bill_id' => $invoice->id,
            'product_id' => $product->id,
            'qty' => 2,
            'unit_price' => 250,
            'line_tax' => 0,
            'grand_total' => 500,
            'business_id' => 1
        ]);

        // Trigger posting
        VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($invoice);

        // 6. Verify Finished Goods Account - CREDIT (Qty 2 * Purchase Price 150 = 300)
        $fgAccount = \App\Account::where('name', 'Finished Goods Account')->first();
        $fgTxn = \App\AccountTransaction::where('account_id', $fgAccount->id)
            ->where('note', "Customer Test Customer\nVAT Invoice VAT-TEST-PRODUCT")
            ->first();
        $this->assertNotNull($fgTxn, 'Finished Goods transaction should be posted.');
        $this->assertEquals('credit', $fgTxn->type);
        $this->assertEquals(300, $fgTxn->amount);

        // 7. Verify Category COGS Account - DEBIT (Qty 2 * Purchase Price 150 = 300)
        $cogsTxn = \App\AccountTransaction::where('account_id', $customCogsAccount->id)
            ->where('note', "Customer Test Customer\nVAT Invoice VAT-TEST-PRODUCT")
            ->first();
        $this->assertNotNull($cogsTxn, 'Category-linked COGS transaction should be posted.');
        $this->assertEquals('debit', $cogsTxn->type);
        $this->assertEquals(300, $cogsTxn->amount);

        // 8. Verify Category Sales Income Account - CREDIT (Qty 2 * Sale Price 250 = 500)
        $salesTxn = \App\AccountTransaction::where('account_id', $customSalesIncomeAccount->id)
            ->where('note', "Customer Test Customer\nVAT Invoice VAT-TEST-PRODUCT")
            ->first();
        $this->assertNotNull($salesTxn, 'Category-linked Sales Income transaction should be posted.');
        $this->assertEquals('credit', $salesTxn->type);
        $this->assertEquals(500, $salesTxn->amount);
    }

    /** @test */
    public function it_does_not_skip_duplicate_amounts_for_multiple_products_in_same_invoice()
    {
        $customCogsAccount = \App\Account::where('name', 'Cost of Goods Sold')->first();

        // Product A (Stock Managed)
        $productA = \App\Product::create([
            'name' => 'Product A',
            'business_id' => 1,
            'type' => 'single',
            'unit_id' => 1,
            'enable_stock' => 1,
            'is_service' => 0,
            'sku' => 'VAT-PROD-A'
        ]);

        \App\Variation::create([
            'product_id' => $productA->id,
            'name' => 'DUMMY',
            'sub_sku' => 'VAT-PROD-A-VAR',
            'default_purchase_price' => 150,
            'dpp_inc_tax' => 150,
            'profit_percent' => 0,
            'default_sell_price' => 250,
            'sell_price_inc_tax' => 250
        ]);

        // Product B (Stock Managed)
        $productB = \App\Product::create([
            'name' => 'Product B',
            'business_id' => 1,
            'type' => 'single',
            'unit_id' => 1,
            'enable_stock' => 1,
            'is_service' => 0,
            'sku' => 'VAT-PROD-B'
        ]);

        \App\Variation::create([
            'product_id' => $productB->id,
            'name' => 'DUMMY',
            'sub_sku' => 'VAT-PROD-B-VAR',
            'default_purchase_price' => 300,
            'dpp_inc_tax' => 300,
            'profit_percent' => 0,
            'default_sell_price' => 500,
            'sell_price_inc_tax' => 500
        ]);

        $customer = \App\Contact::create([
            'business_id' => 1,
            'type' => 'customer',
            'name' => 'Duplicate Test Customer',
            'mobile' => '987654321'
        ]);

        $invoice = VatInvoice2::create([
            'business_id' => 1,
            'customer_id' => $customer->id,
            'customer_bill_no' => 'VAT-TEST-DUP-ITEMS',
            'total_amount' => 1100,
            'date' => '2026-05-18',
            'created_by' => 1
        ]);

        \Modules\Vat\Entities\VatInvoiceDetail2::create([
            'issue_bill_id' => $invoice->id,
            'product_id' => $productA->id,
            'qty' => 2,
            'unit_price' => 250,
            'line_tax' => 0,
            'grand_total' => 500,
            'business_id' => 1
        ]);

        \Modules\Vat\Entities\VatInvoiceDetail2::create([
            'issue_bill_id' => $invoice->id,
            'product_id' => $productB->id,
            'qty' => 1,
            'unit_price' => 500,
            'line_tax' => 0,
            'grand_total' => 500,
            'business_id' => 1
        ]);

        VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($invoice);

        // Verify Finished Goods Account - CREDIT should have 2 entries of amount 300
        $fgAccount = \App\Account::where('name', 'Finished Goods Account')->first();
        $fgTxns = \App\AccountTransaction::where('account_id', $fgAccount->id)
            ->where('amount', 300)
            ->where('type', 'credit')
            ->where('note', "Customer Duplicate Test Customer\nVAT Invoice VAT-TEST-DUP-ITEMS")
            ->get();
        $this->assertCount(2, $fgTxns, 'Should have exactly two Finished Goods entries of amount 300.');

        // Verify Category COGS Account - DEBIT should have 2 entries of amount 300
        $cogsTxns = \App\AccountTransaction::where('account_id', $customCogsAccount->id)
            ->where('amount', 300)
            ->where('type', 'debit')
            ->where('note', "Customer Duplicate Test Customer\nVAT Invoice VAT-TEST-DUP-ITEMS")
            ->get();
        $this->assertCount(2, $cogsTxns, 'Should have exactly two COGS entries of amount 300.');
    }
}
