<?php
namespace Modules\EggManagement\Models;

class Purchase extends EggModel
{
    protected $table = 'egg_purchases';
    protected $casts = ['purchase_date'=>'date'];
}
