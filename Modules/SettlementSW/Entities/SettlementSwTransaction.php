<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP Transaction table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\Transaction imports while preserving the existing database schema and behaviour.
 */
class SettlementSwTransaction extends \App\Transaction
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.transactions', $this->getTable()));
    }

}
