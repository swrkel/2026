<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AccountBookFooterTotalCalculationTest extends TestCase
{
    /** @test */
    public function account_book_js_uses_robust_footer_total_calculation(): void
    {
        $view = file_get_contents(__DIR__ . '/../../resources/views/account/show.blade.php');

        // The broken implementation uses simple parseFloat(td.text().replace(/,/g, ''))
        // The robust implementation must find display_currency or data-orig-value
        $this->assertStringContainsString(
            'data-orig-value',
            $view,
            'The sum_table_col javascript helper must read data-orig-value to support formatted currency values and nested classes.'
        );

        $this->assertStringContainsString(
            'hasClass(\'deleted-expense-row\')',
            $view,
            'The sum_table_col javascript helper must check for deleted-expense-row class to exclude deleted rows.'
        );

        $this->assertStringContainsString(
            '.data(\'orig-value\',',
            $view,
            'The account book view must update the jQuery data cache for footer totals.'
        );
    }
}
