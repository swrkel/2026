<?php
namespace Modules\TeaEstateManagement\Entities;
class Party extends BaseTeaModel
{
    protected $table = 'tea_parties';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
