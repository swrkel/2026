<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class F10FormHeader extends Model
{
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'F10 Form';
    protected $table = 'mpcs_f10_headers';
    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['fillable']);
    }

    public function details()
    {
        return $this->hasMany(F10FormDetail::class, 'header_id', 'id');
    }

    public function manager()
    {
        return $this->belongsTo(F10Manager::class, 'manager_id', 'id');
    }
}
