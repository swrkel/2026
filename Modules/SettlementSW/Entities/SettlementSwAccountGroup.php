<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP AccountGroup table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\AccountGroup imports while preserving the existing database schema and behaviour.
 */
class SettlementSwAccountGroup extends \App\AccountGroup
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.account_groups', $this->getTable()));
    }

}
