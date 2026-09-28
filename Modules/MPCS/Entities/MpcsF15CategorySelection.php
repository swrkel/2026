<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;
use App\User;

class MpcsF15CategorySelection extends Model
{
    protected $table = 'mpcs_f15_category_selections';

    protected $fillable = [
        'business_id',
        'category_ids',
        'created_by'
    ];

    protected $casts = [
        'category_ids' => 'array'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
