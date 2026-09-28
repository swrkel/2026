<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955DistributionRemainingChecklistTest extends TestCase
{
    public function test_dis_invoice_list_uses_datatable_with_standard_toolbar_buttons(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString('id="dis_invoice_table"', $indexView);
        $this->assertStringContainsString("$('#dis_invoice_table').DataTable(", $indexView);
        $this->assertStringContainsString('buttons:', $indexView);
        $this->assertStringContainsString("'csv'", $indexView);
        $this->assertStringContainsString("'excel'", $indexView);
        $this->assertStringContainsString("'pdf'", $indexView);
        $this->assertStringContainsString("'print'", $indexView);
        $this->assertStringContainsString("'colvis'", $indexView);
    }

    public function test_dis_invoice_list_shows_payment_details_button_for_any_paid_amount(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString('@if (($inv->payment_total ?? 0) > 0)', $indexView);
        $this->assertStringNotContainsString('@if (($inv->grand_total - ($inv->payment_total ?? 0)) <= 0.009 && ($inv->payment_total ?? 0) > 0)', $indexView);
        $this->assertStringContainsString('Payment Details', $indexView);
    }

    public function test_user_to_routes_page_contains_filters_and_stacked_route_markup(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $userRoutesView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/settings/routes/user_maps/index.blade.php');

        $this->assertIsString($userRoutesView);
        $this->assertStringContainsString('id="user_routes_filter_form"', $userRoutesView);
        $this->assertStringContainsString('name="sales_rep_filter"', $userRoutesView);
        $this->assertStringContainsString('name="status_filter"', $userRoutesView);
        $this->assertStringContainsString('name="route_filter"', $userRoutesView);
        $this->assertStringContainsString('class="mapped-routes-list"', $userRoutesView);
        $this->assertStringNotContainsString("implode(', ', \$map->routes_mapped)", $userRoutesView);
    }

    public function test_sales_orders_list_contains_total_items_column_and_value(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $salesOrdersView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($salesOrdersView);
        $this->assertStringContainsString('<th>Total Items</th>', $salesOrdersView);
        $this->assertStringContainsString('data-total-items=', $salesOrdersView);
    }

    public function test_dis_invoice_notes_modal_contains_structured_changed_details_activity_sections(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString('id="modal_activity_created"', $indexView);
        $this->assertStringContainsString('id="modal_activity_changed"', $indexView);
        $this->assertStringContainsString('id="modal_activity_deleted"', $indexView);
        $this->assertStringContainsString('id="modal_activity_sales_order_note"', $indexView);
        $this->assertStringNotContainsString('id="modal_changed_activities"', $indexView);
    }
}
