<?php
namespace Modules\DealerManagement\Models;

use Illuminate\Database\Eloquent\Model;

class DealerIntegrationEvent extends Model
{
    protected $table = 'dlr_integration_events';
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
