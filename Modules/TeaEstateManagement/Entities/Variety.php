<?php
namespace Modules\TeaEstateManagement\Entities;
class Variety extends BaseTeaModel
{
    protected $table = 'tea_varieties';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
