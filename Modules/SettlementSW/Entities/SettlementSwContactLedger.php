<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP ContactLedger table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\ContactLedger imports while preserving the existing database schema and behaviour.
 */
class SettlementSwContactLedger extends \App\ContactLedger
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.contact_ledger', $this->getTable()));
    }

}
