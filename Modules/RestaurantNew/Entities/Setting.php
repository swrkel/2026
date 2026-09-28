<?php
namespace Modules\RestaurantNew\Entities;

class Setting extends RestnewModel
{
    protected $table = 'restnew_settings';
    protected $casts = [
        'value_json' => 'array',
        'is_encrypted' => 'boolean',
    ];
}
