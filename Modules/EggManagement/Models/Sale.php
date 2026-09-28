<?php
namespace Modules\EggManagement\Models;

class Sale extends EggModel
{
    protected $table = 'egg_sales';
    protected $casts = ['sale_date'=>'date'];
}
