<?php

namespace Modules\AirlineTicketingNew\Entities;

use Illuminate\Database\Eloquent\Model;

abstract class BaseAirlineTicketingModel extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Model $model): void {
            if (!$model->business_id) {
                $model->business_id = (int) session('business.id');
            }

            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function (Model $model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
