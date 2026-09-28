<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

abstract class RestaurantNewBaseModel extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->business_id) && session()->has('business.id')) {
                $model->business_id = session('business.id');
            }
        });
    }
}
