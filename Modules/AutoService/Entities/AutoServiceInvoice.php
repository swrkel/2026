<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceInvoice extends Model
{
    use SoftDeletes;
    protected $table='auto_service_invoices';
    protected $guarded=[];
    public function lines(){return $this->hasMany(AutoServiceInvoiceLine::class,'invoice_id');}
    public function job(){return $this->belongsTo(AutoServiceJob::class,'job_id');}
    public function payments(){return $this->hasMany(AutoServicePayment::class,'invoice_id');}
}
