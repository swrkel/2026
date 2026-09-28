<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP AccountTransaction table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\AccountTransaction imports while preserving the existing database schema and behaviour.
 */
class SettlementSwAccountTransaction extends \App\AccountTransaction
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.account_transactions', $this->getTable()));
    }

}
