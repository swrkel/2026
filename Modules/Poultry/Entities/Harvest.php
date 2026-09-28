<?php

namespace Modules\Poultry\Entities;

use Modules\Poultry\Entities\Shared\Contact;
use Modules\Poultry\Entities\Shared\Variation;

/**
 * Birds leaving the batch - full or partial depletion, cull sales, or spent
 * hens at the end of a laying cycle. Where a variation is mapped, the live
 * weight is produced into shared stock so the existing sales modules can
 * invoice it; this module writes no invoicing code of its own.
 */
class Harvest extends PoultryModel
{
    protected $table = 'poultry_harvests';

    protected $dates = ['harvest_date'];

    protected $casts = ['is_posted' => 'boolean'];

    public const TYPES = [
        'full'       => 'Full depletion',
        'partial'    => 'Partial harvest',
        'cull_sale'  => 'Cull sale',
        'spent_hen'  => 'Spent hen sale',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Contact::class, 'buyer_contact_id');
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    public function getIsStockedAttribute()
    {
        return ! empty($this->variation_id);
    }
}
