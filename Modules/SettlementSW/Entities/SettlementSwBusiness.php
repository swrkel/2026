<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP Business table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\Business imports while preserving the existing database schema and behaviour.
 */
class SettlementSwBusiness extends \App\Business
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.business', $this->getTable()));
    }

}
