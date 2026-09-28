<?php

return [
    /*
     * FK-only mode: when true, UpdatesSettlementTransactions only uses
     * petro_settlement_id lookups and never falls back to LIKE matching on
     * ref_no/invoice_no.
     *
     * Rollout plan:
     * Week 0: false (default) - Day 1 coexistence behaviour.
     * Week 1: false on prod, true in staging - observe staging.
     * Week 2: true on prod, with metrics monitoring.
     * Week 3: delete the LIKE branch and retire this flag.
     */
    'fk_only_settlement_lookup' => env('PETRO_FK_ONLY_SETTLEMENT_LOOKUP', false),
];
