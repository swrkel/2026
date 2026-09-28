<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Finance\Services\Payments\SupplierAdvancePaymentAccountNormalizer;

class RepairSupplierAdvanceBankTransferAccounts extends Migration
{
    public function up()
    {
        // Idempotent data correction. Running the Finance migrations on every
        // tenant automatically repairs historical supplier advance Bank
        // Transfer rows that were incorrectly posted to Cash.
        app(SupplierAdvancePaymentAccountNormalizer::class)->repairHistorical();
    }

    public function down()
    {
        // Intentionally not reversed. Reinstating a known-wrong Cash posting
        // would corrupt the account books again.
    }
}
