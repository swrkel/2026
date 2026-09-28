<?php
namespace Modules\TeaEstateManagement\Entities;
class SaleLine extends BaseTeaModel
{
    protected $table = 'tea_sale_lines';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
