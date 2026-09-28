<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthDocument extends MyHealthBaseModel
{
    protected $table = 'myhealth_documents';
    protected $guarded = ['id'];
}
