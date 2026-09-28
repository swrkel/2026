<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Finance\Services\Payments\SupplierAdvancePaymentAccountNormalizer;

class RepairSupplierAdvanceBankTransferParentChildAccounts extends Migration
{
    public function up()
    {
        // S717 rerun with the parent/child-aware normalizer. The earlier S712
        // migration may already be recorded as completed, so a new migration is
        // required for existing tenants to receive the corrected historical fix.
        app(SupplierAdvancePaymentAccountNormalizer::class)->repairHistorical();
    }

    public function down()
    {
        // Deliberately irreversible: restoring a known-wrong Cash posting would
        // corrupt the account books again.
    }
}
