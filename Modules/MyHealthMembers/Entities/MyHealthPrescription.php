<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthPrescription extends MyHealthBaseModel
{
    protected $table = 'myhealth_prescriptions';
    protected $guarded = ['id'];
}
