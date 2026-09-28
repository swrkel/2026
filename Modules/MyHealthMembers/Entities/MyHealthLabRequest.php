<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthLabRequest extends MyHealthBaseModel
{
    protected $table = 'myhealth_lab_requests';
    protected $guarded = ['id'];

    public function result()
    {
        return $this->hasOne(MyHealthLabResult::class, 'lab_request_id');
    }
}
