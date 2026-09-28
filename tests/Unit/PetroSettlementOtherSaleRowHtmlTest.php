<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroSettlementOtherSaleRowHtmlTest extends TestCase
{
    /** @test */
    public function settlement_save_other_sale_returns_row_html_and_global_handler_uses_it(): void
    {
        // Regression: on Petro / List direct settlements / Action / Edit
        // (and Edit no_change), adding an Other Sale showed the total but
        // not the row. The global .btn_other_sale handler in app.js was
        // building the row from JS globals (other_sale_code, ...) which
        // were unreliable on the edit page. The fix is to have
        // SettlementController@saveOtherSale return a ready-made row_html
        // (mirroring SettlementPDController) and have the global handler
        // prefer result.row_html over the JS-built fallback.
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementController.php'
        );

        $methodStart = strpos($controller, 'public function saveOtherSale');
        $this->assertNotFalse(
            $methodStart,
            'Could not locate saveOtherSale in SettlementController.'
        );

        $methodBody = substr($controller, $methodStart, 6000);

        $this->assertStringContainsString(
            "'row_html'",
            $methodBody,
            'SettlementController@saveOtherSale must return a row_html key so the edit form can render the new row reliably.'
        );

        $this->assertStringContainsString(
            'delete_other_sale',
            $methodBody,
            'The returned row_html must include the delete_other_sale button so the row is fully usable.'
        );

        $appJs = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Resources/assets/js/app.js'
        );

        $handlerStart = strpos($appJs, "on('click.petro_other_sale', '.btn_other_sale'");
        $this->assertNotFalse(
            $handlerStart,
            'Could not locate the global .btn_other_sale handler in app.js.'
        );

        $handlerBody = substr($appJs, $handlerStart, 4000);

        $this->assertStringContainsString(
            'result.row_html',
            $handlerBody,
            'The global .btn_other_sale handler must prefer result.row_html when prepending the new row, so the edit form shows the row even when JS globals are stale.'
        );
    }
}
