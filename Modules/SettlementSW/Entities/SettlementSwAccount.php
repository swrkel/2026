<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP Account table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\Account imports while preserving the existing database schema and behaviour.
 */
class SettlementSwAccount extends \App\Account
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.accounts', $this->getTable()));
    }

}
