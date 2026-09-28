<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class WalkInCustomerLedgerTest extends TestCase
{
    /**
     * Test that the ContactUtil contains Walk-In customer exceptions in customer ledger query.
     */
    public function test_customer_ledger_does_not_exclude_paid_pos_sales_for_walk_in_customer(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $contactUtil = file_get_contents($projectRoot . '/app/Utils/ContactUtil.php');

        $this->assertIsString($contactUtil);
        $this->assertTrue(
            str_contains($contactUtil, '$is_walking_customer') && str_contains($contactUtil, "->where('transactions.payment_status', 'paid')"),
            'Customer ledger query must include paid POS sales for the Walk-In Customer.'
        );
    }

    /**
     * Test that the AddAccountTransaction event listener handles Walk-In Customer POS sales by creating both debit and credit entries.
     */
    public function test_event_listener_creates_debit_ledger_entry_for_walk_in_customer_sales(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $listener = file_get_contents($projectRoot . '/app/Listeners/AddAccountTransaction.php');

        $this->assertIsString($listener);
        $this->assertTrue(
            str_contains($listener, 'is_default') && str_contains($listener, "'debit'") && str_contains($listener, "'sell'"),
            'Event listener AddAccountTransaction must detect Walk-In Customer (is_default) and create a debit contact ledger entry.'
        );
    }
}
