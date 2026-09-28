<?php

namespace Modules\Essentials\Entities;

use Illuminate\Database\Eloquent\Model;

class EssentialsEmployee extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the designation associated with the employee.
     */
    public function designation()
    {
        return $this->belongsTo(\Modules\Essentials\Entities\HrmDesignation::class, 'designation');
    }

    // public function location()
    // {
    //     return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    // }
}
