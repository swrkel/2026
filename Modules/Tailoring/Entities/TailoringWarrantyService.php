<?php
namespace Modules\Tailoring\Entities;
use Illuminate\Database\Eloquent\Model;
class TailoringWarrantyService extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['service_date'=>'date','resolved_at'=>'datetime','photos'=>'array'];
}
