<?php
namespace Modules\TeaEstateManagement\Entities;
class Setting extends BaseTeaModel
{
    protected $table = 'tea_settings';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
