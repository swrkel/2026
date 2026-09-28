<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthOperationTheatreRoom extends Model
{
    protected $table = 'myhealth_operation_theatre_rooms';

    protected $fillable = [
        'business_id', 'location_id', 'room_code', 'room_name', 'room_type',
        'floor', 'status', 'equipment_notes', 'created_by', 'updated_by',
    ];
}
