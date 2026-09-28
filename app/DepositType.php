<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DepositType extends Model
{
    protected $table = 'deposit_types';

    protected $fillable = [
        'business_id',
        'name',
        'period',
        'period_value',
        'status',
        'created_by',
        'last_edited_by'
    ];

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(\App\User::class, 'last_edited_by');
    }

    public function activities()
    {
        return $this->hasMany(\App\DepositTypeActivity::class, 'deposit_type_id');
    }
}
