<?php
namespace Modules\TeaEstateManagement\Entities;
class Factory extends BaseTeaModel
{
    protected $table = 'tea_factories';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
