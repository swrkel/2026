<?php
namespace Modules\DealerManagement\Models;

use Illuminate\Database\Eloquent\Model;

class DealerUserOutlet extends Model
{
    protected $table = 'dlr_user_outlets';
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
