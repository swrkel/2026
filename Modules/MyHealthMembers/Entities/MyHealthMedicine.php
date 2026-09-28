<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthMedicine extends MyHealthBaseModel
{
    protected $table = 'myhealth_medicines';
    protected $guarded = ['id'];

    public function batches()
    {
        return $this->hasMany(MyHealthMedicineBatch::class, 'medicine_id');
    }
}
