<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class DistributionNumberingPrefix extends Model
{
    protected $table = 'distribution_prefix_settings';

    protected $fillable = [
        'business_id',
        'numbering_type',
        'prefix',
        'starting_no',
        'current_no',
        'created_by',
    ];

    /**
     * User who added the record
     */
    public function addedBy()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'created_by');
    }
}
