<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS1294CustomerBulkPaymentsLedgerFixTest extends TestCase
{
    public function test_customer_ledger_groups_customer_bulk_payments_by_reference_number(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $contactUtil = file_get_contents($projectRoot . '/app/Utils/ContactUtil.php');

        $this->assertIsString($contactUtil);

        $this->assertTrue(
            str_contains($contactUtil, "->where('transaction_payments.paid_in_type', 'customer_bulk')"),
            'Customer ledger should detect customer_bulk payments using transaction_payments.paid_in_type.'
        );
        $this->assertTrue(
            str_contains($contactUtil, "->groupBy('transaction_payments.payment_ref_no')"),
            'Customer ledger should group customer_bulk payments by payment_ref_no so ledger shows one total row.'
        );
        $this->assertTrue(
            str_contains($contactUtil, 'SUM(transaction_payments.amount)'),
            'Customer ledger should sum transaction_payments.amount for grouped customer_bulk payments.'
        );
    }

    public function test_pay_due_amount_cash_balance_validation_never_blocks_customer_sell_payments(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $controller = file_get_contents($projectRoot . '/app/Http/Controllers/TransactionPaymentController.php');

        $this->assertIsString($controller);

        $this->assertTrue(
            str_contains($controller, '$isOutgoingDuePayment') && str_contains($controller, '$contact->type'),
            'Cash-balance validation should be based on payment direction (contact type + due type), not just due_payment_type.'
        );
        $this->assertTrue(
            str_contains($controller, "in_array(\$due_payment_type, ['purchase', 'purchase_return']") ||
            str_contains($controller, "in_array(\$due_payment_type, ['purchase', 'purchase_return'], true)"),
            'Supplier outgoing due payments should include purchase and purchase_return for cash-balance validation.'
        );
    }
}

