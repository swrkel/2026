<?php

namespace Modules\SettlementSW\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Settlement SW local work-shift model wrapper.
 *
 * The physical table is a shared ERP setup table, but Settlement SW should not
 * depend on the HR module class directly.
 */
class SettlementSwWorkShift extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.work_shifts', 'work_shifts'));
    }
    protected $guarded = ['id'];
}
