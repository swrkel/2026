<?php
namespace Modules\EggManagement\Models;

class Flock extends EggModel
{
    protected $table = 'egg_flocks';
    protected $casts = ['started_on'=>'date','ended_on'=>'date','active'=>'boolean'];
}
