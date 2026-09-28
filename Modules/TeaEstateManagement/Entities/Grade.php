<?php
namespace Modules\TeaEstateManagement\Entities;
class Grade extends BaseTeaModel
{
    protected $table = 'tea_grades';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
