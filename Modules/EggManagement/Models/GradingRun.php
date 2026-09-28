<?php
namespace Modules\EggManagement\Models;

class GradingRun extends EggModel
{
    protected $table = 'egg_grading_runs';
    protected $casts = ['graded_on'=>'date'];
}
