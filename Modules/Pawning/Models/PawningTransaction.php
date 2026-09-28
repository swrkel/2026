<?php

namespace Modules\Pawning\Models;

use Illuminate\Database\Eloquent\Model;

class PawningTransaction extends Model
{
    protected $table = 'pawning_transactions';
    protected $guarded = ['id'];

    public function pledge()
    {
        return $this->belongsTo(Pledge::class, 'pawning_pledge_id');
    }
}
