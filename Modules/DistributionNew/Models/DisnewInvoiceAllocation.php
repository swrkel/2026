<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewInvoiceAllocation extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_invoice_allocations';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'sales_order_id',
    'sales_order_line_id',
    'sales_invoice_id',
    'sales_invoice_line_id',
    'product_id',
    'ordered_qty',
    'invoiced_qty',
    'remaining_qty',
    'created_by'
    ];
}
