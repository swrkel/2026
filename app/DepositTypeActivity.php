<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DepositTypeActivity extends Model
{
    protected $table = 'deposit_type_activities';

    protected $fillable = [
        'deposit_type_id',
        'original_added_by',
        'changed_by_user',
        'details'
    ];

    public function depositType()
    {
        return $this->belongsTo(\App\DepositType::class, 'deposit_type_id');
    }
}
