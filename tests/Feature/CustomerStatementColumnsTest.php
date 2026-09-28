<?php

namespace Tests\Feature;

use App\Business;
use App\Contact;
use App\CustomerStatement;
use App\CustomerStatementDetail;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerStatementColumnsTest extends TestCase
{
    use DatabaseTransactions;

    public function testShowCustomerStatementDisplaysMissingColumns()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);
        
        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session()->put('business.id', $business->id);
        session()->put('user.business_id', $business->id);

        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Statement Customer',
            'is_default' => 0,
            'created_by' => $user->id
        ]);

        $statement = CustomerStatement::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'statement_no' => 'ST-TEST-1',
            'print_date' => date('Y-m-d'),
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'added_by' => $user->id,
        ]);

        CustomerStatementDetail::create([
            'business_id' => $business->id,
            'statement_id' => $statement->id,
            'date' => '2026-05-20',
            'location' => 'Test Location ABC',
            'invoice_no' => 'INV-TEST-CS1',
            'customer_reference' => 'REF-XYZ-123',
            'order_no' => 'PO-999',
            'vehicle_number' => 'VEH-456',
            'route_name' => 'Test Route Route',
            'order_date' => '2026-05-18',
            'product' => 'Test Product',
            'unit_price' => 10.00,
            'qty' => 5,
            'invoice_amount' => 50.00,
            'due_amount' => 50.00,
        ]);

        // Call showCustomerStatement
        $response = $this->get(action('CustomerStatementController@showCustomerStatement', [$statement->id]) . '?columns=1,2,3,4,5,6,7,8,9,10,11,12,13');

        $response->assertStatus(200);

        // Assert that the headers are present in the response
        $response->assertSee('Location');
        $response->assertSee('Route');
        $response->assertSee('Vehicle');
        $response->assertSee('Customer Reference');
        $response->assertSee('Customer P/O No');
        $response->assertSee('Voucher Order Date');
        $response->assertSee('Due Amount');

        // Assert that the detail values are present in the response
        $response->assertSee('Test Location ABC');
        $response->assertSee('Test Route Route');
        $response->assertSee('REF-XYZ-123');
        $response->assertSee('PO-999');
        $response->assertSee('VEH-456');
        $response->assertSee('2026-05-18');

        // Call rePrintListCustomerState
        $printResponse = $this->get(action('CustomerStatementController@rePrintListCustomerState', [$statement->id]) . '?columns=1,2,3,4,5,6,7,8,9,10,11,12,13');
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Location');
        $printResponse->assertSee('Route');
        $printResponse->assertSee('Vehicle');
        $printResponse->assertSee('Customer Reference');
        $printResponse->assertSee('Customer P/O No');
        $printResponse->assertSee('Voucher Order Date');
        $printResponse->assertSee('Due Amount');
        $printResponse->assertSee('Test Location ABC');
        $printResponse->assertSee('Test Route Route');
        $printResponse->assertSee('REF-XYZ-123');
        $printResponse->assertSee('PO-999');
        $printResponse->assertSee('VEH-456');
        $printResponse->assertSee('2026-05-18');

        // Call exportExcelListCustomerStatement
        $excelResponse = $this->get(action('CustomerStatementController@exportExcelListCustomerStatement', [$statement->id]) . '?columns=1,2,3,4,5,6,7,8,9,10,11,12,13');
        $excelResponse->assertStatus(200);
        // Note: Excel download response contains binary data or HTML since we are using FromView in ListCustomerStatement export.
        // Let's assert that the download has correct headers or is a download response.
        $excelResponse->assertHeader('content-disposition', 'attachment; filename=CustomerStatement.xls');
    }

    public function testExcelExportColumnsAreInCorrectOrder()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);
        
        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session()->put('business.id', $business->id);
        session()->put('user.business_id', $business->id);

        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Statement Customer',
            'is_default' => 0,
            'created_by' => $user->id
        ]);

        $statement = CustomerStatement::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'statement_no' => 'ST-TEST-2',
            'print_date' => date('Y-m-d'),
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'added_by' => $user->id,
        ]);

        // Set session on the request for the view to use
        $request = request();
        $request->setLaravelSession(session()->driver());

        // Render view with columns in scrambled order
        $view = view('customer_statement.list_customer_statement_export', [
            'logo' => null,
            'contact' => $customer,
            'business_details' => null,
            'for_pdf' => 1,
            'location_details' => null,
            'statement' => $statement,
            'reprint_no' => 0,
            'visible_cols' => ['due_amount', 'location', 'date'],
            'bill_lines' => collect([]),
        ])->render();

        // The correct order should be Date, then Location, then Balance Due
        $posDate = strpos($view, __('messages.date'));
        $posLocation = strpos($view, 'Location');
        $posDue = strpos($view, 'Balance Due');

        $this->assertNotFalse($posDate, "Date column header should be present");
        $this->assertNotFalse($posLocation, "Location column header should be present");
        $this->assertNotFalse($posDue, "Balance Due column header should be present");

        $this->assertTrue($posDate < $posLocation, "Date column should appear before Location column");
        $this->assertTrue($posLocation < $posDue, "Location column should appear before Balance Due column");
    }

    public function testShowCustomerStatementFallbackWhenReportConfigurationHasNoVisibilityKeys()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);
        
        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session()->put('business.id', $business->id);
        session()->put('user.business_id', $business->id);

        // Create ReportConfiguration with only font settings
        \App\ReportConfiguration::create([
            'business_id' => $business->id,
            'name' => 'customer_statement_report',
            'configurations' => json_encode([
                'statement_title_size' => '20',
                'business_address_size' => '17'
            ])
        ]);

        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Statement Customer',
            'is_default' => 0,
            'created_by' => $user->id
        ]);

        $statement = CustomerStatement::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'statement_no' => 'ST-TEST-3',
            'print_date' => date('Y-m-d'),
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'added_by' => $user->id,
        ]);

        CustomerStatementDetail::create([
            'business_id' => $business->id,
            'statement_id' => $statement->id,
            'date' => '2026-05-20',
            'location' => 'Test Location ABC',
            'invoice_no' => 'INV-TEST-CS1',
            'customer_reference' => 'REF-XYZ-123',
            'order_no' => 'PO-999',
            'vehicle_number' => 'VEH-456',
            'route_name' => 'Test Route Route',
            'order_date' => '2026-05-18',
            'product' => 'Test Product',
            'unit_price' => 10.00,
            'qty' => 5,
            'invoice_amount' => 50.00,
            'due_amount' => 50.00,
        ]);

        // Call showCustomerStatement
        $response = $this->get(action('CustomerStatementController@showCustomerStatement', [$statement->id]) . '?columns=1,2,3,4,5,6,7,8,9,10,11,12,13');

        $response->assertStatus(200);

        // Assert that the headers and detail values are present (defaulting to visible)
        $response->assertSee('Location');
        $response->assertSee('Route');
        $response->assertSee('Vehicle');
        $response->assertSee('Customer Reference');
        $response->assertSee('Customer P/O No');
        $response->assertSee('Voucher Order Date');
        $response->assertSee('Due Amount');
        $response->assertSee('Test Location ABC');
    }

    public function testSettingsMergeInsteadOfOverwrite()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);
        
        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session()->put('business.id', $business->id);
        session()->put('user.business_id', $business->id);

        // 1. Post to updateCustomerStatementFont
        $fontData = [
            'statement_title_size' => '22',
            'business_address_size' => '18'
        ];
        $this->post(action('ReportConfigurationsController@updateCustomerStatementFont'), $fontData);

        // Verify font settings saved
        $config = \App\ReportConfiguration::where('business_id', $business->id)->where('name', 'customer_statement_report')->first();
        $this->assertNotNull($config);
        $configurations = json_decode($config->configurations, true);
        $this->assertEquals('22', $configurations['statement_title_size']);

        // 2. Post to store (column visibility)
        $columnData = [
            'customer_statement_report' => [
                'date' => '1',
                'location' => '1'
            ]
        ];
        $this->post(action('ReportConfigurationsController@store'), $columnData);

        // Verify BOTH font setting and column visibility are preserved (merged)
        $config->refresh();
        $configurations = json_decode($config->configurations, true);
        $this->assertEquals('22', $configurations['statement_title_size'], "Font setting should be preserved after saving column settings");
        $this->assertEquals('1', $configurations['date'], "Column settings should be saved");
        $this->assertEquals('1', $configurations['location'], "Column settings should be saved");

        // 3. Post to updateCustomerStatementFont again
        $fontData2 = [
            'statement_title_size' => '24',
        ];
        $this->post(action('ReportConfigurationsController@updateCustomerStatementFont'), $fontData2);

        // Verify column settings are preserved after saving font settings
        $config->refresh();
        $configurations = json_decode($config->configurations, true);
        $this->assertEquals('24', $configurations['statement_title_size']);
        $this->assertEquals('1', $configurations['date'], "Column settings should be preserved after saving font settings");
        $this->assertEquals('1', $configurations['location'], "Column settings should be preserved after saving font settings");
    }

    public function testShowCustomerStatementHidesOmittedColumns()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);
        
        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session()->put('business.id', $business->id);
        session()->put('user.business_id', $business->id);

        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Statement Customer',
            'is_default' => 0,
            'created_by' => $user->id
        ]);

        $statement = CustomerStatement::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'statement_no' => 'ST-TEST-4',
            'print_date' => date('Y-m-d'),
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'added_by' => $user->id,
        ]);

        CustomerStatementDetail::create([
            'business_id' => $business->id,
            'statement_id' => $statement->id,
            'date' => '2026-05-20',
            'location' => 'Test Location ABC',
            'invoice_no' => 'INV-TEST-CS1',
            'customer_reference' => 'REF-XYZ-123',
            'order_no' => 'PO-999',
            'vehicle_number' => 'VEH-456',
            'route_name' => 'Test Route Route',
            'order_date' => '2026-05-18',
            'product' => 'Test Product',
            'unit_price' => 10.00,
            'qty' => 5,
            'invoice_amount' => 50.00,
            'due_amount' => 50.00,
        ]);

        // Call showCustomerStatement and omit columns: 2 (location) and 6 (customer_reference)
        // Passed columns: 1,3,4,5,7,8,9,10,11,12,13
        $response = $this->get(action('CustomerStatementController@showCustomerStatement', [$statement->id]) . '?columns=1,3,4,5,7,8,9,10,11,12,13');

        $response->assertStatus(200);

        // Assert that the headers are NOT present in the response
        $response->assertDontSee('Location');
        $response->assertDontSee('Customer Reference');

        // Assert that the detail values are NOT present in the response
        $response->assertDontSee('Test Location ABC');
        $response->assertDontSee('REF-XYZ-123');

        // Call rePrintListCustomerState and omit columns: 2 (location) and 6 (customer_reference)
        $printResponse = $this->get(action('CustomerStatementController@rePrintListCustomerState', [$statement->id]) . '?columns=1,3,4,5,7,8,9,10,11,12,13');
        $printResponse->assertStatus(200);
        $printResponse->assertDontSee('Location');
        $printResponse->assertDontSee('Customer Reference');
        $printResponse->assertDontSee('Test Location ABC');
        $printResponse->assertDontSee('REF-XYZ-123');
    }
}


