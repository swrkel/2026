<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CashAccountBookFooterTotalTest extends TestCase
{
    /** @test */
    public function cash_account_book_renders_footer_total_row(): void
    {
        // Regression: Accounting module / List accounts / Cash account book was
        // hiding the DataTable footer Total row by wrapping <tfoot> in
        // @if (empty($is_cash_account)). Cash accounts should display the
        // Total row (with debit/credit sums) just like non-cash accounts.
        $view = file_get_contents(__DIR__ . '/../../resources/views/account/show.blade.php');

        $this->assertStringContainsString(
            'id="footer_debit_total"',
            $view,
            'Account book view must declare the footer debit total span.'
        );
        $this->assertStringContainsString(
            'id="footer_credit_total"',
            $view,
            'Account book view must declare the footer credit total span.'
        );

        $tfootStart = strpos($view, '<tfoot>');
        $this->assertNotFalse($tfootStart, 'Account book view must render a <tfoot> for the totals row.');

        $before = substr($view, 0, $tfootStart);
        // Walk backwards from <tfoot> through whitespace; the directly
        // preceding non-whitespace token must NOT be the cash-account guard.
        $trimmed = rtrim($before);
        $this->assertStringEndsNotWith(
            '@if (empty($is_cash_account))',
            $trimmed,
            'Cash accounts must not have their footer Total row hidden — the @if (empty($is_cash_account)) wrapper around <tfoot> must be removed so the Total row renders for cash accounts too.'
        );
    }
}
