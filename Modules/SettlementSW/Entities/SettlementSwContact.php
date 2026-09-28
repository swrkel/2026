<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP Contact table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\Contact imports while preserving the existing database schema and behaviour.
 */
class SettlementSwContact extends \App\Contact
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.contacts', $this->getTable()));
    }

}
