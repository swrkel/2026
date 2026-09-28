<?php
namespace Modules\EzyLaw\Contracts;
interface FinanceGateway
{
    public function available(): bool;
    public function syncInvoice(int $invoiceId): array;
    public function syncPayment(int $paymentId): array;
    public function syncExpense(int $expenseId): array;
    public function syncTrustTransaction(int $transactionId): array;
    public function syncInvoiceAdjustment(int $adjustmentId): array;
    public function syncClientAdvance(int $advanceId): array;
    public function syncAdvanceAllocation(int $allocationId): array;
}
