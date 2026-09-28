<?php

namespace Modules\Poultry\Entities;

use Modules\Poultry\Entities\Shared\Variation;

/**
 * Feed or medication issued to a batch. The item itself is a row in the shared
 * products / variations tables - this records the link plus the id of the
 * stock transaction it posted, so a correction reverses cleanly rather than
 * editing stock in place.
 */
class FeedConsumption extends PoultryModel
{
    protected $table = 'poultry_feed_consumptions';

    protected $dates = ['consumption_date'];

    protected $casts = ['is_posted' => 'boolean'];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('consumption_date', [$from, $to]);
    }

    public function scopePosted($query)
    {
        return $query->where('is_posted', 1);
    }
}
