<?php
namespace Modules\TeaEstateManagement\Entities;
class Estate extends BaseTeaModel
{
    protected $table = 'tea_estates';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
