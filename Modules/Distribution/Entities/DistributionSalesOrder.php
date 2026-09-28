<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DistributionSalesOrder extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'customer_id',
        'customer_name',
        'customer_contact',
        'customer_address',
        'date',
        'delivery_date',
        'sales_rep_id',
        'route_id',
        'vehicle_id',
        'category_id',
        'sales_order_no',
        'loading_sheet_no',
        'invoice_note',
        'shipping_note',
        'shipping_details',
        'shipping_status',
        'status',
        'total',
        'discount',
        'grand_total',
        'payment_cash',
        'payment_card',
        'payment_credit',
        'payment_cheque',
        'payment_total',
        'created_by',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('distribution_sales_order')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function lines()
    {
        return $this->hasMany(DistributionSalesOrderLine::class, 'sales_order_id');
    }

    public function customer()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Contact::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasMany(DistributionInvoice::class, 'sales_order_id');
    }

    public function distributionRoute()
    {
        return $this->belongsTo(Distribution_routes::class, 'route_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'updated_by');
    }
}
