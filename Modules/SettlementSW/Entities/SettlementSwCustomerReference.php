<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP CustomerReference table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\CustomerReference imports while preserving the existing database schema and behaviour.
 */
class SettlementSwCustomerReference extends \App\CustomerReference
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.customer_references', $this->getTable()));
    }

}
