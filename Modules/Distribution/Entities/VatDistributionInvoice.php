<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class VatDistributionInvoice extends Model
{
    protected $table = 'vat_distribution_invoices';
    protected $guarded = ['id'];

    public function customer()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Contact::class, 'customer_id');
    }

    public function lines()
    {
        return $this->hasMany(VatDistributionInvoiceLine::class, 'invoice_id');
    }

    public function cheques()
    {
        return $this->hasMany(VatDistributionInvoiceCheque::class, 'invoice_id');
    }

    public function salesRep()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\SalesAgent::class, 'sales_rep_id');
    }

    public function route()
    {
        return $this->belongsTo(Distribution_routes::class, 'route_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(DistributionVehicles::class, 'vehicle_id');
    }

    public function category()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Category::class, 'category_id');
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
