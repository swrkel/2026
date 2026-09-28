<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'description'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the business that owns the user group
     */
    public function business()
    {
        return $this->belongsTo('App\Business');
    }

    /**
     * Get users that belong to this group
     */
    public function users()
    {
        return $this->belongsToMany('App\User', 'user_group_user', 'user_group_id', 'user_id');
    }
}
