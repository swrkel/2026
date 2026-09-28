<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP Product table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\Product imports while preserving the existing database schema and behaviour.
 */
class SettlementSwProduct extends \App\Product
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.products', $this->getTable()));
    }

}
