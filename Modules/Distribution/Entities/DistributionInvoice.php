<?php
namespace Modules\Distribution\Entities;

use Modules\Distribution\Entities\DistributionContact as Contact;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DistributionInvoice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'customer_id',
        'customer_name',
        'customer_address',
        'customer_contact',
        'date',
        'delivery_date',
        'sales_rep_id',
        'route_id',
        'vehicle_id',
        'category_id',
        'invoice_no',
        'loading_sheet_no',
        'invoice_note',
        'shipping_note',
        'sales_order_note',
        'shipping_details',
        'shipping_status',
        'status',
        'sales_order_id',
        'added_by',
        'updated_by',
        'total',
        'discount',
        'grand_total',
        'payment_cash',
        'payment_card',
        'payment_credit',
        'payment_cheque',
        'payment_total',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('distribution_invoice')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function lines()
    {
        return $this->hasMany(DistributionInvoiceLine::class, 'invoice_id');
    }

    public function customer()
    {
        // Try Customer model first, fallback to Contact
        if (class_exists(\Modules\Distribution\Entities\Core\Customer::class)) {
            return $this->belongsTo(\Modules\Distribution\Entities\Core\Customer::class, 'customer_id');
        }
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function salesRep()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\SalesAgent::class, 'sales_rep_id');
    }

    public function route()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Distribution_routes::class, 'route_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\DistributionVehicles::class, 'vehicle_id');
    }

    public function category()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Category::class, 'category_id');
    }

    public function cheques()
    {
        return $this->hasMany(DistributionInvoiceCheque::class, 'invoice_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(DistributionSalesOrder::class, 'sales_order_id');
    }

    public function addedUser()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'added_by');
    }

    public function updatedUser()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'updated_by');
    }
}
