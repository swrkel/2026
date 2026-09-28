<?php
namespace Modules\TeaEstateManagement\Entities;
class Sale extends BaseTeaModel
{
    protected $table = 'tea_sales';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
