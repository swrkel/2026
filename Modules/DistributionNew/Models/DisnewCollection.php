<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewCollection extends Model
{
    use SoftDeletes;

    protected $table = 'disnew_collections';

    protected $fillable = [
        'business_id','business_location_id','sales_rep_id','customer_id','sales_order_id','sales_invoice_id',
        'collection_no','collection_date','payment_method','reference_no','amount','status','note','created_by','updated_by'
    ];
}
