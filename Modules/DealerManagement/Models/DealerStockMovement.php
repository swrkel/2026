<?php
namespace Modules\DealerManagement\Models;

use Illuminate\Database\Eloquent\Model;

class DealerStockMovement extends Model
{
    protected $table = 'dlr_stock_movements';
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
