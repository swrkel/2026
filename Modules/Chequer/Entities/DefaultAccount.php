<?php

namespace Modules\Chequer\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefaultAccount extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    protected static $logName = 'Default Account'; 
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

     /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'is_main_account' => 'boolean',
        'visible' => 'boolean',
        'show_in_balance_sheet' => 'boolean',
    ];
    
    //modilfed by iftekhar
    public function defaultAccountType(){
        return $this->belongsTo(DefaultAccountType::class, 'account_type_id', 'id');
    }
    //modilfed by iftekhar
    public function defaultAccountGroup(){
        return $this->hasOne(DefaultAccountGroup::class, 'id', 'asset_type');
    }

    public function parentAccount()
    {
        return $this->belongsTo(self::class, 'parent_account_id');
    }

    public function subAccounts()
    {
        return $this->hasMany(self::class, 'parent_account_id');
    }
}
