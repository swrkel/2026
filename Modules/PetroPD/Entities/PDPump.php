<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PDPump extends Model
{
    protected $table = 'pumps';
    protected $guarded = ['id'];

    public function scopeOtherSales($query)
    {
        if (!Schema::hasColumn('pumps', 'is_other_sales_pump')) {
            return $query;
        }
        return $query->where('is_other_sales_pump', 1);
    }

    public function scopeNotOtherSales($query)
    {
        if (!Schema::hasColumn('pumps', 'is_other_sales_pump')) {
            return $query;
        }
        return $query->where('is_other_sales_pump', 0);
    }
}
