<?php
namespace Modules\TeaEstateManagement\Entities;
class FieldActivity extends BaseTeaModel
{
    protected $table = 'tea_field_activities';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
