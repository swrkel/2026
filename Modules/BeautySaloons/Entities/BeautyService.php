<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyService extends Model
{
    protected $table = 'bs_services';
    protected $guarded = ['id'];

    public function category()
    {
        return $this->belongsTo(BeautyServiceCategory::class, 'category_id');
    }
}
