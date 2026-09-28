<?php
namespace Modules\TeaEstateManagement\Entities;
class Harvest extends BaseTeaModel
{
    protected $table = 'tea_harvests';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
