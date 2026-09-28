<?php
namespace Modules\Tailoring\Entities;
use Illuminate\Database\Eloquent\Model;
class TailoringQuotation extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['quotation_date'=>'date','valid_until'=>'date','items'=>'array','totals'=>'array'];
}
