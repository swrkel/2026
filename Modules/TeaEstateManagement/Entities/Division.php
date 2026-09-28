<?php
namespace Modules\TeaEstateManagement\Entities;
class Division extends BaseTeaModel
{
    protected $table = 'tea_divisions';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
