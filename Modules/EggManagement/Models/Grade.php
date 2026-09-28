<?php
namespace Modules\EggManagement\Models;

class Grade extends EggModel
{
    protected $table = 'egg_grades';
    protected $casts = ['active'=>'boolean'];
}
