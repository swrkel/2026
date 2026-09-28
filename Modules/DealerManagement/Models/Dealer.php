<?php
namespace Modules\DealerManagement\Models;

use Illuminate\Database\Eloquent\Model;

class Dealer extends Model
{
    protected $table = 'dlr_dealers';
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
