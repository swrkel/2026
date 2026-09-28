<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP BusinessLocation table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\BusinessLocation imports while preserving the existing database schema and behaviour.
 */
class SettlementSwBusinessLocation extends \App\BusinessLocation
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.business_locations', $this->getTable()));
    }

}
