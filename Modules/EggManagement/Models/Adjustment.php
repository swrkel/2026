<?php
namespace Modules\EggManagement\Models;

class Adjustment extends EggModel
{
    protected $table = 'egg_adjustments';
    protected $casts = ['adjustment_date'=>'date'];
}
