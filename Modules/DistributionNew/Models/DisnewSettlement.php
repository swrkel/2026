<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewSettlement extends Model
{
    use SoftDeletes;

    protected $table = 'disnew_settlements';

    protected $fillable = [
        'business_id','business_location_id','sales_rep_id','vehicle_id','settlement_no','settlement_date',
        'opening_stock_value','loaded_value','sold_value','returned_value','collection_total',
        'shortage_value','excess_value','status','finalized_at','finalized_by','note','created_by','updated_by'
    ];

    public function lines(){ return $this->hasMany(DisnewSettlementLine::class, 'settlement_id'); }
}
