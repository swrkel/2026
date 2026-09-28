<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderChangedDetailsActivityTest extends TestCase
{
    public function test_sales_orders_notes_modal_contains_structured_changed_details_activity_sections(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        // New AJAX-based approach: modal placeholder and JS handler
        $this->assertStringContainsString('so_activity_log_modal', $indexView);
        $this->assertStringContainsString('view-so-changed-activities', $indexView);
        $this->assertStringNotContainsString('id="so_modal_changed_activities"', $indexView);
    }
}
