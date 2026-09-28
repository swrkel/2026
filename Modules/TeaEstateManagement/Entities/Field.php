<?php
namespace Modules\TeaEstateManagement\Entities;
class Field extends BaseTeaModel
{
    protected $table = 'tea_fields';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
