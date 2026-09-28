<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthDiagnosis extends MyHealthBaseModel
{
    protected $table = 'myhealth_diagnoses';
    protected $guarded = ['id'];
}
