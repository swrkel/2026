<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderNotesModalTest extends TestCase
{
    public function test_sales_orders_list_contains_notes_modal_elements_and_buttons(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php';

        $this->assertFileExists($filePath);
        $indexView = file_get_contents($filePath);

        $this->assertIsString($indexView);

        // Assert Notes action button exists in dropdown
        $this->assertStringContainsString('class="show-so-notes-btn"', $indexView);
        $this->assertStringContainsString('data-invoice-note=', $indexView);
        $this->assertStringContainsString('data-shipping-note=', $indexView);

        // Assert main notes modal exists
        $this->assertStringContainsString('id="salesOrderNotesModal"', $indexView);

        // Assert the three panels exist in the view
        $this->assertStringContainsString('Dis Invoice Note', $indexView);
        $this->assertStringContainsString('id="dis_invoice_note_content"', $indexView);
        $this->assertStringContainsString('Shipping Note', $indexView);
        $this->assertStringContainsString('id="shipping_note_content"', $indexView);
        $this->assertStringContainsString('Sales Order Note', $indexView);
        $this->assertStringContainsString('id="sales_order_note_content"', $indexView);

        // Assert old submodal buttons and modals are removed
        $this->assertStringNotContainsString('id="so_modal_shipping_note_btn"', $indexView);
        $this->assertStringNotContainsString('id="so_modal_sales_order_note_btn"', $indexView);
        $this->assertStringNotContainsString('id="shippingNoteDetailModal"', $indexView);
        $this->assertStringNotContainsString('id="salesOrderNoteDetailModal"', $indexView);

        // Assert jQuery click handler updates content directly
        $this->assertStringContainsString("$(document).on('click', '.show-so-notes-btn', function", $indexView);
        $this->assertStringContainsString("$('#dis_invoice_note_content').text", $indexView);
        $this->assertStringContainsString("$('#shipping_note_content').text", $indexView);
        $this->assertStringContainsString("$('#sales_order_note_content').text", $indexView);

        // Assert only salesOrderNotesModal is appended to body, others removed
        $this->assertStringContainsString("$('#salesOrderNotesModal').appendTo('body');", $indexView);
        $this->assertStringNotContainsString("$('#shippingNoteDetailModal').appendTo('body');", $indexView);
        $this->assertStringNotContainsString("$('#salesOrderNoteDetailModal').appendTo('body');", $indexView);
    }
}
