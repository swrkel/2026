<?php

namespace Modules\Poultry\Entities;

/**
 * Movement of birds between houses, or from a rearing (pullet) batch into a
 * laying batch at point of lay. In the latter case the rearing cost carried on
 * the source batch transfers as the destination batch's opening capitalised
 * value - see Services\CostingService::transferRearingCost().
 */
class BatchTransfer extends PoultryModel
{
    protected $table = 'poultry_batch_transfers';

    protected $dates = ['transfer_date'];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function toBatch()
    {
        return $this->belongsTo(Batch::class, 'to_batch_id');
    }

    public function fromHouse()
    {
        return $this->belongsTo(House::class, 'from_house_id');
    }

    public function toHouse()
    {
        return $this->belongsTo(House::class, 'to_house_id');
    }

    public function getIsPointOfLayAttribute()
    {
        return ! empty($this->to_batch_id);
    }
}
