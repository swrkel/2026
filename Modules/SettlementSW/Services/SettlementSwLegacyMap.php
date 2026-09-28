<?php

namespace Modules\SettlementSW\Services;

/**
 * Central place for legacy-compatible keys/columns used by Settlement SW.
 *
 * Tenant databases may still use old Petro names. Controllers should not hard-code
 * those names; this service keeps compatibility while making future migration to
 * Settlement SW-owned names a one-file config change.
 */
class SettlementSwLegacyMap
{
    public function productModuleKey(): string
    {
        return config('settlementsw.product_module_key', 'settlement_sw');
    }

    public function settlementReferenceColumn(): string
    {
        return config('settlementsw.columns.settlement_reference', 'settlement_id');
    }
}
