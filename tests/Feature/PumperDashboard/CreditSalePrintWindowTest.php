<?php

namespace Tests\Feature\PumperDashboard;

use App\Business;
use App\Contact;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\PumperDashboard\Entities\SettlementCreditSalePayment;
use Modules\PumperDashboard\Entities\PumpOperator;
use Tests\TestCase;

/**
 * Tests that the credit sale print route returns proper HTML content (not blank).
 * Covers: GET /pumper-dashboard/pump-operator-pmts/print-credit-sale/{id}
 */
class CreditSalePrintWindowTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * The print route must return 200 with HTML containing window.print().
     * This ensures the tab opened by JS will not be blank.
     */
    public function test_print_credit_sale_route_returns_html_with_window_print_call()
    {
        $business = Business::first();
        $this->assertNotNull($business, 'Need at least one business in the database.');

        $user = User::where('business_id', $business->id)->first();
        $this->assertNotNull($user, 'Need at least one user for this business.');

        // Find or create a SettlementCreditSalePayment to print
        $scsp = SettlementCreditSalePayment::where('business_id', $business->id)->first();

        if (empty($scsp)) {
            $this->markTestSkipped('No SettlementCreditSalePayment found for this business.');
        }

        $response = $this->actingAs($user)
            ->withSession([
                'business.id'      => $business->id,
                'user.business_id' => $business->id,
            ])
            ->get('/pumper-dashboard/pump-operator-pmts/print-credit-sale/' . $scsp->id);

        $response->assertStatus(200);

        // The print view must contain window.print() so the browser auto-prints
        $response->assertSee('window.print()', false);
    }

    /**
     * The print route with ?copy=customer must return Customer Copy label.
     */
    public function test_print_credit_sale_route_with_copy_customer_shows_customer_copy()
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        if (empty($user)) {
            $this->markTestSkipped('No user found.');
        }

        $scsp = SettlementCreditSalePayment::where('business_id', $business->id)->first();

        if (empty($scsp)) {
            $this->markTestSkipped('No SettlementCreditSalePayment found for this business.');
        }

        $response = $this->actingAs($user)
            ->withSession([
                'business.id'      => $business->id,
                'user.business_id' => $business->id,
            ])
            ->get('/pumper-dashboard/pump-operator-pmts/print-credit-sale/' . $scsp->id . '?copy=customer');

        $response->assertStatus(200);
        $response->assertSee('Customer Copy', false);
    }

    /**
     * The print route with ?copy=both must return both Customer Copy and Merchant Copy.
     */
    public function test_print_credit_sale_route_with_copy_both_shows_two_copies()
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        if (empty($user)) {
            $this->markTestSkipped('No user found.');
        }

        $scsp = SettlementCreditSalePayment::where('business_id', $business->id)->first();

        if (empty($scsp)) {
            $this->markTestSkipped('No SettlementCreditSalePayment found for this business.');
        }

        $response = $this->actingAs($user)
            ->withSession([
                'business.id'      => $business->id,
                'user.business_id' => $business->id,
            ])
            ->get('/pumper-dashboard/pump-operator-pmts/print-credit-sale/' . $scsp->id . '?copy=both');

        $response->assertStatus(200);
        $response->assertSee('Customer Copy', false);
        $response->assertSee('Merchant Copy', false);
    }

    /**
     * JS must NOT use document.write() for the print window.
     * Verifies the fix: payments.blade.php should not contain the broken pattern.
     */
    public function test_payments_blade_does_not_use_document_write_for_print_window()
    {
        $bladeContent = file_get_contents(
            base_path('Modules/PumperDashboard/Resources/views/actions/payments.blade.php')
        );

        // The broken pattern: writing html_content via document.write into a blank window
        $this->assertStringNotContainsString(
            'printWindow.document.write(result.html_content)',
            $bladeContent,
            'payments.blade.php must not use document.write() to inject HTML into the print window. Use printWindow.location.href instead.'
        );
    }

    /**
     * JS must use printWindow.location.href with print_credit_sale_id.
     * Verifies the fix is in place.
     */
    public function test_payments_blade_uses_location_href_for_print_window()
    {
        $bladeContent = file_get_contents(
            base_path('Modules/PumperDashboard/Resources/views/actions/payments.blade.php')
        );

        $this->assertStringContainsString(
            'print-credit-sale/',
            $bladeContent,
            'payments.blade.php must navigate printWindow to the print-credit-sale route.'
        );

        $this->assertStringContainsString(
            'printWindow.location.href',
            $bladeContent,
            'payments.blade.php must use printWindow.location.href to navigate to the print route.'
        );
    }

    /**
     * JS must expose resetCreditSaleFormFull globally so it can be called from outside $(document).ready block.
     */
    public function test_payments_blade_exposes_reset_credit_sale_form_full_globally()
    {
        $bladeContent = file_get_contents(
            base_path('Modules/PumperDashboard/Resources/views/actions/payments.blade.php')
        );

        $this->assertStringContainsString(
            'window.resetCreditSaleFormFull',
            $bladeContent,
            'payments.blade.php must expose resetCreditSaleFormFull to window scope.'
        );
    }

    /**
     * The print route must consolidate all credit sale items with the same bill_number.
     */
    public function test_print_credit_sale_route_consolidates_multiple_items_under_same_bill_number()
    {
        $business = Business::first();
        $this->assertNotNull($business, 'Need at least one business in the database.');

        $user = User::where('business_id', $business->id)->first();
        $this->assertNotNull($user, 'Need at least one user for this business.');

        $location = \App\BusinessLocation::where('business_id', $business->id)->first();
        if (!$location) {
            $this->markTestSkipped('Need at least one business location.');
        }

        $contact = Contact::where('business_id', $business->id)->first();
        if (!$contact) {
            $contact = Contact::create([
                'business_id' => $business->id,
                'type' => 'customer',
                'name' => 'Consolidated Customer',
            ]);
        }

        $unit = \App\Unit::where('business_id', $business->id)->first();
        if (!$unit) {
            $unit = \App\Unit::create([
                'business_id' => $business->id,
                'actual_name' => 'Litre',
                'short_name' => 'Ltr',
                'allow_decimal' => 1,
                'created_by' => $user->id,
            ]);
        }

        $product = \App\Product::where('business_id', $business->id)->first();
        if (!$product) {
            $product = \App\Product::create([
                'name' => 'Test Fuel',
                'business_id' => $business->id,
                'type' => 'single',
                'unit_id' => $unit->id,
                'sku' => 'TESTFUEL-' . uniqid(),
                'tax_type' => 'inclusive',
                'created_by' => $user->id,
            ]);
        }

        $pump_operator = PumpOperator::where('business_id', $business->id)->first();
        if (!$pump_operator) {
            $pump_operator = PumpOperator::create([
                'business_id' => $business->id,
                'name' => 'Test Operator',
                'location_id' => $location->id,
            ]);
        }

        $pop = \Modules\PumperDashboard\Entities\PumpOperatorPayment::create([
            'business_id' => $business->id,
            'pump_operator_id' => $pump_operator->id,
            'payment_type' => 'credit',
            'payment_amount' => 300,
            'created_by' => $user->id,
            'collection_form_no' => '9999',
        ]);

        $bill_number = 'TESTBILL-' . uniqid();
        $order_date = date('Y-m-d');

        $dv1 = \Modules\Petro\Entities\DailyVoucher::create([
            'business_id' => $business->id,
            'transaction_date' => $order_date,
            'daily_vouchers_no' => '9999',
            'location_id' => $location->id,
            'operator_id' => $pump_operator->id,
            'customer_id' => $contact->id,
            'current_outstanding' => 0,
            'outstanding_pending' => 0,
            'voucher_order_number' => 'ORD-1',
            'voucher_order_date' => $order_date,
            'status' => 1,
            'created_by' => $user->id,
            'total_amount' => 100,
        ]);
        
        $dv_item1 = \Modules\Petro\Entities\DailyVoucherItem::create([
            'business_id' => $business->id,
            'daily_voucher_id' => $dv1->id,
            'product_id' => $product->id,
            'unit_price' => 100,
            'qty' => 1,
            'sub_total' => 100,
        ]);

        $dv2 = \Modules\Petro\Entities\DailyVoucher::create([
            'business_id' => $business->id,
            'transaction_date' => $order_date,
            'daily_vouchers_no' => '9999',
            'location_id' => $location->id,
            'operator_id' => $pump_operator->id,
            'customer_id' => $contact->id,
            'current_outstanding' => 0,
            'outstanding_pending' => 0,
            'voucher_order_number' => 'ORD-2',
            'voucher_order_date' => $order_date,
            'status' => 1,
            'created_by' => $user->id,
            'total_amount' => 200,
        ]);
        
        $dv_item2 = \Modules\Petro\Entities\DailyVoucherItem::create([
            'business_id' => $business->id,
            'daily_voucher_id' => $dv2->id,
            'product_id' => $product->id,
            'unit_price' => 200,
            'qty' => 1,
            'sub_total' => 200,
        ]);

        $scsp1_id = \DB::table('settlement_credit_sale_payments')->insertGetId([
            'business_id' => $business->id,
            'pump_operator_id' => $pump_operator->id,
            'customer_id' => $contact->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'order_date' => $order_date,
            'price' => 100,
            'qty' => 1,
            'amount' => 100,
            'sub_total' => 100,
            'bill_number' => $bill_number,
            'daily_voucher_id' => $dv1->id,
            'pump_payment_id' => $pop->id,
            'is_from_pumper' => 1,
            'collection_form_no' => '9999',
        ]);

        $scsp2_id = \DB::table('settlement_credit_sale_payments')->insertGetId([
            'business_id' => $business->id,
            'pump_operator_id' => $pump_operator->id,
            'customer_id' => $contact->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-2',
            'order_date' => $order_date,
            'price' => 200,
            'qty' => 1,
            'amount' => 200,
            'sub_total' => 200,
            'bill_number' => $bill_number,
            'daily_voucher_id' => $dv2->id,
            'pump_payment_id' => $pop->id,
            'is_from_pumper' => 1,
            'collection_form_no' => '9999',
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'business.id'      => $business->id,
                'user.business_id' => $business->id,
            ])
            ->get('/pumper-dashboard/pump-operator-pmts/print-credit-sale/' . $scsp1_id);

        $response->assertStatus(200);

        // It must display the details of both items: 100 and 200 under the same bill
        $response->assertSee('100.00');
        $response->assertSee('200.00');
    }
}


