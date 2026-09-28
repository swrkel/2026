<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PumperDashboardCloseShiftViewTest extends TestCase
{
    /** @test */
    public function close_shift_table_totals_testing_litres_and_locks_closed_shift_actions(): void
    {
        $view = file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Resources/views/actions/closing_shift.blade.php'
        );
        $partial = file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Resources/views/partials/closing_shift.blade.php'
        );
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Http/Controllers/ClosingShiftController.php'
        );

        $this->assertStringContainsString(
            '<span class="display_currency testing_ltr"',
            $controller,
            'Close Shift rows must expose testing_ltr with data-orig-value so the footer total can sum it.'
        );

        $this->assertStringContainsString(
            "'footerCallback': function(row, data, start, end, display)",
            $view,
            'Close Shift must calculate the Test Qty total from DataTables row data, not visible DOM text only.'
        );

        $this->assertStringContainsString(
            "id=\"footer_cs_testing_ltr\"",
            $partial,
            'Close Shift footer must include the Test Qty total cell.'
        );

        $this->assertStringContainsString(
            '@if (empty($shift) || $shift->status != 2)',
            $view,
            'Other Sales and Payment buttons must render only while the selected shift is open.'
        );

        $this->assertStringNotContainsString(
            "lllllll",
            file_get_contents(__DIR__ . '/../../Modules/PumperDashboard/Resources/views/partials/closing_shift_summary.blade.php'),
            'Closed shift label must not contain debug text.'
        );
    }
}
