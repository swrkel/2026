<?php

namespace Modules\PetroDirect\Entities;

use Illuminate\Database\Eloquent\Model;

class DayCountSetting extends Model
{
    /**
     * Table used for daily collection day-count settings.
     */
    protected $table = 'day_count_settings';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
